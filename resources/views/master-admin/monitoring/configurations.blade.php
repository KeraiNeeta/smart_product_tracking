@extends('master-admin.layout')

@section('title', 'Monitoring configuration')

@section('content')
    <div class="page-heading">
        <div><h1>Monitoring configuration</h1><p class="muted">Every change creates a new immutable version and a pending device-sync command.</p></div>
        <a class="button" href="{{ route('master-admin.configurations.create') }}">New configuration</a>
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $configurations, 'placeholder' => 'Search device, mode, timezone, version…'])
        <table>
            <thead><tr><th>Device</th><th>Version</th><th>Monitoring</th><th>Schedule</th><th>Hooter</th><th>Alerts</th><th>Sync</th><th>Effective from</th><th>Updated by</th></tr></thead>
            <tbody>
                @forelse ($configurations as $configuration)
                    <tr>
                        <td>{{ $configuration->device_uid }}</td>
                        <td>{{ $configuration->config_version }}</td>
                        <td>{{ $configuration->manual_override ?: $configuration->monitoring_mode }}<div class="muted">{{ $configuration->timezone }}</div></td>
                        <td>{{ $configuration->schedule_enabled ? 'Enabled' : 'Disabled' }}</td>
                        <td>{{ $configuration->hooter_enabled ? 'On' : 'Off' }}</td>
                        <td>Open: {{ $configuration->alert_on_open ? 'On' : 'Off' }}<div class="muted">Close: {{ $configuration->alert_on_close ? 'On' : 'Off' }}</div></td>
                        <td><span class="badge {{ $configuration->acknowledged_version >= $configuration->config_version ? 'delivered' : 'pending' }}">{{ $configuration->acknowledged_version >= $configuration->config_version ? 'Acknowledged' : 'Pending' }}</span></td>
                        <td>{{ $configuration->effective_from }}</td>
                        <td>{{ $configuration->updated_by_name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">{{ request()->filled('search') ? 'No configurations match your search.' : 'No monitoring configurations have been created.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $configurations->links() }}</div>
    </div>
    <div class="page-heading section-heading">
        <div><h2>Schedules</h2><p class="muted">Set a timezone-aware weekly monitoring window for each device.</p></div>
        <a class="button" href="{{ route('master-admin.schedules.index') }}">Manage schedules</a>
    </div>
@endsection
