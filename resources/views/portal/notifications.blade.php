@extends('portal.layout')

@section('title', 'Notifications')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Product notifications</h1>
            <p class="muted">Notifications for Shop Guard products visible to your account.</p>
        </div>
        @if ($unreadNotificationCount > 0)
            <form method="POST" action="{{ route('portal.notifications.read') }}">
                @csrf
                <button type="submit">Mark all {{ $unreadNotificationCount }} as read</button>
            </form>
        @endif
    </div>
    @if (session('status'))<div class="card">{{ session('status') }}</div>@endif
    <div class="table-wrap">
        @include('components.table-search', ['rows' => $notifications, 'placeholder' => 'Search product, notification, channel…'])
        <table>
            <thead><tr><th>Product</th><th>Notification</th><th>Channel</th><th>Delivery status</th><th>Queued at</th></tr></thead>
            <tbody>
                @forelse ($notifications as $notification)
                    @php($payload = is_string($notification->payload) ? json_decode($notification->payload, true) : (array) $notification->payload)
                    <tr class="{{ $notification->read_at ? 'notification-read' : 'notification-unread' }}">
                        <td>{{ $notification->device_uid }}</td>
                        <td><strong>{{ $payload['title'] ?? 'Shutter update' }}</strong><div class="muted">{{ $payload['message'] ?? '' }}</div>@unless($notification->read_at)<span class="badge pending">Unread</span>@endunless</td>
                        <td>{{ str_replace('_', ' ', ucfirst(strtolower($notification->channel))) }}</td>
                        <td><span class="badge {{ $notification->delivery_status }}">{{ ucfirst($notification->delivery_status) }}</span></td>
                        <td>{{ $notification->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ request()->filled('search') ? 'No notifications match your search.' : 'No notifications have been queued for your products.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $notifications->links() }}</div>
    </div>
@endsection
