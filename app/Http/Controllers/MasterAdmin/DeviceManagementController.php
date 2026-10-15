<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeviceManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $devices = DB::table('devices')
            ->leftJoin('companies', 'companies.company_id', '=', 'devices.company_id')
            ->leftJoin('device_assignments', function ($join) {
                $join->on('device_assignments.device_id', '=', 'devices.device_id')
                    ->where('device_assignments.is_current', true);
            })
            ->leftJoin('users as owners', 'owners.user_id', '=', 'device_assignments.owner_user_id')
            ->leftJoin('shops', 'shops.shop_id', '=', 'device_assignments.shop_id')
            ->whereNull('devices.deleted_at')
            ->select('devices.*', 'companies.company_name', 'owners.full_name as owner_name', 'shops.shop_name');
        TableSearch::apply($devices, $search, [
            'devices.device_uid', 'devices.serial_number', 'devices.model',
            'companies.company_name', 'owners.full_name', 'shops.shop_name',
            'devices.lifecycle_status', 'devices.connectivity_status',
        ]);

        return view('master-admin.devices.index', [
            'devices' => $devices->orderBy('devices.device_uid')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('master-admin.devices.form-page', [
            'device' => null,
            'companies' => DB::table('companies')->orderBy('company_name')->get(),
        ]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $deviceId = DB::transaction(function () use ($request, $data, $audit): int {
            $deviceId = DB::table('devices')->insertGetId($data, 'device_id');
            $audit->record($request, 'registered', 'device', $deviceId, after: $data, companyId: $data['company_id'] ?? null);

            return $deviceId;
        });

        return redirect()->route('master-admin.devices.index')
            ->with('status', 'Device registered successfully.');
    }

    public function edit(int $device): View
    {
        return view('master-admin.devices.form-page', [
            'device' => DB::table('devices')->whereNull('deleted_at')->where('device_id', $device)->firstOrFail(),
            'companies' => DB::table('companies')->orderBy('company_name')->get(),
        ]);
    }

    public function update(Request $request, int $device, AdminAuditLogger $audit): RedirectResponse
    {
        DB::table('devices')->whereNull('deleted_at')->where('device_id', $device)->firstOrFail();
        $data = $this->validated($request, $device);
        unset($data['company_id']);
        $newLifecycleStatus = $data['lifecycle_status'];
        unset($data['lifecycle_status']);
        $data['updated_at'] = now();

        DB::transaction(function () use ($request, $device, $data, $newLifecycleStatus, $audit): void {
            $lockedDevice = DB::table('devices')
                ->whereNull('deleted_at')
                ->where('device_id', $device)
                ->lockForUpdate()
                ->firstOrFail();
            if (
                in_array($newLifecycleStatus, ['assigned', 'active'], true)
                && $newLifecycleStatus !== $lockedDevice->lifecycle_status
            ) {
                throw ValidationException::withMessages([
                    'lifecycle_status' => 'Assigned and installed statuses are set through the transfer workflow.',
                ]);
            }

            $statusChanged = $newLifecycleStatus !== $lockedDevice->lifecycle_status;
            $before = (array) $lockedDevice;

            if ($statusChanged) {
                $assignment = DB::table('device_assignments')
                    ->where('device_id', $device)
                    ->where('is_current', true)
                    ->lockForUpdate()
                    ->first();
                if ($assignment) {
                    DB::table('device_assignments')
                        ->where('assignment_id', $assignment->assignment_id)
                        ->update(['is_current' => false, 'unassigned_at' => now()]);
                }

                $transactionType = match ($newLifecycleStatus) {
                    'in_stock' => 'RETURN_TO_STOCK',
                    'maintenance' => 'SEND_TO_REPAIR',
                    'retired' => 'RETIRE',
                    'lost' => 'CORRECTION',
                    default => throw ValidationException::withMessages([
                        'lifecycle_status' => 'Choose a valid inventory status transition.',
                    ]),
                };

                DB::table('device_inventory_transactions')->insert([
                    'device_id' => $device,
                    'transaction_type' => $transactionType,
                    'from_user_id' => $assignment?->owner_user_id,
                    'to_user_id' => null,
                    'from_company_id' => $lockedDevice->company_id,
                    'to_company_id' => $lockedDevice->company_id,
                    'notes' => 'Lifecycle changed from '.$lockedDevice->lifecycle_status.' to '.$newLifecycleStatus,
                    'performed_by' => $request->user()->getAuthIdentifier(),
                    'performed_at' => now(),
                    'metadata' => json_encode([
                        'from_status' => $lockedDevice->lifecycle_status,
                        'to_status' => $newLifecycleStatus,
                    ], JSON_THROW_ON_ERROR),
                ]);
            }

            $data['lifecycle_status'] = $newLifecycleStatus;
            DB::table('devices')->where('device_id', $device)->update($data);
            $audit->record($request, 'updated', 'device', $device, $before, $data, $lockedDevice->company_id);
        });

        return redirect()->route('master-admin.devices.index')
            ->with('status', 'Device updated successfully.');
    }

    public function transferForm(int $device): View
    {
        $deviceRecord = DB::table('devices')->whereNull('deleted_at')->where('device_id', $device)->firstOrFail();

        return view('master-admin.devices.transfer', [
            'device' => $deviceRecord,
            'currentAssignment' => DB::table('device_assignments')
                ->leftJoin('users', 'users.user_id', '=', 'device_assignments.owner_user_id')
                ->leftJoin('shops', 'shops.shop_id', '=', 'device_assignments.shop_id')
                ->where('device_assignments.device_id', $device)
                ->where('device_assignments.is_current', true)
                ->select('device_assignments.*', 'users.full_name as owner_name', 'shops.shop_name')
                ->first(),
            'owners' => DB::table('users')
                ->join('roles', 'roles.role_id', '=', 'users.role_id')
                ->where('users.status', 'active')
                ->whereNull('users.deleted_at')
                ->where('roles.role_code', '!=', 'SUPER_ADMIN')
                ->orderBy('users.full_name')
                ->select('users.user_id', 'users.full_name', 'users.company_id', 'roles.role_name')
                ->get(),
            'shops' => DB::table('shops')->where('status', 'active')->orderBy('shop_name')->get(),
        ]);
    }

    public function transfer(Request $request, int $device, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'owner_user_id' => ['required', 'integer', Rule::exists('users', 'user_id')->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at'))],
            'shop_id' => ['nullable', 'integer', 'exists:shops,shop_id'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $owner = DB::table('users')->where('user_id', $data['owner_user_id'])->firstOrFail();
        $shop = isset($data['shop_id'])
            ? DB::table('shops')->where('shop_id', $data['shop_id'])->firstOrFail()
            : null;

        if ($shop && (int) ($owner->company_id ?? 0) !== (int) ($shop->company_id ?? 0)) {
            throw ValidationException::withMessages([
                'shop_id' => 'The selected shop must belong to the device owner company.',
            ]);
        }

        DB::transaction(function () use ($request, $device, $data, $owner, $shop, $audit): void {
            $deviceRecord = DB::table('devices')->whereNull('deleted_at')->where('device_id', $device)->lockForUpdate()->firstOrFail();
            $current = DB::table('device_assignments')
                ->where('device_id', $device)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($current) {
                DB::table('device_assignments')
                    ->where('assignment_id', $current->assignment_id)
                    ->update(['is_current' => false, 'unassigned_at' => now()]);
            }

            DB::table('device_assignments')->insert([
                'device_id' => $device,
                'owner_user_id' => $owner->user_id,
                'shop_id' => $shop?->shop_id,
                'assigned_by' => $request->user()->getAuthIdentifier(),
                'assigned_at' => now(),
                'is_current' => true,
                'notes' => $data['notes'] ?? null,
            ]);

            DB::table('device_inventory_transactions')->insert([
                'device_id' => $device,
                'transaction_type' => 'TRANSFER',
                'from_user_id' => $current?->owner_user_id,
                'to_user_id' => $owner->user_id,
                'from_company_id' => $deviceRecord->company_id,
                'to_company_id' => $owner->company_id,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'performed_by' => $request->user()->getAuthIdentifier(),
                'performed_at' => now(),
                'metadata' => json_encode(['shop_id' => $shop?->shop_id], JSON_THROW_ON_ERROR),
            ]);

            DB::table('devices')->where('device_id', $device)->update([
                'company_id' => $owner->company_id,
                'lifecycle_status' => $shop ? 'active' : 'assigned',
                'updated_at' => now(),
            ]);

            $before = [
                'owner_user_id' => $current?->owner_user_id,
                'company_id' => $deviceRecord->company_id,
            ];
            $after = [
                'owner_user_id' => $owner->user_id,
                'company_id' => $owner->company_id,
                'shop_id' => $shop?->shop_id,
            ];
            $audit->record($request, 'transferred', 'device', $device, $before, $after, $owner->company_id);
        });

        return redirect()->route('master-admin.devices.index')
            ->with('status', 'Device transfer recorded successfully.');
    }

    private function validated(Request $request, ?int $deviceId = null): array
    {
        $rules = [
            'serial_number' => ['nullable', 'string', 'max:120', Rule::unique('devices', 'serial_number')->ignore($deviceId, 'device_id')],
            'model' => ['nullable', 'string', 'max:100'],
            'firmware_version' => ['nullable', 'string', 'max:100'],
            'company_id' => ['nullable', 'integer', 'exists:companies,company_id'],
            'manufacturing_batch' => ['nullable', 'string', 'max:100'],
            'lifecycle_status' => ['required', Rule::in($deviceId === null
                ? ['in_stock']
                : ['in_stock', 'assigned', 'active', 'maintenance', 'lost', 'retired'])],
        ];

        if ($deviceId === null) {
            $rules['device_uid'] = ['required', 'string', 'max:120', 'unique:devices,device_uid'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }
}
