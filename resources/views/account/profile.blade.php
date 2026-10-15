@extends($layout)

@section('title', 'My profile')

@section('content')
    <div class="page-heading">
        <div>
            <h1>My profile</h1>
            <p class="muted">Update your account details and keep your password secure.</p>
        </div>
    </div>

    @if (session('status'))<div class="card">{{ session('status') }}</div>@endif

    <div class="cards profile-forms">
        <section class="card">
            <h2>Profile details</h2>
            <p class="muted">Changes apply only to your own account.</p>
            <form method="POST" action="{{ $isAdmin ? route('master-admin.profile.update') : route('portal.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required maxlength="150" autocomplete="name">
                    @error('full_name')<small class="muted">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                    @error('email')<small class="muted">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="mobile">Mobile number</label>
                    <input id="mobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="30" autocomplete="tel">
                    @error('mobile')<small class="muted">{{ $message }}</small>@enderror
                </div>
                <button type="submit">Save profile</button>
            </form>
        </section>

        <section class="card">
            <h2>Change password</h2>
            <p class="muted">Use your current password and choose at least 12 characters.</p>
            <form method="POST" action="{{ $isAdmin ? route('master-admin.profile.password') : route('portal.profile.password') }}">
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                    @error('current_password')<small class="muted">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="new_password">New password</label>
                    <input id="new_password" type="password" name="new_password" required minlength="12" autocomplete="new-password">
                    @error('new_password')<small class="muted">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="new_password_confirmation">Confirm new password</label>
                    <input id="new_password_confirmation" type="password" name="new_password_confirmation" required minlength="12" autocomplete="new-password">
                </div>
                <button type="submit">Change password</button>
            </form>
        </section>
    </div>
@endsection
