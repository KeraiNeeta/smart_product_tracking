@if (!$role)
    <div class="field">
        <label for="role_code">Role code</label>
        <input id="role_code" name="role_code" value="{{ old('role_code') }}" required maxlength="50" pattern="[A-Z][A-Z0-9_]*" placeholder="e.g. INVENTORY_MANAGER">
        <small class="muted">Use uppercase letters, numbers, and underscores; begin with a letter.</small>
        @error('role_code') <div class="error">{{ $message }}</div> @enderror
    </div>
@else
    <div class="field">
        <label>Role code</label>
        <input value="{{ $role->role_code }}" disabled>
    </div>
@endif

<div class="field">
    <label for="role_name">Role name</label>
    <input id="role_name" name="role_name" value="{{ old('role_name', $role?->role_name) }}" required maxlength="100">
    @error('role_name') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description" maxlength="1000">{{ old('description', $role?->description) }}</textarea>
    @error('description') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="form-actions">
    <button type="submit">{{ $role ? 'Save changes' : 'Create role' }}</button>
    <a class="button secondary" href="{{ route('master-admin.roles.index') }}">Cancel</a>
</div>
