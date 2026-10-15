<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use JsonException;
use RuntimeException;
use Throwable;

class NotificationDeliveryService
{
    public function __construct(private readonly NotificationProviderSettings $settings)
    {
    }

    public function sendTest(string $channel, string $recipient): void
    {
        $configuration = $this->configuration($channel);
        $this->send($channel, $recipient, [
            'title' => 'Shop Guard notification test',
            'message' => 'This is a test notification from Shop Guard.',
        ], $configuration);
    }

    public function deliver(int $notificationJobId): bool
    {
        $job = DB::table('notification_jobs')
            ->where('notification_job_id', $notificationJobId)
            ->lockForUpdate()
            ->first();
        if (!$job) {
            throw new RuntimeException('Notification job was not found.');
        }
        if (!in_array($job->status, ['queued', 'pending'], true)) {
            return false;
        }

        $attempt = (int) $job->attempt_count + 1;
        try {
            $configuration = $this->configuration($job->channel);
            $payload = is_string($job->payload)
                ? json_decode($job->payload, true, 512, JSON_THROW_ON_ERROR)
                : (array) $job->payload;

            $this->send($job->channel, $job->recipient, $payload, $configuration);
            DB::table('notification_jobs')->where('notification_job_id', $notificationJobId)->update([
                'status' => 'sent',
                'attempt_count' => $attempt,
                'updated_at' => now(),
            ]);
            $this->logAttempt($job, $attempt, 'sent', null);

            return true;
        } catch (Throwable $exception) {
            $status = $attempt >= 3 ? 'failed' : 'queued';
            DB::table('notification_jobs')->where('notification_job_id', $notificationJobId)->update([
                'status' => $status,
                'attempt_count' => $attempt,
                'next_attempt_at' => $status === 'queued' ? now()->addMinutes(5 * $attempt) : now(),
                'updated_at' => now(),
            ]);
            $this->logAttempt($job, $attempt, $status, $exception);

            return false;
        }
    }

    private function configuration(string $channel): array
    {
        $record = DB::table('notification_provider_settings')->where('channel', $channel)->first();
        if (!$record || !$record->is_enabled) {
            throw new RuntimeException('Notification channel '.$channel.' is not configured and enabled.');
        }

        try {
            $configuration = json_decode(Crypt::decryptString($record->encrypted_settings), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Stored notification provider settings are invalid.', previous: $exception);
        }
        if (!is_array($configuration)) {
            throw new RuntimeException('Stored notification provider settings are invalid.');
        }

        return $configuration;
    }

    private function send(string $channel, string $recipient, array $payload, array $configuration): void
    {
        $title = (string) ($payload['title'] ?? 'Shop Guard notification');
        $body = (string) ($payload['message'] ?? 'Shop Guard product update.');

        match ($channel) {
            'EMAIL' => $this->sendEmail($recipient, $title, $body, $configuration),
            'SMS' => $this->sendSms($recipient, $body, $configuration),
            'WHATSAPP' => $this->sendWhatsApp($recipient, $body, $configuration),
            'PUSH' => $this->sendPush($recipient, $title, $body, $configuration),
            default => throw new RuntimeException('Unsupported notification channel: '.$channel),
        };
    }

    private function sendEmail(string $recipient, string $title, string $body, array $configuration): void
    {
        Mail::build([
            'transport' => 'smtp',
            'host' => $configuration['host'],
            'port' => (int) $configuration['port'],
            'scheme' => match ($configuration['encryption'] ?? 'tls') {
                'ssl' => 'smtps',
                'none' => null,
                default => 'smtp',
            },
            'username' => $configuration['username'] ?? null,
            'password' => $configuration['password'] ?? null,
            'timeout' => 15,
        ])->raw($body, function ($message) use ($recipient, $title, $configuration): void {
            $message->to($recipient)
                ->subject($title)
                ->from($configuration['from_address'], $configuration['from_name'] ?? 'Shop Guard');
        });
    }

    private function sendSms(string $recipient, string $body, array $configuration): void
    {
        $response = Http::asForm()
            ->withBasicAuth($configuration['account_sid'], $configuration['auth_token'])
            ->timeout(20)
            ->post(
                'https://api.twilio.com/2010-04-01/Accounts/'.$configuration['account_sid'].'/Messages.json',
                ['To' => $recipient, 'From' => $configuration['from_number'], 'Body' => $body],
            );
        $response->throw();
    }

    private function sendWhatsApp(string $recipient, string $body, array $configuration): void
    {
        $version = $configuration['graph_version'] ?? 'v21.0';
        if (!preg_match('/^v\d+\.\d+$/', $version)) {
            throw new RuntimeException('Meta Graph API version must look like v21.0.');
        }

        Http::withToken($configuration['access_token'])
            ->timeout(20)
            ->post(
                'https://graph.facebook.com/'.$version.'/'.$configuration['phone_number_id'].'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => ['preview_url' => false, 'body' => $body],
                ],
            )
            ->throw();
    }

