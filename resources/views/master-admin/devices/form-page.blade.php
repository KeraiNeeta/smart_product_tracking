@extends('master-admin.layout')

@section('title', $device ? 'Edit device' : 'Register device')

@section('content')
    <div class="page-heading"><h1>{{ $device ? 'Edit device' : 'Register device' }}</h1></div>
    <div class="panel">
        <form method="POST" action="{{ $device ? route('master-admin.devices.update', $device->device_id) : route('master-admin.devices.store') }}">
            @csrf
            @if ($device) @method('PUT') @endif
            @if ($device)
                <div class="field"><label for="device_uid">Immutable device UID</label><input id="device_uid" value="{{ $device->device_uid }}" disabled></div>
            @else
                <div class="field"><label for="device_uid">Device UID</label><input id="device_uid" name="device_uid" value="{{ old('device_uid') }}" required maxlength="120">@error('device_uid') <div class="error">{{ $message }}</div> @enderror</div>
            @endif
            <div class="field"><label for="serial_number">Serial number</label><input id="serial_number" name="serial_number" value="{{ old('serial_number', $device?->serial_number) }}" maxlength="120">@error('serial_number') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="model">Model</label><input id="model" name="model" value="{{ old('model', $device?->model) }}" maxlength="100"></div>
            <div class="field"><label for="firmware_version">Firmware version</label><input id="firmware_version" name="firmware_version" value="{{ old('firmware_version', $device?->firmware_version) }}" maxlength="100"></div>
            @if (!$device)
                <div class="field"><label>Company</label><input value="{{ $companies->first()?->company_name ?? 'No active company' }}" disabled><small class="muted">New devices are added to the default company.</small></div>
                <input type="hidden" name="company_id" value="{{ $defaultCompanyId }}">
                <div class="field"><label for="manufacturing_batch">Manufacturing batch</label><input id="manufacturing_batch" name="manufacturing_batch" value="{{ old('manufacturing_batch') }}" maxlength="100"></div>
            @else
                <div class="field"><label>Company</label><input value="{{ $companies->firstWhere('company_id', $device->company_id)?->company_name ?? 'Unassigned' }}" disabled><small class="muted">Use Transfer / assign to change ownership.</small></div>
                <input type="hidden" name="company_id" value="{{ $device->company_id }}">
                <div class="field"><label for="manufacturing_batch">Manufacturing batch</label><input id="manufacturing_batch" name="manufacturing_batch" value="{{ old('manufacturing_batch', $device->manufacturing_batch) }}" maxlength="100"></div>
            @endif
            <div class="field"><label for="lifecycle_status">Lifecycle status</label><select id="lifecycle_status" name="lifecycle_status" required>@foreach ($device ? ['in_stock', 'assigned', 'active', 'maintenance', 'lost', 'retired'] : ['in_stock'] as $status)<option value="{{ $status }}" @selected(old('lifecycle_status', $device?->lifecycle_status ?? 'in_stock') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select>@if ($device)<small class="muted">Assigned/active states change via Transfer / assign; inventory transitions are recorded.</small>@endif @error('lifecycle_status') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="form-actions"><button type="submit">{{ $device ? 'Save changes' : 'Register device' }}</button><a class="button secondary" href="{{ route('master-admin.devices.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
