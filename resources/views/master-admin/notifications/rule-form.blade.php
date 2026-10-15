@extends('master-admin.layout')

@section('title', isset($rule) ? 'Edit notification rule' : 'Add notification rule')

@section('content')
    <div class="page-heading"><h1>{{ isset($rule) ? 'Edit notification rule' : 'Add notification rule' }}</h1></div>
    <div class="panel">
        <form method="POST" action="{{ isset($rule) ? route('master-admin.notification-rules.update', $rule->notification_rule_id) : route('master-admin.notification-rules.store') }}">
            @csrf
            @if (isset($rule)) @method('PUT') @endif
            <div class="field"><label for="alert_type">Alert type</label><input id="alert_type" name="alert_type" value="{{ old('alert_type', $rule->alert_type ?? '') }}" required maxlength="60"><small class="muted">Use ARMED or DISARMED to notify active company users when shutter monitoring state changes.</small>@error('alert_type') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="channel">Channel</label><select id="channel" name="channel" required>@foreach (['SMS', 'WHATSAPP', 'EMAIL', 'VOICE_CALL', 'PUSH'] as $channel)<option value="{{ $channel }}" @selected(old('channel', $rule->channel ?? 'SMS') === $channel)>{{ str_replace('_', ' ', ucfirst(strtolower($channel))) }}</option>@endforeach</select>@error('channel') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="company_id">Company scope</label><select id="company_id" name="company_id"><option value="">All companies</option>@foreach ($companies as $company)<option value="{{ $company->company_id }}" @selected((string) old('company_id', $rule->company_id ?? '') === (string) $company->company_id)>{{ $company->company_name }}</option>@endforeach</select></div>
            <div class="field"><label for="user_id">User scope</label><select id="user_id" name="user_id"><option value="">Any user</option>@foreach ($users as $user)<option value="{{ $user->user_id }}" @selected((string) old('user_id', $rule->user_id ?? '') === (string) $user->user_id)>{{ $user->full_name }} — {{ $user->company_id ? 'Company '.$user->company_id : 'Global' }}</option>@endforeach</select></div>
            <div class="field"><label for="device_id">Device scope</label><select id="device_id" name="device_id"><option value="">Any device</option>@foreach ($devices as $device)<option value="{{ $device->device_id }}" @selected((string) old('device_id', $rule->device_id ?? '') === (string) $device->device_id)>{{ $device->device_uid }}</option>@endforeach</select></div>
            <div class="field"><label for="recipient_value">Recipient override</label><input id="recipient_value" name="recipient_value" value="{{ old('recipient_value', $rule->recipient_value ?? '') }}" maxlength="255"></div>
            <div class="field"><label for="priority">Priority</label><input id="priority" type="number" name="priority" min="0" max="32767" value="{{ old('priority', $rule->priority ?? 100) }}" required></div>
            <div class="field"><label for="quiet_hours_start">Quiet hours start</label><input id="quiet_hours_start" type="time" name="quiet_hours_start" value="{{ old('quiet_hours_start', isset($rule->quiet_hours_start) ? substr((string) $rule->quiet_hours_start, 0, 5) : '') }}"></div>
            <div class="field"><label for="quiet_hours_end">Quiet hours end</label><input id="quiet_hours_end" type="time" name="quiet_hours_end" value="{{ old('quiet_hours_end', isset($rule->quiet_hours_end) ? substr((string) $rule->quiet_hours_end, 0, 5) : '') }}"></div>
            <div class="field"><label><input type="checkbox" name="is_enabled" value="1" style="width:auto" @checked(old('is_enabled', $rule->is_enabled ?? true))> Enabled</label></div>
            <div class="form-actions"><button type="submit">{{ isset($rule) ? 'Save changes' : 'Create rule' }}</button><a class="button secondary" href="{{ route('master-admin.notification-rules.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
