@extends('master-admin.layout')

@section('title', 'Companies')

@section('content')
    <div class="page-heading">
        <div><h1>Companies</h1><p class="muted">Manage tenant organizations and company accounts.</p></div>
        <a class="button" href="{{ route('master-admin.companies.create') }}">Add company</a>
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $companies, 'placeholder' => 'Search company, email, code, status…'])
        <table>
            <thead><tr><th>Code</th><th>Company</th><th>Contact</th><th>Users</th><th>Shops</th><th>Devices</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr>
                        <td>{{ $company->company_code }}</td>
                        <td>{{ $company->company_name }}<div class="muted">{{ $company->legal_name }}</div></td>
                        <td>{{ $company->email }}<div class="muted">{{ $company->phone }}</div></td>
                        <td>{{ $company->users_count }}</td>
                        <td>{{ $company->shops_count }}</td>
                        <td>{{ $company->devices_count }}</td>
                        <td><span class="badge {{ $company->status }}">{{ ucfirst($company->status) }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('master-admin.companies.edit', $company->company_id) }}">Edit</a>
                                @if (!$company->users_count && !$company->devices_count && !$company->shops_count && !$company->inventory_count && !$company->notification_rules_count && !$company->audit_logs_count)
                                    <form method="POST" action="{{ route('master-admin.companies.destroy', $company->company_id) }}" onsubmit="return confirm('Delete this company?')">
                                        @csrf @method('DELETE')
                                        <button class="danger" type="submit">Delete</button>
                                    </form>
                                @else
                                    <span class="muted">In use</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">{{ request()->filled('search') ? 'No companies match your search.' : 'No companies have been added yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $companies->links() }}</div>
    </div>
@endsection
