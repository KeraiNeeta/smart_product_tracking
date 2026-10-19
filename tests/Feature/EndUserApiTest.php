<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EndUserApiTest extends TestCase
{
    public function test_end_user_can_register_login_view_dashboard_control_device_and_logout(): void
    {
        DB::beginTransaction();

        try {
            $suffix = Str::lower(Str::random(10));
            $serialNumber = 'API-SERIAL-'.Str::upper($suffix);
            DB::table('devices')->insert([
                'device_uid' => 'API-DEVICE-'.Str::upper($suffix),
                'serial_number' => $serialNumber,
                'lifecycle_status' => 'in_stock',
                'connectivity_status' => 'online',
            ]);

            $email = 'api-user-'.$suffix.'@example.test';
            $credentials = [
                'full_name' => 'API End User',
                'email' => $email,
                'mobile' => '+1555'.random_int(1000000000, 9999999999),
                'serial_number' => $serialNumber,
                'password' => 'ShopGuardApi!123',
                'password_confirmation' => 'ShopGuardApi!123',
                'device_name' => 'android-test',
            ];
            $registration = $this->postJson('/api/v1/end-user/register', $credentials)
                ->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.user.role', 'END_CUSTOMER')
                ->assertJsonPath('data.token_type', 'Bearer');
            $token = $registration->json('data.token');
            $this->assertNotEmpty($token);

            $headers = ['Authorization' => 'Bearer '.$token];
            $this->getJson('/api/v1/end-user/dashboard', $headers)
                ->assertOk()
                ->assertJsonPath('data.device.serial_number', $serialNumber)
                ->assertJsonPath('data.device.monitoring_state', 'DISARMED');

            $this->postJson('/api/v1/end-user/device/monitoring-state', ['state' => 'ARMED'], $headers)
                ->assertStatus(202)
                ->assertJsonPath('data.requested_state', 'ARMED')
                ->assertJsonPath('data.command_status', 'pending');
            $commandId = DB::table('device_commands')
                ->where('requested_by', $registration->json('data.user.user_id'))
                ->where('status', 'pending')
                ->value('command_id');
            DB::table('device_commands')->where('command_id', $commandId)->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
            ]);

            $this->postJson('/api/v1/end-user/device/monitoring-state', ['state' => 'DISARMED'], $headers)
                ->assertStatus(202)
                ->assertJsonPath('data.requested_state', 'DISARMED');

            $this->getJson('/api/v1/end-user/device', $headers)
                ->assertOk()
                ->assertJsonPath('data.device.monitoring_state', 'DISARMED');
            $this->getJson('/api/v1/end-user/notifications', $headers)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonStructure(['data' => ['unread_count', 'notifications']]);
            $this->getJson('/api/v1/end-user/profile', $headers)
                ->assertOk()
                ->assertJsonPath('data.user.email', $email);
            $this->patchJson('/api/v1/end-user/profile', [
                'full_name' => 'Updated API User',
                'email' => $email,
                'mobile' => '+15551234567',
            ], $headers)
                ->assertOk()
                ->assertJsonPath('data.user.full_name', 'Updated API User')
                ->assertJsonPath('data.user.mobile', '+15551234567');
            $pushToken = 'api-push-token-'.Str::random(20);
            $this->postJson('/api/v1/end-user/push-token', [
                'platform' => 'ANDROID',
                'token' => $pushToken,
            ], $headers)->assertCreated();
            $this->assertDatabaseHas('push_tokens', [
                'token' => $pushToken,
                'user_id' => $registration->json('data.user.user_id'),
                'is_active' => true,
            ]);
            $this->deleteJson('/api/v1/end-user/push-token', ['token' => $pushToken], $headers)
                ->assertOk();
            $this->assertDatabaseHas('push_tokens', ['token' => $pushToken, 'is_active' => false]);

            $this->postJson('/api/v1/end-user/logout', [], $headers)
                ->assertOk()
                ->assertJsonPath('success', true);
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_id' => $registration->json('data.user.user_id'),
                'name' => 'android-test',
            ]);
            auth()->forgetGuards();
            $this->getJson('/api/v1/end-user/dashboard', $headers)->assertUnauthorized();

            $login = $this->postJson('/api/v1/end-user/login', [
                'email' => $email,
                'password' => $credentials['password'],
                'device_name' => 'android-login-test',
            ])->assertOk()
                ->assertJsonPath('data.user.role', 'END_CUSTOMER')
                ->assertJsonPath('data.token_type', 'Bearer');
            $loginHeaders = ['Authorization' => 'Bearer '.$login->json('data.token')];
            $this->putJson('/api/v1/end-user/profile/password', [
                'current_password' => $credentials['password'],
                'new_password' => 'ShopGuardApi!456',
                'new_password_confirmation' => 'ShopGuardApi!456',
            ], $loginHeaders)
                ->assertOk()
                ->assertJsonPath('success', true);
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_id' => $registration->json('data.user.user_id'),
                'name' => 'android-login-test',
            ]);
            auth()->forgetGuards();
            $this->getJson('/api/v1/end-user/dashboard', $loginHeaders)->assertUnauthorized();
            $this->postJson('/api/v1/end-user/login', [
                'email' => $email,
                'password' => 'ShopGuardApi!456',
            ])->assertOk();
        } finally {
            DB::rollBack();
        }
    }

    public function test_login_rejects_non_end_user_accounts(): void
    {
        DB::beginTransaction();

        try {
            $roleId = DB::table('roles')->where('role_code', 'DEALER')->value('role_id');
            $email = 'api-dealer-'.Str::lower(Str::random(10)).'@example.test';
            DB::table('users')->insert([
                'role_id' => $roleId,
                'full_name' => 'API Dealer',
                'email' => $email,
                'password_hash' => \Illuminate\Support\Facades\Hash::make('ShopGuardApi!123'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->postJson('/api/v1/end-user/login', [
                'email' => $email,
                'password' => 'ShopGuardApi!123',
            ])->assertUnauthorized();
        } finally {
            DB::rollBack();
        }
    }
}
