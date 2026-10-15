@extends('master-admin.layout')

@section('title', 'Roles')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Roles</h1>
            <p class="muted">Manage role names and descriptions. Role codes are fixed after creation.</p>
        </div>
        <a class="button" href="{{ route('master-admin.roles.create') }}">Add role</a>
    </div>

    <div class="panel">
        @include('components.table-search', ['rows' => $roles, 'placeholder' => 'Search role name, code, description…'])
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Users</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>{{ $role->role_name }}</td>
                        <td><code>{{ $role->role_code }}</code></td>
                        <td>{{ $role->description ?: '—' }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td><span class="badge">{{ $role->is_system_role ? 'System' : 'Custom' }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('master-admin.roles.edit', $role) }}">Edit</a>
                                @if (!$role->is_system_role && $role->users_count === 0)
                                    <form method="POST" action="{{ route('master-admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger" type="submit">Delete</button>
                                    </form>
                                @else
                                    <span class="muted">{{ $role->is_system_role ? 'Protected' : 'In use' }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ request()->filled('search') ? 'No roles match your search.' : 'No roles found.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $roles->links() }}</div>
    </div>
@endsection
