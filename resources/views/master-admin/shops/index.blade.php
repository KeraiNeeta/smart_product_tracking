@extends('master-admin.layout')

@section('title', 'Shops')

@section('content')
    <div class="page-heading">
        <div><h1>Shops</h1><p class="muted">Customer locations, contacts and timezone settings.</p></div>
        <a class="button" href="{{ route('master-admin.shops.create') }}">Add shop</a>
    </div>
    <div class="panel">
        @include('components.table-search', ['rows' => $shops, 'placeholder' => 'Search shop, customer, company, location…'])
        <table>
            <thead><tr><th>Shop</th><th>Company</th><th>Customer</th><th>Location</th><th>Timezone</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($shops as $shop)
                    <tr>
                        <td>{{ $shop->shop_name }}<div class="muted">{{ $shop->shop_code }}</div></td>
                        <td>{{ $shop->company_name ?: '—' }}</td>
                        <td>{{ $shop->customer_name }}</td>
                        <td>{{ collect([$shop->city, $shop->state, $shop->postal_code])->filter()->join(', ') ?: '—' }}</td>
                        <td>{{ $shop->timezone }}</td>
                        <td><span class="badge {{ $shop->status }}">{{ ucfirst($shop->status) }}</span></td>
                        <td><div class="actions">
                            <a class="button" href="{{ route('master-admin.shops.edit', $shop->shop_id) }}">Edit</a>
                            @if (!$shop->assignment_count && !$shop->alerts_count)
                                <form method="POST" action="{{ route('master-admin.shops.destroy', $shop->shop_id) }}" onsubmit="return confirm('Delete this shop?')">@csrf @method('DELETE')<button class="danger" type="submit">Delete</button></form>
                            @else
                                <span class="muted">In use</span>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7">{{ request()->filled('search') ? 'No shops match your search.' : 'No shops have been added yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $shops->links() }}</div>
    </div>
@endsection
