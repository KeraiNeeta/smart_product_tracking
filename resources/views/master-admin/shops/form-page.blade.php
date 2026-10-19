@extends('master-admin.layout')

@section('title', $shop ? 'Edit shop' : 'Add shop')

@section('content')
    <div class="page-heading"><h1>{{ $shop ? 'Edit shop' : 'Add shop' }}</h1></div>
    <div class="panel">
        <form method="POST" action="{{ $shop ? route('master-admin.shops.update', $shop->shop_id) : route('master-admin.shops.store') }}">
            @csrf
            @if ($shop) @method('PUT') @endif
            <div class="field"><label for="shop_name">Shop name</label><input id="shop_name" name="shop_name" value="{{ old('shop_name', $shop?->shop_name) }}" required maxlength="200">@error('shop_name') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="shop_code">Shop code</label><input id="shop_code" name="shop_code" value="{{ old('shop_code', $shop?->shop_code) }}" maxlength="80">@error('shop_code') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label>Company</label><input value="{{ $companies->first()?->company_name ?? 'No active company' }}" disabled><small class="muted">Shops are assigned to the default company.</small></div>
            <input type="hidden" name="company_id" value="{{ $companies->first()?->company_id }}">
            <div class="field"><label for="customer_user_id">End customer</label><select id="customer_user_id" name="customer_user_id" required><option value="">Select a customer</option>@foreach ($customers as $customer)<option value="{{ $customer->user_id }}" @selected((string) old('customer_user_id', $shop?->customer_user_id) === (string) $customer->user_id)>{{ $customer->full_name }} — {{ $customer->email }}</option>@endforeach</select><small class="muted">Create an active user with the End Customer role first.</small>@error('customer_user_id') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="address_line1">Address line 1</label><input id="address_line1" name="address_line1" value="{{ old('address_line1', $shop?->address_line1) }}" maxlength="250"></div>
            <div class="field"><label for="address_line2">Address line 2</label><input id="address_line2" name="address_line2" value="{{ old('address_line2', $shop?->address_line2) }}" maxlength="250"></div>
            <div class="field"><label for="city">City</label><input id="city" name="city" value="{{ old('city', $shop?->city) }}" maxlength="100"></div>
            <div class="field"><label for="state">State</label><input id="state" name="state" value="{{ old('state', $shop?->state) }}" maxlength="100"></div>
            <div class="field"><label for="postal_code">Postal code</label><input id="postal_code" name="postal_code" value="{{ old('postal_code', $shop?->postal_code) }}" maxlength="20"></div>
            <div class="field"><label for="latitude">Latitude</label><input id="latitude" type="number" name="latitude" step="0.0000001" min="-90" max="90" value="{{ old('latitude', $shop?->latitude) }}"></div>
            <div class="field"><label for="longitude">Longitude</label><input id="longitude" type="number" name="longitude" step="0.0000001" min="-180" max="180" value="{{ old('longitude', $shop?->longitude) }}"></div>
            <div class="field"><label for="timezone">IANA timezone</label><input id="timezone" name="timezone" value="{{ old('timezone', $shop?->timezone ?? 'Asia/Kolkata') }}" required placeholder="Asia/Kolkata">@error('timezone') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="contact_name">Contact name</label><input id="contact_name" name="contact_name" value="{{ old('contact_name', $shop?->contact_name) }}" maxlength="200"></div>
            <div class="field"><label for="contact_mobile">Contact mobile</label><input id="contact_mobile" name="contact_mobile" value="{{ old('contact_mobile', $shop?->contact_mobile) }}" maxlength="30"></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="active" @selected(old('status', $shop?->status ?? 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $shop?->status) === 'inactive')>Inactive</option></select></div>
            <div class="form-actions"><button type="submit">{{ $shop ? 'Save changes' : 'Create shop' }}</button><a class="button secondary" href="{{ route('master-admin.shops.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
