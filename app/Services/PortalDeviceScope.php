<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PortalDeviceScope
{
    public function visibleDevices(User $user): Builder
    {
        $user->loadMissing('role');
        $query = DB::table('devices')
            ->leftJoin('companies', 'companies.company_id', '=', 'devices.company_id')
            ->leftJoin('device_assignments', function ($join): void {
                $join->on('device_assignments.device_id', '=', 'devices.device_id')
                    ->where('device_assignments.is_current', true);
            })
            ->leftJoin('users as owners', 'owners.user_id', '=', 'device_assignments.owner_user_id')
            ->leftJoin('shops', 'shops.shop_id', '=', 'device_assignments.shop_id')
            ->whereNull('devices.deleted_at')
            ->select(
                'devices.device_id',
                'devices.device_uid',
                'devices.model',
                'devices.lifecycle_status',
                'devices.connectivity_status',
                'devices.last_seen_at',
                'companies.company_name',
                'device_assignments.owner_user_id',
                'owners.full_name as owner_name',
                'shops.shop_name',
            );

        switch ($user->role?->role_code) {
            case 'COMPANY_ADMIN':
                $query->where('devices.company_id', $user->company_id ?? 0);
                break;

            case 'DEALER':
            case 'SUB_DEALER':
                $accountIds = $this->descendantAccountIds((int) $user->user_id);
                $query->whereExists(function ($assignment) use ($accountIds): void {
                    $assignment->selectRaw('1')
                        ->from('device_assignments as scoped_assignments')
                        ->whereColumn('scoped_assignments.device_id', 'devices.device_id')
                        ->where('scoped_assignments.is_current', true)
                        ->whereIn('scoped_assignments.owner_user_id', $accountIds);
                });
                break;

            case 'END_CUSTOMER':
                $query->whereExists(function ($assignment) use ($user): void {
                    $assignment->selectRaw('1')
                        ->from('device_assignments as scoped_assignments')
                        ->whereColumn('scoped_assignments.device_id', 'devices.device_id')
                        ->where('scoped_assignments.is_current', true)
                        ->where(function ($scope) use ($user): void {
                            $scope->where('scoped_assignments.owner_user_id', $user->user_id)
                                ->orWhereExists(function ($shop) use ($user): void {
                                    $shop->selectRaw('1')
                                        ->from('shops as customer_shops')
                                        ->whereColumn('customer_shops.shop_id', 'scoped_assignments.shop_id')
                                        ->where('customer_shops.customer_user_id', $user->user_id);
                                });
                        });
                });
                break;
        }

        return $query;
    }

    public function visibleDeviceIds(User $user): Builder
    {
        return $this->visibleDevices($user)->select('devices.device_id');
    }

    public function dealerHierarchy(User $dealer, Builder $visibleDevices): array
    {
        $accounts = DB::select(
            'WITH RECURSIVE account_tree AS (
                SELECT user_id, parent_user_id
                FROM users
                WHERE parent_user_id = ? AND deleted_at IS NULL
                UNION
                SELECT users.user_id, users.parent_user_id
                FROM users
                INNER JOIN account_tree ON users.parent_user_id = account_tree.user_id
                WHERE users.deleted_at IS NULL
            )
            SELECT users.user_id, users.parent_user_id, users.full_name, users.email, users.mobile,
                users.status, roles.role_code, roles.role_name
            FROM account_tree
            INNER JOIN users ON users.user_id = account_tree.user_id
            INNER JOIN roles ON roles.role_id = users.role_id
            WHERE users.status = ?
            ORDER BY roles.role_code, users.full_name',
            [$dealer->user_id, 'active'],
        );

        $devicesByOwner = (clone $visibleDevices)
            ->whereNotNull('device_assignments.owner_user_id')
            ->orderBy('devices.device_uid')
            ->get()
            ->groupBy(fn (object $device): int => (int) $device->owner_user_id);

        $nodes = [];
        $childIdsByParent = [];
        foreach ($accounts as $account) {
            $nodes[(int) $account->user_id] = [
                'account' => $account,
                'devices' => $devicesByOwner->get((int) $account->user_id, collect())->all(),
            ];
            $childIdsByParent[(int) $account->parent_user_id][] = (int) $account->user_id;
        }

        $buildBranch = function (int $userId) use (&$buildBranch, $nodes, $childIdsByParent): array {
            $branch = $nodes[$userId];
            $branch['children'] = [];
            foreach ($childIdsByParent[$userId] ?? [] as $childId) {
                $branch['children'][] = $buildBranch($childId);
            }

            return $branch;
        };

        $roots = [];
        foreach ($childIdsByParent[(int) $dealer->user_id] ?? [] as $childId) {
            $roots[] = $buildBranch($childId);
        }

        return $roots;
    }

    private function descendantAccountIds(int $userId): array
    {
        return array_map(
            static fn (object $row): int => (int) $row->user_id,
            DB::select(
                'WITH RECURSIVE account_tree AS (
                    SELECT user_id FROM users WHERE user_id = ? AND deleted_at IS NULL
                    UNION ALL
                    SELECT users.user_id
                    FROM users
                    INNER JOIN account_tree ON users.parent_user_id = account_tree.user_id
                    WHERE users.deleted_at IS NULL
                )
                SELECT user_id FROM account_tree',
                [$userId],
            ),
        );
    }
}
