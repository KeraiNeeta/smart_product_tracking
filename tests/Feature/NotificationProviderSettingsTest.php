<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationProviderSettingsTest extends TestCase
{
    public function test_super_admin_can_save_encrypted_twilio_settings_and_send_a_test_message(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::where('email', 'admin@example.com')->firstOrFail();
            $this->actingAs($admin);
            $secret = 'twilio-test-secret-'.bin2hex(random_bytes(8));
            $accountSid = 'AC'.strtoupper(bin2hex(random_bytes(16)));

            $this->put(route('master-admin.notification-providers.update', 'SMS'), [
                'settings' => [
                    'account_sid' => $accountSid,
                    'auth_token' => $secret,
                    'from_number' => '+15551234567',
                ],
                'is_enabled' => '1',
            ])->assertRedirect();

            $stored = DB::table('notification_provider_settings')->where('channel', 'SMS')->firstOrFail();
            $this->assertStringNotContainsString($secret, $stored->encrypted_settings);
            $decrypted = json_decode(Crypt::decryptString($stored->encrypted_settings), true);
            $this->assertSame($secret, $decrypted['auth_token']);

            $this->put(route('master-admin.notification-providers.update', 'SMS'), [
                'settings' => [
                    'account_sid' => $accountSid,
                    'auth_token' => '',
                    'from_number' => '+15551234567',
                ],
                'is_enabled' => '1',
            ])->assertRedirect();
            $stored = DB::table('notification_provider_settings')->where('channel', 'SMS')->firstOrFail();
            $this->assertSame(
                $secret,
                json_decode(Crypt::decryptString($stored->encrypted_settings), true)['auth_token'],
            );

            $response = $this->get(route('master-admin.notification-providers.index'));
            $response->assertOk()->assertDontSee($secret);

            Http::fake([
                'api.twilio.com/*' => Http::response(['sid' => 'SM-test'], 201),
            ]);
            $this->post(route('master-admin.notification-providers.test', 'SMS'), [
                'test_recipient' => '+15557654321',
            ])->assertRedirect()->assertSessionHas('status');

            Http::assertSent(fn ($request): bool => $request['To'] === '+15557654321'
                && $request['From'] === '+15551234567'
                && $request['Body'] === 'This is a test notification from Shop Guard.');

            DB::table('notification_jobs')->where('status', 'queued')->update([
                'next_attempt_at' => now()->addHour(),
            ]);
            $alertId = DB::table('alerts')->value('alert_id');
            $this->assertNotNull($alertId, 'A demo alert is required to exercise the notification worker.');
            $jobId = DB::table('notification_jobs')->insertGetId([
                'alert_id' => $alertId,
                'channel' => 'SMS',
                'recipient' => '+15557654321',
                'payload' => json_encode(['title' => 'Worker test', 'message' => 'Queued test delivery']),
                'status' => 'queued',
                'attempt_count' => 0,
                'next_attempt_at' => now()->subMinute(),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'notification_job_id');
            $this->artisan('notifications:process', ['--limit' => 1])->assertSuccessful();
            $this->assertDatabaseHas('notification_jobs', [
                'notification_job_id' => $jobId,
                'status' => 'sent',
                'attempt_count' => 1,
            ]);
            $this->assertDatabaseHas('notification_logs', [
                'notification_job_id' => $jobId,
                'status' => 'sent',
                'provider_name' => 'TWILIO',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_non_super_admin_cannot_open_provider_settings(): void
    {
        $user = User::where('email', 'shop-user@demo.shopguard.local')->firstOrFail();

        $this->actingAs($user)
            ->get(route('master-admin.notification-providers.index'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHasErrors('access');
    }
}
