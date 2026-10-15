@extends('master-admin.layout')

@section('title', 'Notification providers')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Notification provider settings</h1>
            <p class="muted">Configure delivery providers. Credentials are encrypted at rest and never displayed after saving.</p>
        </div>
        <a class="button secondary" href="{{ route('master-admin.notification-rules.index') }}">Manage notification rules</a>
    </div>

    <div class="alert" role="note">
        Delivery requires a provider to be enabled and a matching event rule to exist. Push delivery also requires registered active device tokens.
        Run <code>php artisan schedule:work</code> locally (or Laravel's scheduler in production) to process queued notifications each minute.
    </div>

    <div class="module-grid provider-grid">
        @foreach ($channels as $channel => $provider)
            <section class="panel provider-card">
                <div class="page-heading">
                    <div>
                        <h2>{{ $provider['label'] }}</h2>
                        <p class="muted">{{ $provider['description'] }}</p>
                    </div>
                    <span class="badge {{ $provider['enabled'] ? 'active' : 'inactive' }}">
                        {{ $provider['enabled'] ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <p class="muted">Provider: <strong>{{ str_replace('_', ' ', $provider['provider']) }}</strong>
                    @if ($provider['configured'])
                        · Settings saved
                    @else
                        · Not configured
                    @endif
                </p>
                @if ($channel === 'WHATSAPP')
                    <p class="muted">Meta may require an approved message template when the recipient is outside the 24-hour customer-service window.</p>
                @endif

                <form method="POST" action="{{ route('master-admin.notification-providers.update', $channel) }}">
                    @csrf
                    @method('PUT')
                    @foreach ($provider['fields'] as $key => $field)
                        <div class="field">
                            <label for="{{ $channel }}_{{ $key }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'select')
                                <select id="{{ $channel }}_{{ $key }}" name="settings[{{ $key }}]" required>
                                    @foreach ($field['options'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('settings.'.$key, $field['value']) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'textarea')
                                <textarea id="{{ $channel }}_{{ $key }}" name="settings[{{ $key }}]" rows="5" @required(($field['required'] ?? true) && !$field['secret_saved']) placeholder="{{ $field['secret_saved'] ? 'Saved securely — leave blank to keep current credential' : 'Paste the Firebase service account JSON' }}">{{ ($field['secret'] ?? false) ? '' : old('settings.'.$key, $field['value']) }}</textarea>
                            @else
                                <input
                                    id="{{ $channel }}_{{ $key }}"
                                    type="{{ $field['type'] }}"
                                    name="settings[{{ $key }}]"
                                    value="{{ ($field['secret'] ?? false) ? '' : old('settings.'.$key, $field['value']) }}"
                                    @required(($field['required'] ?? true) && !$field['secret_saved'])
                                    @if ($field['secret_saved']) placeholder="Saved securely — leave blank to keep current credential" @endif
                                    @if ($field['type'] === 'number') min="1" max="65535" @endif
                                >
                            @endif
                            @if ($field['secret_saved'])
                                <small class="muted">Credential is saved encrypted. Leave blank to retain it.</small>
                            @endif
                            @error('settings.'.$key) <div class="error">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                    <div class="field">
                        <label for="{{ $channel }}_enabled">
                            <input id="{{ $channel }}_enabled" type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $provider['enabled']))>
                            Enable {{ $provider['label'] }} delivery
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Save provider settings</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('master-admin.notification-providers.test', $channel) }}" style="margin-top:20px;padding-top:18px;border-top:1px solid #e8edf3">
                    @csrf
                    <div class="field">
                        <label for="{{ $channel }}_test_recipient">
                            @if ($channel === 'PUSH')
                                Registered Firebase device token
                            @elseif ($channel === 'EMAIL')
                                Test email address
                            @else
                                Test phone number in international format
                            @endif
                        </label>
                        <input
                            id="{{ $channel }}_test_recipient"
                            name="test_recipient"
                            type="{{ $channel === 'EMAIL' ? 'email' : 'text' }}"
                            placeholder="{{ $channel === 'EMAIL' ? 'admin@example.com' : ($channel === 'PUSH' ? 'FCM registration token' : '+15551234567') }}"
                            required
                        >
                        @error('test_recipient') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="button secondary">Send test notification</button>
                </form>
            </section>
        @endforeach
    </div>
@endsection
