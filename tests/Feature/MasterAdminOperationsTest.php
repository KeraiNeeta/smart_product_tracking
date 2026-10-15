<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterAdminOperationsTest extends TestCase
{
    public function test_super_admin_can_record_device_transfers_and_versioned_monitoring_changes(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::with('role')->where('email', 'admin@example.com')->firstOrFail();
            $this->actingAs($admin);
            $suffix = Str::lower(Str::random(12));

            $this->post(route('master-admin.companies.store'), [
                'company_code' => 'TEST_'.$suffix,
                'company_name' => 'Temporary Test Company',
                'status' => 'active',
            ])->assertRedirect(route('master-admin.companies.index'));

            $companyId = DB::table('companies')->where('company_code', 'TEST_'.$suffix)->value('company_id');
            $roleId = DB::table('roles')->where('role_code', 'COMPANY_ADMIN')->value('role_id');
            $owner = User::create([
                'company_id' => $companyId,
                'role_id' => $roleId,
                'full_name' => 'Temporary Device Owner',
                'email' => 'owner-'.$suffix.'@example.test',
                'password_hash' => Hash::make(Str::random(30)),
                'status' => 'active',
            ]);
            $customerRoleId = DB::table('roles')->where('role_code', 'END_CUSTOMER')->value('role_id');
            $customer = User::create([
                'company_id' => $companyId,
                'role_id' => $customerRoleId,
                'full_name' => 'Temporary Shop Customer',
                'email' => 'customer-'.$suffix.'@example.test',
                'password_hash' => Hash::make(Str::random(30)),
                'status' => 'active',
            ]);
            foreach ([$owner, $customer] as $user) {
                DB::table('push_tokens')->insert([
                    'user_id' => $user->user_id,
                    'platform' => 'ANDROID',
                    'token' => 'operation-test-'.$suffix.'-'.$user->user_id,
                    'is_active' => true,
                    'last_seen_at' => now(),
                ]);
            }

            $shopCode = 'SHOP_'.$suffix;
            $this->post(route('master-admin.shops.store'), [
                'company_id' => $companyId,
                'customer_user_id' => $customer->user_id,
                'shop_name' => 'Temporary Test Shop',
                'shop_code' => $shopCode,
                'timezone' => 'Asia/Kolkata',
                'status' => 'active',
            ])->assertRedirect(route('master-admin.shops.index'));

            $shopId = DB::table('shops')->where('shop_code', $shopCode)->value('shop_id');
            $this->post(route('master-admin.notification-rules.store'), [
                'company_id' => $companyId,
                'alert_type' => 'SHUTTER_OPEN',
                'channel' => 'EMAIL',
                'priority' => 100,
                'is_enabled' => '1',
            ])->assertRedirect(route('master-admin.notification-rules.index'));
            foreach (['ARMED', 'DISARMED'] as $stateEvent) {
                foreach (['EMAIL', 'PUSH'] as $channel) {
                    $this->post(route('master-admin.notification-rules.store'), [
                        'company_id' => $companyId,
                        'alert_type' => $stateEvent,
                        'channel' => $channel,
                        'priority' => 100,
                        'is_enabled' => '1',
                    ])->assertRedirect(route('master-admin.notification-rules.index'));
                }
            }

            $deviceUid = 'test-'.$suffix;
            $this->post(route('master-admin.devices.store'), [
                'device_uid' => $deviceUid,
                'company_id' => $companyId,
                'lifecycle_status' => 'in_stock',
            ])->assertRedirect(route('master-admin.devices.index'));

            $deviceId = DB::table('devices')->where('device_uid', $deviceUid)->value('device_id');
            $this->post(route('master-admin.devices.transfer', $deviceId), [
                'owner_user_id' => $owner->user_id,
                'shop_id' => $shopId,
                'reference_no' => 'TEST-'.$suffix,
                'notes' => 'Feature test transfer',
            ])->assertRedirect(route('master-admin.devices.index'));

            $this->assertDatabaseHas('device_assignments', [
                'device_id' => $deviceId,
                'owner_user_id' => $owner->user_id,
                'is_current' => true,
            ]);
            $this->assertDatabaseHas('device_inventory_transactions', [
                'device_id' => $deviceId,
                'transaction_type' => 'TRANSFER',
                'performed_by' => $admin->user_id,
            ]);

            $this->put(route('master-admin.devices.update', $deviceId), [
                'lifecycle_status' => 'maintenance',
            ])->assertRedirect(route('master-admin.devices.index'));

            $this->assertDatabaseHas('devices', [
                'device_id' => $deviceId,
                'lifecycle_status' => 'maintenance',
            ]);
            $this->assertDatabaseHas('device_assignments', [
                'device_id' => $deviceId,
                'owner_user_id' => $owner->user_id,
                'is_current' => false,
            ]);
            $this->assertDatabaseHas('device_inventory_transactions', [
                'device_id' => $deviceId,
                'transaction_type' => 'SEND_TO_REPAIR',
                'from_user_id' => $owner->user_id,
                'performed_by' => $admin->user_id,
            ]);

            $this->post(route('master-admin.configurations.store'), [
                'device_id' => $deviceId,
                'monitoring_mode' => 'ARMED',
                'manual_override' => 'ARMED',
                'timezone' => 'Asia/Kolkata',
                'effective_from' => now()->format('Y-m-d\TH:i'),
                'hooter_enabled' => '1',
                'alert_on_open' => '1',
                'config_payload' => '{}',
            ])->assertRedirect(route('master-admin.configurations.index'));

            $this->assertDatabaseHas('device_configurations', [
                'device_id' => $deviceId,
                'config_version' => 1,
                'monitoring_mode' => 'ARMED',
            ]);
            $this->assertDatabaseHas('device_commands', [
                'device_id' => $deviceId,
                'command_type' => 'SYNC_CONFIG',
                'status' => 'pending',
            ]);
            $armedAlert = DB::table('alerts')
                ->where('device_id', $deviceId)
                ->where('alert_type', 'ARMED')
                ->first();
            $this->assertNotNull($armedAlert);
            $this->assertSame(4, DB::table('notification_jobs')->where('alert_id', $armedAlert->alert_id)->count());
            $this->assertDatabaseHas('notification_jobs', [
                'alert_id' => $armedAlert->alert_id,
                'channel' => 'EMAIL',
                'recipient' => $owner->email,
                'status' => 'queued',
            ]);
            $this->assertDatabaseHas('notification_jobs', [
                'alert_id' => $armedAlert->alert_id,
                'channel' => 'EMAIL',
                'recipient' => $customer->email,
                'status' => 'queued',
            ]);
            $this->assertDatabaseHas('notification_jobs', [
                'alert_id' => $armedAlert->alert_id,
                'channel' => 'PUSH',
                'recipient' => 'operation-test-'.$suffix.'-'.$owner->user_id,
                'status' => 'queued',
            ]);

            $this->post(route('master-admin.configurations.store'), [
                'device_id' => $deviceId,
                'monitoring_mode' => 'ARMED',
                'manual_override' => 'ARMED',
                'timezone' => 'Asia/Kolkata',
                'effective_from' => now()->format('Y-m-d\TH:i'),
                'hooter_enabled' => '1',
                'alert_on_open' => '1',
                'config_payload' => '{}',
            ])->assertRedirect(route('master-admin.configurations.index'));
            $this->assertSame(
                1,
                DB::table('alerts')->where('device_id', $deviceId)->where('alert_type', 'ARMED')->count(),
            );
            $this->assertSame(
                4,
                DB::table('notification_jobs')->where('alert_id', $armedAlert->alert_id)->count(),
            );

            $this->post(route('master-admin.configurations.store'), [
                'device_id' => $deviceId,
                'monitoring_mode' => 'DISARMED',
                'timezone' => 'Asia/Kolkata',
                'effective_from' => now()->format('Y-m-d\TH:i'),
                'hooter_enabled' => '1',
                'alert_on_open' => '1',
                'config_payload' => '{}',
            ])->assertRedirect(route('master-admin.configurations.index'));
            $disarmedAlert = DB::table('alerts')
                ->where('device_id', $deviceId)
                ->where('alert_type', 'DISARMED')
                ->first();
            $this->assertNotNull($disarmedAlert);
            $this->assertSame(4, DB::table('notification_jobs')->where('alert_id', $disarmedAlert->alert_id)->count());

            $this->post(route('master-admin.schedules.store'), [
                'device_id' => $deviceId,
                'schedule_name' => 'Overnight test',
                'start_time' => '21:00',
                'end_time' => '07:00',
                'weekdays' => [1, 2, 3, 4, 5],
                'timezone' => 'Asia/Kolkata',
                'priority' => 100,
                'is_enabled' => '1',
            ])->assertRedirect(route('master-admin.schedules.index'));

            $this->assertDatabaseHas('device_schedules', [
                'device_id' => $deviceId,
                'schedule_name' => 'Overnight test',
                'start_time' => '21:00:00',
                'end_time' => '07:00:00',
            ]);

            $alertId = DB::table('alerts')->insertGetId([
                'device_id' => $deviceId,
                'shop_id' => $shopId,
                'alert_type' => 'SHUTTER_OPEN',
                'title' => 'Temporary test alert',
                'message' => 'Feature test alert',
                'status' => 'open',
                'triggered_at' => now(),
            ], 'alert_id');
            $this->post(route('master-admin.alerts.acknowledge', $alertId))
                ->assertRedirect();
            $this->assertDatabaseHas('alerts', [
                'alert_id' => $alertId,
                'status' => 'acknowledged',
                'acknowledged_by' => $admin->user_id,
            ]);

            $this->assertDatabaseHas('notification_rules', [
                'company_id' => $companyId,
                'alert_type' => 'SHUTTER_OPEN',
                'channel' => 'EMAIL',
            ]);
            $this->assertDatabaseHas('audit_logs', [
                'actor_user_id' => $admin->user_id,
                'entity_type' => 'device',
                'action' => 'transferred',
            ]);
        } finally {
            DB::rollBack();
        }
    }
}
