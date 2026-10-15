@extends('master-admin.layout')

@section('title', 'Users')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Users</h1>
            <p class="muted">Create and manage platform accounts and role assignments.</p>
        </div>
        <a class="button" href="{{ route('master-admin.users.create') }}">Add user</a>
    </div>

    <div class="panel">
        @include('components.table-search', ['rows' => $users, 'placeholder' => 'Search name, email, mobile, role…'])
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->full_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->mobile ?: '—' }}</td>
                        <td>{{ $user->role?->role_name ?? 'Unassigned' }}</td>
                        <td><span class="badge {{ $user->status }}">{{ ucfirst($user->status) }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('master-admin.users.edit', $user) }}">Edit</a>
                                <form method="POST" action="{{ route('master-admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ request()->filled('search') ? 'No users match your search.' : 'No users found.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $users->links() }}</div>
    </div>
@endsection
