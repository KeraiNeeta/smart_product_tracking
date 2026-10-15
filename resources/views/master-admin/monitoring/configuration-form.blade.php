@extends('master-admin.layout')

@section('title', 'New configuration')

@section('content')
    <div class="page-heading"><div><h1>New monitoring configuration</h1><p class="muted">This appends a version; previous configurations are retained.</p></div></div>
    <div class="panel">
        <form method="POST" action="{{ route('master-admin.configurations.store') }}">
            @csrf
            <div class="field"><label for="device_id">Device</label><select id="device_id" name="device_id" required><option value="">Select a device</option>@foreach ($devices as $device)<option value="{{ $device->device_id }}" @selected((string) old('device_id') === (string) $device->device_id)>{{ $device->device_uid }} {{ $device->model ? '— '.$device->model : '' }}</option>@endforeach</select>@error('device_id') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="monitoring_mode">Monitoring mode</label><select id="monitoring_mode" name="monitoring_mode" required><option value="DISARMED" @selected(old('monitoring_mode', 'DISARMED') === 'DISARMED')>Disarmed</option><option value="ARMED" @selected(old('monitoring_mode') === 'ARMED')>Armed</option><option value="SCHEDULED" @selected(old('monitoring_mode') === 'SCHEDULED')>Scheduled</option></select>@error('monitoring_mode') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="manual_override">Manual override</label><select id="manual_override" name="manual_override"><option value="">No manual override</option><option value="ARMED" @selected(old('manual_override') === 'ARMED')>Armed</option><option value="DISARMED" @selected(old('manual_override') === 'DISARMED')>Disarmed</option></select>@error('manual_override') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="timezone">IANA timezone</label><input id="timezone" name="timezone" value="{{ old('timezone', 'Asia/Kolkata') }}" required>@error('timezone') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="effective_from">Effective from</label><input id="effective_from" type="datetime-local" name="effective_from" value="{{ old('effective_from', now()->format('Y-m-d\TH:i')) }}" required>@error('effective_from') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="effective_to">Effective to <span class="muted">(optional)</span></label><input id="effective_to" type="datetime-local" name="effective_to" value="{{ old('effective_to') }}">@error('effective_to') <div class="error">{{ $message }}</div> @enderror</div>
            @foreach (['schedule_enabled' => 'Enable schedules', 'hooter_enabled' => 'Hooter enabled', 'alert_on_open' => 'Alert when shutter opens', 'alert_on_close' => 'Alert when shutter closes'] as $name => $label)
                <div class="field"><label><input type="checkbox" name="{{ $name }}" value="1" style="width:auto" @checked(old($name, in_array($name, ['hooter_enabled', 'alert_on_open'], true)))> {{ $label }}</label></div>
            @endforeach
            <div class="field"><label for="config_payload">Additional JSON payload</label><textarea id="config_payload" name="config_payload" placeholder="{}">{{ old('config_payload', '{}') }}</textarea>@error('config_payload') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="form-actions"><button type="submit">Save version and queue sync</button><a class="button secondary" href="{{ route('master-admin.configurations.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
