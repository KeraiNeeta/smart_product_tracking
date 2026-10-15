@extends('master-admin.layout')

@section('title', 'Devices')

@section('content')
    <div class="page-heading">
        <div><h1>Device inventory</h1><p class="muted">Register devices, track ownership, and record inventory transfers.</p></div>
        <a class="button" href="{{ route('master-admin.devices.create') }}">Register device</a>
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $devices, 'placeholder' => 'Search device, model, company, owner…'])
        <table>
            <thead><tr><th>Device UID</th><th>Serial / model</th><th>Company</th><th>Owner / shop</th><th>Lifecycle</th><th>Connectivity</th><th>Last seen</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td>{{ $device->device_uid }}</td>
                        <td>{{ $device->serial_number ?: '—' }}<div class="muted">{{ $device->model }}</div></td>
                        <td>{{ $device->company_name ?: '—' }}</td>
                        <td>{{ $device->owner_name ?: 'Unassigned' }}<div class="muted">{{ $device->shop_name }}</div></td>
                        <td><span class="badge {{ $device->lifecycle_status }}">{{ str_replace('_', ' ', ucfirst($device->lifecycle_status)) }}</span></td>
                        <td>{{ ucfirst($device->connectivity_status) }}</td>
                        <td>{{ $device->last_seen_at ?: '—' }}</td>
                        <td><div class="actions">
                            <a class="button" href="{{ route('master-admin.devices.edit', $device->device_id) }}">Edit</a>
                            <a class="button" href="{{ route('master-admin.devices.transfer-form', $device->device_id) }}">Transfer / assign</a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8">{{ request()->filled('search') ? 'No devices match your search.' : 'No devices have been registered yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $devices->links() }}</div>
    </div>
@endsection
