<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EndCustomerDeviceControlTest extends TestCase
{
    public function test_customer_can_queue_arm_and_disarm_after_device_acknowledges_each_change(): void
    {
        DB::beginTransaction();

        try {
            [$customer, $deviceId] = $this->createCustomerAndDevice();

            $this->actingAs($customer)
                ->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('MONITORING STATUS')
                ->assertSee('Arm shutter')
                ->assertSee('Disarm shutter')
                ->assertSee('aria-pressed="false"', false)
                ->assertSee('aria-pressed="true"', false)
                ->assertDontSee('aria-pressed="false" disabled', false)
                ->assertDontSee('aria-pressed="true" disabled', false);

            $this->post(route('portal.devices.monitoring-state', $deviceId), [
                'state' => 'ARMED',
            ])->assertRedirect(route('portal.dashboard'))
                ->assertSessionHas('status');

            $configuration = DB::table('device_configurations')->where('device_id', $deviceId)->first();
            $command = DB::table('device_commands')->where('device_id', $deviceId)->first();
            $this->assertSame('ARMED', $configuration->manual_override);
            $this->assertSame('pending', $command->status);
            $this->assertSame('SYNC_CONFIG', $command->command_type);
            $this->assertSame($customer->user_id, (int) $command->requested_by);
            $this->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('REQUEST IN PROGRESS')
                ->assertSee('Waiting for your device to confirm this change.')
                ->assertSee('aria-pressed="true" disabled', false)
                ->assertDontSee('aria-pressed="false" disabled', false);

            $commandId = $command->command_id;
            $this->post(route('portal.devices.monitoring-state', $deviceId), [
                'state' => 'DISARMED',
            ])->assertRedirect(route('portal.dashboard'))
                ->assertSessionHas('status');

            $this->assertDatabaseHas('device_commands', [
                'command_id' => $commandId,
                'status' => 'failed',
                'error_message' => 'Superseded by a newer monitoring-state request.',
            ]);
            $newCommandId = DB::table('device_commands')
                ->where('device_id', $deviceId)
                ->where('status', 'pending')
                ->value('command_id');

            DB::table('device_commands')->where('command_id', $newCommandId)->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
            ]);

            $this->post(route('portal.devices.monitoring-state', $deviceId), [
                'state' => 'ARMED',
            ])->assertRedirect(route('portal.dashboard'))
                ->assertSessionHas('status');

            $this->assertSame(3, DB::table('device_commands')->where('device_id', $deviceId)->count());
            $this->assertSame(
                'ARMED',
                DB::table('device_configurations')
                    ->where('device_id', $deviceId)
                    ->orderByDesc('config_version')
                    ->value('manual_override'),
            );
        } finally {
            DB::rollBack();
        }
    }

    public function test_customer_can_arm_when_admin_disarm_command_is_pending(): void
    {
        DB::beginTransaction();

        try {
            [$customer, $deviceId] = $this->createCustomerAndDevice();
            $adminId = DB::table('device_assignments')
                ->where('device_id', $deviceId)
                ->value('assigned_by');
            $now = now();
            DB::table('device_configurations')->insert([
                'device_id' => $deviceId,
                'monitoring_mode' => 'DISARMED',
                'manual_override' => null,
                'schedule_enabled' => false,
                'timezone' => 'Asia/Kolkata',
                'config_version' => 1,
                'hooter_enabled' => true,
                'alert_on_open' => true,
                'alert_on_close' => false,
                'effective_from' => $now,
                'updated_by' => $adminId,
                'updated_at' => $now,
                'config_payload' => '{}',
            ]);
            $adminCommandId = DB::table('device_commands')->insertGetId([
                'device_id' => $deviceId,
                'command_type' => 'SYNC_CONFIG',
                'requested_payload' => '{}',
                'status' => 'pending',
                'requested_by' => $adminId,
                'requested_at' => $now,
                'expires_at' => $now->copy()->addMinutes(5),
            ], 'command_id');

            $this->actingAs($customer)
                ->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('REQUEST IN PROGRESS')
                ->assertSee('aria-pressed="false" >', false)
                ->assertSee('aria-pressed="true" disabled', false);

            $this->post(route('portal.devices.monitoring-state', $deviceId), [
                'state' => 'ARMED',
            ])->assertRedirect(route('portal.dashboard'))
                ->assertSessionHas('status');

            $this->assertDatabaseHas('device_commands', [
                'command_id' => $adminCommandId,
                'status' => 'failed',
                'error_message' => 'Superseded by a newer monitoring-state request.',
            ]);
            $this->assertSame(
                'ARMED',
                DB::table('device_configurations')
                    ->where('device_id', $deviceId)
                    ->orderByDesc('config_version')
                    ->value('manual_override'),
            );
        } finally {
            DB::rollBack();
        }
    }

    public function test_customer_cannot_control_a_device_outside_their_assignment(): void
    {
        DB::beginTransaction();

        try {
            [$customer] = $this->createCustomerAndDevice();
            $otherDeviceId = DB::table('devices')->insertGetId([
                'device_uid' => 'UNASSIGNED-'.Str::upper(Str::random(8)),
                'lifecycle_status' => 'active',
                'connectivity_status' => 'online',
            ], 'device_id');

            $this->actingAs($customer)
                ->post(route('portal.devices.monitoring-state', $otherDeviceId), [
                    'state' => 'ARMED',
                ])
                ->assertNotFound();

            $this->assertDatabaseMissing('device_commands', ['device_id' => $otherDeviceId]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_stale_pending_commands_do_not_block_a_new_arm_request(): void
    {
        DB::beginTransaction();

        try {
            [$customer, $deviceId] = $this->createCustomerAndDevice();
            DB::table('device_commands')->insert([
                'device_id' => $deviceId,
                'command_type' => 'SYNC_CONFIG',
                'requested_payload' => '{}',
                'status' => 'pending',
                'requested_by' => $customer->user_id,
                'requested_at' => now()->subMinutes(30),
            ]);

            $this->actingAs($customer)
                ->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('MONITORING STATUS')
                ->assertDontSee('REQUEST IN PROGRESS');

            $this->post(route('portal.devices.monitoring-state', $deviceId), [
                'state' => 'ARMED',
            ])->assertRedirect(route('portal.dashboard'))
                ->assertSessionHas('status');

            $this->assertDatabaseHas('device_commands', [
                'device_id' => $deviceId,
                'status' => 'failed',
                'error_message' => 'Command expired before the device acknowledged it.',
            ]);
            $this->assertSame(2, DB::table('device_commands')->where('device_id', $deviceId)->count());
        } finally {
            DB::rollBack();
        }
    }

    private function createCustomerAndDevice(): array
    {
        $suffix = Str::lower(Str::random(10));
        $companyId = DB::table('companies')->insertGetId([
            'company_code' => 'CUSTOMER_'.$suffix,
            'company_name' => 'Customer Control Test',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'company_id');
        $customer = User::create([
            'company_id' => $companyId,
            'role_id' => DB::table('roles')->where('role_code', 'END_CUSTOMER')->value('role_id'),
            'full_name' => 'Control Customer',
            'email' => 'control-'.$suffix.'@example.test',
            'password_hash' => Hash::make('ShopGuardTest!123'),
            'status' => 'active',
        ]);
        $admin = User::create([
            'company_id' => $companyId,
            'role_id' => DB::table('roles')->where('role_code', 'COMPANY_ADMIN')->value('role_id'),
            'full_name' => 'Control Admin',
            'email' => 'admin-'.$suffix.'@example.test',
            'password_hash' => Hash::make('ShopGuardTest!123'),
            'status' => 'active',
        ]);
        $deviceId = DB::table('devices')->insertGetId([
            'device_uid' => 'CONTROL-'.Str::upper($suffix),
            'serial_number' => 'SERIAL-'.Str::upper($suffix),
            'company_id' => $companyId,
            'lifecycle_status' => 'active',
            'connectivity_status' => 'online',
        ], 'device_id');
        DB::table('device_assignments')->insert([
            'device_id' => $deviceId,
            'owner_user_id' => $customer->user_id,
            'assigned_by' => $admin->user_id,
            'assigned_at' => now(),
            'is_current' => true,
        ]);

        return [$customer, $deviceId];
    }
}
