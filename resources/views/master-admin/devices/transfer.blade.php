@extends('master-admin.layout')

@section('title', 'Transfer device')

@section('content')
    <div class="page-heading">
        <div><h1>Transfer / assign device</h1><p class="muted">Device {{ $device->device_uid }} — {{ $device->model ?: 'model not set' }}</p></div>
    </div>
    <div class="panel">
        @if ($currentAssignment)
            <p>Current owner: <strong>{{ $currentAssignment->owner_name }}</strong> · Shop: <strong>{{ $currentAssignment->shop_name ?: 'Not assigned' }}</strong></p>
        @else
            <p>This device does not have a current owner assignment.</p>
        @endif
        <p class="muted">Saving creates a new assignment and an immutable inventory ledger entry.</p>
        <form method="POST" action="{{ route('master-admin.devices.transfer', $device->device_id) }}">
            @csrf
            <div class="field"><label for="owner_user_id">New owner</label><select id="owner_user_id" name="owner_user_id" required><option value="">Select an owner</option>@foreach ($owners as $owner)<option value="{{ $owner->user_id }}" @selected((string) old('owner_user_id', $currentAssignment?->owner_user_id) === (string) $owner->user_id)>{{ $owner->full_name }} — {{ $owner->role_name }}</option>@endforeach</select>@error('owner_user_id') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="shop_id">Shop <span class="muted">(optional)</span></label><select id="shop_id" name="shop_id"><option value="">Not installed at a shop</option>@foreach ($shops as $shop)<option value="{{ $shop->shop_id }}" @selected((string) old('shop_id', $currentAssignment?->shop_id) === (string) $shop->shop_id)>{{ $shop->shop_name }} — {{ $shop->shop_code }}</option>@endforeach</select>@error('shop_id') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="reference_no">Reference number</label><input id="reference_no" name="reference_no" value="{{ old('reference_no') }}" maxlength="100">@error('reference_no') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="2000">{{ old('notes') }}</textarea>@error('notes') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="form-actions"><button type="submit">Record transfer</button><a class="button secondary" href="{{ route('master-admin.devices.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
