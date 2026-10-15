@extends('master-admin.layout')

@section('title', 'Monitoring schedules')

@section('content')
    <div class="page-heading">
        <div><h1>Monitoring schedules</h1><p class="muted">Weekly windows use each schedule's IANA timezone; overnight windows are supported.</p></div>
        <a class="button" href="{{ route('master-admin.schedules.create') }}">Add schedule</a>
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $schedules, 'placeholder' => 'Search schedule, device, timezone…'])
        <table>
            <thead><tr><th>Schedule</th><th>Device</th><th>Window</th><th>Days</th><th>Timezone</th><th>Valid dates</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->schedule_name }}</td>
                        <td>{{ $schedule->device_uid }}</td>
                        <td>{{ substr((string) $schedule->start_time, 0, 5) }}–{{ substr((string) $schedule->end_time, 0, 5) }}</td>
                        <td>{{ collect(explode(',', trim((string) $schedule->weekdays, '{}')))->map(fn ($day) => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][(int) $day - 1] ?? $day)->join(', ') }}</td>
                        <td>{{ $schedule->timezone }}</td>
                        <td>{{ $schedule->valid_from ?: 'Always' }}{{ $schedule->valid_to ? ' – '.$schedule->valid_to : '' }}</td>
                        <td>{{ $schedule->priority }}</td>
                        <td><span class="badge {{ $schedule->is_enabled ? 'active' : 'inactive' }}">{{ $schedule->is_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                        <td><div class="actions">
                            <a class="button" href="{{ route('master-admin.schedules.edit', $schedule->schedule_id) }}">Edit</a>
                            <form method="POST" action="{{ route('master-admin.schedules.destroy', $schedule->schedule_id) }}" onsubmit="return confirm('Delete this schedule?')">@csrf @method('DELETE')<button class="danger" type="submit">Delete</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="9">{{ request()->filled('search') ? 'No schedules match your search.' : 'No schedules have been configured.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $schedules->links() }}</div>
    </div>
@endsection
