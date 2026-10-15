@extends('master-admin.layout')

@section('title', 'Alerts')

@section('content')
    <div class="page-heading"><div><h1>Alerts</h1><p class="muted">Review device alerts, record acknowledgement, and resolve completed incidents.</p></div></div>
    <div class="panel">
        @include('components.table-search', ['rows' => $alerts, 'placeholder' => 'Search alert, device, shop, status…'])
        <table>
            <thead><tr><th>Alert</th><th>Device / shop</th><th>Severity</th><th>Status</th><th>Triggered</th><th>Acknowledged by</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr>
                        <td><strong>{{ $alert->title }}</strong><div class="muted">{{ $alert->message }}</div></td>
                        <td>{{ $alert->device_uid }}<div class="muted">{{ $alert->shop_name }}</div></td>
                        <td>{{ ucfirst($alert->severity) }}</td>
                        <td><span class="badge {{ $alert->status }}">{{ ucfirst($alert->status) }}</span></td>
                        <td>{{ $alert->triggered_at }}</td>
                        <td>{{ $alert->acknowledged_by_name ?: '—' }}</td>
                        <td>
                            @if (in_array($alert->status, ['open', 'acknowledged'], true))
                                <div class="actions">
                                    @if ($alert->status === 'open')
                                        <form method="POST" action="{{ route('master-admin.alerts.acknowledge', $alert->alert_id) }}">@csrf<button type="submit">Acknowledge</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('master-admin.alerts.resolve', $alert->alert_id) }}">@csrf<button class="secondary" type="submit">Resolve</button></form>
                                </div>
                            @else
                                <span class="muted">No action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">{{ request()->filled('search') ? 'No alerts match your search.' : 'No alerts have been received.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $alerts->links() }}</div>
    </div>
@endsection
