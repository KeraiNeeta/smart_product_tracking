<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use App\Services\NotificationDeliveryService;
use App\Services\NotificationProviderSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class NotificationProviderSettingsController extends Controller
{
    public function index(NotificationProviderSettings $settings): View
    {
        $channels = $settings->all();
        foreach ($channels as $channel => $definition) {
            $storedSettings = $settings->settingsFor($channel);
            foreach ($definition['fields'] as $key => &$field) {
                $field['value'] = ($field['secret'] ?? false) ? '' : ($storedSettings[$key] ?? '');
                $field['secret_saved'] = (bool) ($field['secret'] ?? false)
                    && isset($storedSettings[$key])
                    && $storedSettings[$key] !== '';
            }
            unset($field);
            $channels[$channel]['fields'] = $definition['fields'];
        }

        return view('master-admin.notifications.providers', compact('channels'));
    }

    public function update(
        Request $request,
        string $channel,
        NotificationProviderSettings $settings,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $definition = $settings->channels()[$channel] ?? abort(404);
        $existing = $settings->settingsFor($channel);
        $rules = ['is_enabled' => ['nullable', 'boolean']];
        foreach ($definition['fields'] as $key => $field) {
            $formatRule = match (true) {
                $channel === 'SMS' && $key === 'account_sid' => 'regex:/^AC[a-fA-F0-9]{32}$/',
                $channel === 'SMS' && $key === 'from_number' => 'regex:/^\+[1-9]\d{7,14}$/',
                $channel === 'WHATSAPP' && $key === 'phone_number_id' => 'regex:/^\d+$/',
                $channel === 'WHATSAPP' && $key === 'graph_version' => 'regex:/^v\d+\.\d+$/',
                default => null,
            };
            $rules['settings.'.$key] = [
                (($field['secret'] ?? false) && isset($existing[$key])) || !($field['required'] ?? true)
                    ? 'nullable'
                    : 'required',
                match ($field['type']) {
                    'email' => 'email',
                    'number' => 'integer',
                    'select' => 'in:'.implode(',', array_keys($field['options'])),
                    'textarea' => 'string',
                    default => 'string',
                },
                ...($formatRule ? [$formatRule] : []),
                'max:12000',
            ];
        }
        try {
            $data = $request->validate($rules);
        } catch (ValidationException $exception) {
            $this->removeSecretInput($request, $definition);
            throw $exception;
        }
        $input = $data['settings'];
        if (
            $channel === 'EMAIL'
            && (bool) ($input['username'] ?? '') !== (bool) ($input['password'] ?? $existing['password'] ?? '')
        ) {
            $this->removeSecretInput($request, $definition);
            throw ValidationException::withMessages([
                'settings.password' => 'Enter both SMTP username and password, or leave both blank for an unauthenticated server.',
            ]);
        }
        foreach ($definition['fields'] as $key => $field) {
            if (($field['secret'] ?? false) && ($input[$key] ?? '') === '' && isset($existing[$key])) {
                $input[$key] = $existing[$key];
            }
        }
        if (
            $channel === 'PUSH'
            && isset($input['service_account_json'])
        ) {
            try {
                $serviceAccount = json_decode($input['service_account_json'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->removeSecretInput($request, $definition);
                throw ValidationException::withMessages([
                    'settings.service_account_json' => 'Enter valid Firebase service account JSON.',
                ]);
            }
            if (
                !is_array($serviceAccount)
                || ($serviceAccount['type'] ?? null) !== 'service_account'
                || ($serviceAccount['project_id'] ?? null) !== ($input['project_id'] ?? null)
                || empty($serviceAccount['private_key'])
                || empty($serviceAccount['client_email'])
            ) {
                $this->removeSecretInput($request, $definition);
                throw ValidationException::withMessages([
                    'settings.service_account_json' => 'The service account must contain a private key and match the Firebase project ID.',
                ]);
            }
        }

        $settings->save(
            $channel,
            $input,
            $request->boolean('is_enabled'),
            (int) $request->user()->getAuthIdentifier(),
        );
        $audit->record(
            $request,
            'provider_settings_updated',
            'notification_provider',
            $channel,
            after: ['provider' => $definition['provider'], 'is_enabled' => $request->boolean('is_enabled')],
        );

        return back()->with('status', $definition['label'].' provider settings saved. Secrets are encrypted.');
    }

    public function test(
        Request $request,
        string $channel,
        NotificationProviderSettings $settings,
        NotificationDeliveryService $delivery,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        abort_unless(isset($settings->channels()[$channel]), 404);
        $recipientRule = match ($channel) {
            'EMAIL' => 'required|email|max:255',
            'SMS', 'WHATSAPP' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'PUSH' => 'required|string|max:4096',
        };
        $data = $request->validate(['test_recipient' => $recipientRule]);

        try {
            $delivery->sendTest($channel, $data['test_recipient']);
        } catch (
            RequestException
            | ConnectionException
            | TransportExceptionInterface
            | RuntimeException
            | JsonException $exception
        ) {
            $context = [
                'channel' => $channel,
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception::class,
            ];
            if ($exception instanceof RequestException) {
                $context['http_status'] = $exception->response->status();
            }
            Log::warning('Notification provider test failed.', $context);

            return back()->withErrors([
                'test_recipient' => $exception instanceof RequestException
                    ? 'The provider rejected the test request with HTTP '.$exception->response->status().'. Verify the provider credentials and recipient.'
                    : 'Test delivery failed. Verify the provider settings and recipient, then try again.',
            ]);
        }

        $audit->record(
            $request,
            'provider_test_sent',
            'notification_provider',
            $channel,
            after: ['channel' => $channel],
        );

        return back()->with('status', 'Test '.$settings->channels()[$channel]['label'].' notification accepted by the provider.');
    }

    private function removeSecretInput(Request $request, array $definition): void
    {
        $input = $request->all();
        foreach ($definition['fields'] as $key => $field) {
            if ($field['secret'] ?? false) {
                unset($input['settings'][$key]);
            }
        }
        $request->replace($input);
    }
}
