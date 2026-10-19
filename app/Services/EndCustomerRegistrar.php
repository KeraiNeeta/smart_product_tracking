<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EndCustomerRegistrar
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $companyId = app(DefaultCompany::class)->requireId();
            $device = DB::table('devices')
                ->where('serial_number', $data['serial_number'])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$device || $device->lifecycle_status !== 'in_stock') {
                throw ValidationException::withMessages([
                    'serial_number' => 'This serial number is invalid or the device is not available for registration.',
                ]);
            }
            if ($device->company_id !== null && (int) $device->company_id !== $companyId) {
                throw ValidationException::withMessages([
                    'serial_number' => 'This serial number is invalid or the device is not available for registration.',
                ]);
            }
            if (
                DB::table('device_assignments')
                    ->where('device_id', $device->device_id)
                    ->where('is_current', true)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'serial_number' => 'This device is already assigned to an account.',
                ]);
            }

            $role = Role::where('role_code', 'END_CUSTOMER')->firstOrFail();
            $user = User::create([
                'company_id' => $companyId,
                'role_id' => $role->role_id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password_hash' => Hash::make($data['password']),
                'status' => 'active',
            ]);
            $now = now();
            DB::table('device_assignments')->insert([
                'device_id' => $device->device_id,
                'owner_user_id' => $user->user_id,
                'assigned_by' => $user->user_id,
                'assigned_at' => $now,
                'is_current' => true,
                'notes' => 'Claimed during End Customer registration using device serial number.',
            ]);
            DB::table('devices')->where('device_id', $device->device_id)->update([
                'company_id' => $companyId,
                'lifecycle_status' => 'assigned',
                'updated_at' => $now,
            ]);
            DB::table('device_inventory_transactions')->insert([
                'device_id' => $device->device_id,
                'transaction_type' => 'TRANSFER',
                'to_user_id' => $user->user_id,
                'from_company_id' => $companyId,
                'to_company_id' => $companyId,
                'notes' => 'Device claimed during End Customer registration.',
                'performed_by' => $user->user_id,
                'performed_at' => $now,
                'metadata' => json_encode(['serial_number' => $device->serial_number], JSON_THROW_ON_ERROR),
            ]);

            return $user;
        });
    }
}