    private function sendPush(string $recipient, string $title, string $body, array $configuration): void
    {
        $serviceAccount = json_decode($configuration['service_account_json'], true, 512, JSON_THROW_ON_ERROR);
        if (
            !is_array($serviceAccount)
            || ($serviceAccount['type'] ?? null) !== 'service_account'
            || ($serviceAccount['project_id'] ?? null) !== ($configuration['project_id'] ?? null)
            || empty($serviceAccount['client_email'])
            || empty($serviceAccount['private_key'])
        ) {
            throw new RuntimeException('Firebase service account JSON does not match the configured project.');
        }

        $accessToken = $this->firebaseAccessToken($serviceAccount);
        Http::withToken($accessToken)
            ->timeout(20)
            ->post(
                'https://fcm.googleapis.com/v1/projects/'.$configuration['project_id'].'/messages:send',
                ['message' => [
                    'token' => $recipient,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => ['source' => 'shop_guard'],
                ]],
            )
            ->throw();
    }

    private function firebaseAccessToken(array $serviceAccount): string
    {
        $issuedAt = time();
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $encode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$claims;
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if ($privateKey === false || !openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign Firebase service account assertion.');
        }

        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned.'.'.$encode($signature),
        ])->throw()->json();

        if (empty($response['access_token'])) {
            throw new RuntimeException('Firebase OAuth response did not include an access token.');
        }

        return $response['access_token'];
    }

    private function logAttempt(object $job, int $attempt, string $status, ?Throwable $exception): void
    {
        DB::table('notification_logs')->insert([
            'notification_job_id' => $job->notification_job_id,
            'alert_id' => $job->alert_id,
            'channel' => $job->channel,
            'recipient_masked' => $this->maskRecipient($job->recipient),
            'provider_name' => match ($job->channel) {
                'EMAIL' => 'SMTP',
                'SMS' => 'TWILIO',
                'WHATSAPP' => 'META_WHATSAPP',
                'PUSH' => 'FIREBASE_FCM',
                default => 'UNKNOWN',
            },
            'status' => $status,
            'attempt_no' => $attempt,
            'request_summary' => json_encode(['delivery' => 'notification_job'], JSON_THROW_ON_ERROR),
            'response_summary' => json_encode(['accepted' => $exception === null], JSON_THROW_ON_ERROR),
            'error_code' => $exception ? 'DELIVERY_FAILED' : null,
            'error_message' => $exception ? 'Provider delivery request failed.' : null,
            'attempted_at' => now(),
            'delivered_at' => $exception === null ? now() : null,
        ]);
    }

    private function maskRecipient(string $recipient): string
    {
        if (str_contains($recipient, '@')) {
            [$name, $domain] = explode('@', $recipient, 2);

            return substr($name, 0, 1).'***@'.$domain;
        }

        return strlen($recipient) > 4 ? '***'.substr($recipient, -4) : '***';
    }
}
