@extends('master-admin.layout')

@section('title', 'Notification rules')

@section('content')
    <div class="page-heading">
        <div><h1>Notification rules</h1><p class="muted">Configure channels and recipients for alert types.</p></div>
        <a class="button" href="{{ route('master-admin.notification-rules.create') }}">Add rule</a>
    </div>
    <div class="actions" style="margin-bottom: 18px">
        <a class="button" href="{{ route('master-admin.notification-providers.index') }}">Provider settings</a>
        <a class="button secondary" href="{{ route('master-admin.operations.index', 'notification-jobs') }}">Queue</a>
        <a class="button secondary" href="{{ route('master-admin.operations.index', 'notification-logs') }}">Delivery logs</a>
        <a class="button secondary" href="{{ route('master-admin.operations.index', 'push-tokens') }}">Push registrations</a>
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $rules, 'placeholder' => 'Search event, channel, company, recipient…'])
        <table>
            <thead><tr><th>Alert type</th><th>Channel</th><th>Scope</th><th>Recipient</th><th>Quiet hours</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td>{{ $rule->alert_type }}</td>
                        <td>{{ ucfirst($rule->channel) }}</td>
                        <td>{{ $rule->company_name ?: 'All companies' }}{{ $rule->full_name ? ' · '.$rule->full_name : '' }}{{ $rule->device_uid ? ' · '.$rule->device_uid : '' }}</td>
                        <td>{{ $rule->recipient_value ?: 'Account default' }}</td>
                        <td>{{ $rule->quiet_hours_start ?: '—' }}{{ $rule->quiet_hours_end ? ' – '.$rule->quiet_hours_end : '' }}</td>
                        <td>{{ $rule->priority }}</td>
                        <td><span class="badge {{ $rule->is_enabled ? 'active' : 'inactive' }}">{{ $rule->is_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                        <td><div class="actions">
                            <a class="button" href="{{ route('master-admin.notification-rules.edit', $rule->notification_rule_id) }}">Edit</a>
                            <form method="POST" action="{{ route('master-admin.notification-rules.destroy', $rule->notification_rule_id) }}" onsubmit="return confirm('Delete this notification rule?')">@csrf @method('DELETE')<button class="danger" type="submit">Delete</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8">{{ request()->filled('search') ? 'No notification rules match your search.' : 'No notification rules have been configured.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $rules->links() }}</div>
    </div>
@endsection
