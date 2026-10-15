@extends('master-admin.layout')

@section('title', $definition['title'])

@section('content')
    <div class="page-heading">
        <div><h1>{{ $definition['title'] }}</h1><p class="muted">{{ $definition['description'] }}</p></div>
    </div>
    <div class="actions" style="margin-bottom: 18px">
        @foreach (['inventory' => 'Inventory', 'events' => 'Events', 'heartbeats' => 'Heartbeats', 'commands' => 'Commands', 'notification-jobs' => 'Notification queue', 'notification-logs' => 'Delivery logs', 'push-tokens' => 'Push registrations', 'audit' => 'Audit trail'] as $slug => $label)
            <a class="button {{ $module === $slug ? '' : 'secondary' }}" href="{{ route('master-admin.operations.index', $slug) }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $records, 'placeholder' => 'Search records…'])
        <table>
            <thead><tr>@foreach ($definition['columns'] as $column)<th>{{ $definition['labels'][$column] }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        @foreach ($definition['columns'] as $column)
                            <td>
                                @if (in_array($column, ['is_active'], true))
                                    <span class="badge {{ $record->{$column} ? 'active' : 'inactive' }}">{{ $record->{$column} ? 'Yes' : 'No' }}</span>
                                @else
                                    {{ $record->{$column} ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($definition['columns']) }}">{{ request()->filled('search') ? 'No records match your search.' : 'No records found.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $records->links() }}</div>
    </div>
@endsection
