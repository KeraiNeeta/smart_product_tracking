@extends('master-admin.layout')

@section('title', $schedule ? 'Edit schedule' : 'Add schedule')

@section('content')
    <div class="page-heading"><h1>{{ $schedule ? 'Edit schedule' : 'Add schedule' }}</h1></div>
    <div class="panel">
        <form method="POST" action="{{ $schedule ? route('master-admin.schedules.update', $schedule->schedule_id) : route('master-admin.schedules.store') }}">
            @csrf
            @if ($schedule) @method('PUT') @endif
            <div class="field"><label for="device_id">Device</label><select id="device_id" name="device_id" required><option value="">Select a device</option>@foreach ($devices as $device)<option value="{{ $device->device_id }}" @selected((string) old('device_id', $schedule?->device_id) === (string) $device->device_id)>{{ $device->device_uid }}</option>@endforeach</select>@error('device_id') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="schedule_name">Schedule name</label><input id="schedule_name" name="schedule_name" value="{{ old('schedule_name', $schedule?->schedule_name ?? 'Default Schedule') }}" required maxlength="150"></div>
            <div class="field"><label for="start_time">Start time</label><input id="start_time" type="time" name="start_time" value="{{ old('start_time', $schedule ? substr((string) $schedule->start_time, 0, 5) : '21:00') }}" required></div>
            <div class="field"><label for="end_time">End time</label><input id="end_time" type="time" name="end_time" value="{{ old('end_time', $schedule ? substr((string) $schedule->end_time, 0, 5) : '07:00') }}" required><small class="muted">End times earlier than start times represent overnight windows.</small></div>
            @php($selectedDays = old('weekdays', $schedule?->weekdays ?? [1, 2, 3, 4, 5, 6, 7]))
            <div class="field"><label>Days of week</label><div class="actions">@foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $index => $day)<label><input type="checkbox" name="weekdays[]" value="{{ $index + 1 }}" style="width:auto" @checked(in_array($index + 1, array_map('intval', $selectedDays)))> {{ $day }}</label>@endforeach</div>@error('weekdays') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="timezone">IANA timezone</label><input id="timezone" name="timezone" value="{{ old('timezone', $schedule?->timezone ?? 'Asia/Kolkata') }}" required>@error('timezone') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="valid_from">Valid from</label><input id="valid_from" type="date" name="valid_from" value="{{ old('valid_from', $schedule?->valid_from) }}"></div>
            <div class="field"><label for="valid_to">Valid to</label><input id="valid_to" type="date" name="valid_to" value="{{ old('valid_to', $schedule?->valid_to) }}"></div>
            <div class="field"><label for="priority">Priority</label><input id="priority" type="number" name="priority" min="0" max="32767" value="{{ old('priority', $schedule?->priority ?? 100) }}" required></div>
            <div class="field"><label><input type="checkbox" name="is_enabled" value="1" style="width:auto" @checked(old('is_enabled', $schedule?->is_enabled ?? true))> Enabled</label></div>
            <div class="form-actions"><button type="submit">{{ $schedule ? 'Save changes' : 'Create schedule' }}</button><a class="button secondary" href="{{ route('master-admin.schedules.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
