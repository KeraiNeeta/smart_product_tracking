<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NotificationProviderSettings
{
    public function channels(): array
    {
        return [
            'EMAIL' => [
                'label' => 'Email',
                'provider' => 'SMTP',
                'description' => 'Send Shop Guard alerts through your SMTP server.',
                'fields' => [
                    'host' => ['label' => 'SMTP host', 'type' => 'text', 'secret' => false],
                    'port' => ['label' => 'SMTP port', 'type' => 'number', 'secret' => false],
                    'encryption' => ['label' => 'Encryption', 'type' => 'select', 'secret' => false, 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None']],
                    'username' => ['label' => 'SMTP username (optional)', 'type' => 'text', 'secret' => false, 'required' => false],
                    'password' => ['label' => 'SMTP password (optional)', 'type' => 'password', 'secret' => true, 'required' => false],
                    'from_address' => ['label' => 'From email', 'type' => 'email', 'secret' => false],
                    'from_name' => ['label' => 'From name', 'type' => 'text', 'secret' => false],
                ],
            ],
            'SMS' => [
                'label' => 'SMS',
                'provider' => 'TWILIO',
                'description' => 'Send text messages using a Twilio sender.',
                'fields' => [
                    'account_sid' => ['label' => 'Twilio Account SID', 'type' => 'text', 'secret' => false],
                    'auth_token' => ['label' => 'Twilio Auth Token', 'type' => 'password', 'secret' => true],
                    'from_number' => ['label' => 'Twilio sender number', 'type' => 'text', 'secret' => false],
                ],
            ],
            'WHATSAPP' => [
                'label' => 'WhatsApp',
                'provider' => 'META_WHATSAPP',
                'description' => 'Send WhatsApp Cloud API messages from a Meta business phone number.',
                'fields' => [
                    'access_token' => ['label' => 'Meta access token', 'type' => 'password', 'secret' => true],
                    'phone_number_id' => ['label' => 'WhatsApp phone number ID', 'type' => 'text', 'secret' => false],
                    'graph_version' => ['label' => 'Graph API version', 'type' => 'text', 'secret' => false],
                ],
            ],
            'PUSH' => [
                'label' => 'Push notifications',
                'provider' => 'FIREBASE_FCM',
                'description' => 'Send device push messages using Firebase Cloud Messaging HTTP v1.',
                'fields' => [
                    'project_id' => ['label' => 'Firebase project ID', 'type' => 'text', 'secret' => false],
                    'service_account_json' => ['label' => 'Firebase service account JSON', 'type' => 'textarea', 'secret' => true],
                ],
            ],
        ];
    }

    public function all(): array
    {
        $records = DB::table('notification_provider_settings')->get()->keyBy('channel');
        $result = [];

        foreach ($this->channels() as $channel => $definition) {
            $record = $records->get($channel);
            $settings = $record ? $this->decrypt($record->encrypted_settings) : [];
            $result[$channel] = [
                ...$definition,
                'enabled' => (bool) ($record?->is_enabled ?? false),
                'configured' => $record !== null && $settings !== [],
                'has_secrets' => collect($definition['fields'])
                    ->filter(fn (array $field): bool => $field['secret'])
                    ->keys()
                    ->contains(fn (string $key): bool => isset($settings[$key]) && $settings[$key] !== ''),
            ];
        }

        return $result;
    }

    public function settingsFor(string $channel): array
    {
        $record = DB::table('notification_provider_settings')->where('channel', $channel)->first();

        return $record ? $this->decrypt($record->encrypted_settings) : [];
    }

    public function save(string $channel, array $input, bool $enabled, int $userId): void
    {
        $definition = $this->channels()[$channel] ?? throw new RuntimeException('Unsupported notification channel.');
        $existing = DB::table('notification_provider_settings')->where('channel', $channel)->first();
        $current = $this->settingsFor($channel);

        foreach ($definition['fields'] as $key => $field) {
            if (($field['secret'] ?? false) && ($input[$key] ?? '') === '' && isset($current[$key])) {
                $input[$key] = $current[$key];
            }
        }

        DB::table('notification_provider_settings')->updateOrInsert(
            ['channel' => $channel],
            [
                'provider' => $definition['provider'],
                'is_enabled' => $enabled,
                'encrypted_settings' => Crypt::encryptString(json_encode($input, JSON_THROW_ON_ERROR)),
                'updated_by' => $userId,
                'updated_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
            ],
        );
    }

    private function decrypt(string $encryptedSettings): array
    {
        $decoded = json_decode(Crypt::decryptString($encryptedSettings), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Stored notification provider settings are invalid.');
        }

        return $decoded;
    }
}
