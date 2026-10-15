@extends('master-admin.layout')

@section('title', $company ? 'Edit company' : 'Add company')

@section('content')
    <div class="page-heading"><h1>{{ $company ? 'Edit company' : 'Add company' }}</h1></div>
    <div class="panel">
        <form method="POST" action="{{ $company ? route('master-admin.companies.update', $company->company_id) : route('master-admin.companies.store') }}">
            @csrf
            @if ($company) @method('PUT') @endif
            <div class="field"><label for="company_code">Company code</label><input id="company_code" name="company_code" value="{{ old('company_code', $company?->company_code) }}" required maxlength="50">@error('company_code') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="company_name">Company name</label><input id="company_name" name="company_name" value="{{ old('company_name', $company?->company_name) }}" required maxlength="200">@error('company_name') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="legal_name">Legal name</label><input id="legal_name" name="legal_name" value="{{ old('legal_name', $company?->legal_name) }}" maxlength="250">@error('legal_name') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $company?->email) }}" maxlength="255">@error('email') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $company?->phone) }}" maxlength="30">@error('phone') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="field"><label for="status">Status</label><select id="status" name="status" required>@foreach (['active', 'inactive', 'suspended'] as $status)<option value="{{ $status }}" @selected(old('status', $company?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>@error('status') <div class="error">{{ $message }}</div> @enderror</div>
            <div class="form-actions"><button type="submit">{{ $company ? 'Save changes' : 'Create company' }}</button><a class="button secondary" href="{{ route('master-admin.companies.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection
