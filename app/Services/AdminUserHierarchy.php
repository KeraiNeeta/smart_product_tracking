<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AdminUserHierarchy
{
    public function tree(): array
    {
        $accounts = DB::table('users')
            ->join('roles', 'roles.role_id', '=', 'users.role_id')
            ->leftJoin('companies', 'companies.company_id', '=', 'users.company_id')
            ->whereNull('users.deleted_at')
            ->orderBy('roles.role_code')
            ->orderBy('users.full_name')
            ->get([
                'users.user_id',
                'users.parent_user_id',
                'users.company_id',
                'users.full_name',
                'users.email',
                'users.mobile',
                'users.status',
                'roles.role_code',
                'roles.role_name',
                'companies.company_name',
            ]);

        $devicesByOwner = DB::table('device_assignments')
            ->join('devices', 'devices.device_id', '=', 'device_assignments.device_id')
            ->leftJoin('shops', 'shops.shop_id', '=', 'device_assignments.shop_id')
            ->where('device_assignments.is_current', true)
            ->whereNull('devices.deleted_at')
            ->orderBy('devices.device_uid')
            ->get([
                'device_assignments.owner_user_id',
                'devices.device_uid',
                'devices.model',
                'devices.lifecycle_status',
                'devices.connectivity_status',
                'shops.shop_name',
            ])
            ->groupBy(fn (object $device): int => (int) $device->owner_user_id);

        $nodes = [];
        $childIdsByParent = [];
        foreach ($accounts as $account) {
            $userId = (int) $account->user_id;
            $nodes[$userId] = [
                'account' => $account,
                'devices' => $devicesByOwner->get($userId, collect())->all(),
                'children' => [],
            ];
            $childIdsByParent[(int) $account->parent_user_id][] = $userId;
        }

        $visited = [];
        $building = [];
        $buildNode = function (int $userId) use (&$buildNode, &$visited, &$building, $nodes, $childIdsByParent): ?array {
            if (!isset($nodes[$userId]) || isset($building[$userId])) {
                return null;
            }
            $building[$userId] = true;
            $node = $nodes[$userId];
            foreach ($childIdsByParent[$userId] ?? [] as $childId) {
                $child = $buildNode($childId);
                if ($child !== null) {
                    $node['children'][] = $child;
                }
            }
            unset($building[$userId]);
            $visited[$userId] = true;

            return $node;
        };

        $roots = [];
        foreach ($accounts as $account) {
            $userId = (int) $account->user_id;
            $parentId = (int) $account->parent_user_id;
            if ($parentId === 0 || !isset($nodes[$parentId])) {
                $root = $buildNode($userId);
                if ($root !== null) {
                    $roots[] = $root;
                }
            }
        }

        foreach (array_keys($nodes) as $userId) {
            if (!isset($visited[$userId])) {
                $root = $buildNode($userId);
                if ($root !== null) {
                    $roots[] = $root;
                }
            }
        }

        return $roots;
    }
}
