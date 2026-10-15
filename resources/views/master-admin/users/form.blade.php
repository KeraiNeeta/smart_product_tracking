<div class="field">
    <label for="full_name">Full name</label>
    <input id="full_name" name="full_name" value="{{ old('full_name', $managedUser?->full_name) }}" required maxlength="255">
    @error('full_name') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $managedUser?->email) }}" required maxlength="255" autocomplete="email">
    @error('email') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="mobile">Mobile</label>
    <input id="mobile" name="mobile" value="{{ old('mobile', $managedUser?->mobile) }}" maxlength="30" autocomplete="tel">
    @error('mobile') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="role_id">Role</label>
    <select id="role_id" name="role_id" required>
        <option value="">Select a role</option>
        @foreach ($roles as $role)
            <option value="{{ $role->role_id }}" @selected((string) old('role_id', $managedUser?->role_id) === (string) $role->role_id)>
                {{ $role->role_name }} ({{ $role->role_code }})
            </option>
        @endforeach
    </select>
    @error('role_id') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="company_id">Company <span class="muted">(optional)</span></label>
    <select id="company_id" name="company_id">
        <option value="">No company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->company_id }}" @selected((string) old('company_id', $managedUser?->company_id) === (string) $company->company_id)>
                {{ $company->company_name }}
            </option>
        @endforeach
    </select>
    @if ($companies->isEmpty())
        <small class="muted">No companies have been created yet.</small>
    @endif
    @error('company_id') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="parent_user_id">Parent account <span class="muted">(optional)</span></label>
    <select id="parent_user_id" name="parent_user_id">
        <option value="">No parent account</option>
        @foreach ($parents as $parent)
            <option value="{{ $parent->user_id }}" @selected((string) old('parent_user_id', $managedUser?->parent_user_id) === (string) $parent->user_id)>
                {{ $parent->full_name }}{{ $parent->company_id ? ' — Company '.$parent->company_id : '' }}
            </option>
        @endforeach
    </select>
    @error('parent_user_id') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="status">Status</label>
    <select id="status" name="status" required>
        @foreach (['pending', 'active', 'inactive', 'blocked'] as $status)
            <option value="{{ $status }}" @selected(old('status', $managedUser?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    @error('status') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="password">Password @if ($managedUser) <span class="muted">(leave blank to keep current password)</span> @endif</label>
    <input id="password" type="password" name="password" autocomplete="new-password" @required(!$managedUser) minlength="8">
    @error('password') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="password_confirmation">Confirm password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" @required(!$managedUser) minlength="8">
</div>

<div class="form-actions">
    <button type="submit">{{ $managedUser ? 'Save changes' : 'Create user' }}</button>
    <a class="button secondary" href="{{ route('master-admin.users.index') }}">Cancel</a>
</div>
