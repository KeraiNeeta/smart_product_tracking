@extends('portal.layout')

@section('title', 'My dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Shutter dashboard</h1>
            <p class="muted">Your Shop Guard devices and recent activity.</p>
        </div>
        @if ($isDealer)
            <button type="button" class="button secondary" data-sidebar-toggle="dealer-network" aria-controls="dealer-network-sidebar" aria-expanded="true">Hide user tree</button>
        @endif
    </div>

    <div class="{{ $isDealer ? 'dealer-workspace' : '' }}" @if ($isDealer) data-sidebar-layout data-collapsed="false" @endif>
        @if ($isDealer)
            <aside class="dealer-sidebar" id="dealer-network-sidebar" aria-label="Dealer user and device hierarchy" data-sidebar-panel="dealer-network">
                <div class="dealer-sidebar-heading">
                    <div>
                        <span class="section-eyebrow">ACCOUNT TREE</span>
                        <h2>My network</h2>
                    </div>
                    <span class="network-count">{{ $deviceCount }} devices</span>
                </div>
                <p class="dealer-sidebar-caption">Sub-dealers, direct users, and their assigned devices.</p>
                <div class="dealer-tree">
                    @forelse ($dealerHierarchy as $branch)
                        @include('portal.partials.dealer-account', ['branch' => $branch])
                    @empty
                        <p class="network-empty">No sub-dealers or direct users are linked to your account yet.</p>
                    @endforelse
                </div>
            </aside>
        @endif

        <div class="{{ $isDealer ? 'dealer-main' : '' }}">
            <div class="cards">
                <div class="card metric"><span>Visible products</span><strong>{{ number_format($deviceCount) }}</strong></div>
                <div class="card metric"><span>Assigned / installed</span><strong>{{ number_format($assignedCount) }}</strong></div>
                <div class="card metric"><span>Open alerts</span><strong>{{ number_format($openAlertCount) }}</strong></div>
            </div>

            <h2 class="section-title">Products in your access scope</h2>
            <div class="table-wrap">
                @include('components.table-search', ['rows' => $devices, 'searchId' => 'portal-devices', 'placeholder' => 'Search product, shop, company…'])
                <table>
                    <thead><tr><th>Device</th><th>Shop</th><th>Company</th><th>Owner</th><th>Lifecycle</th><th>Connection</th><th>Last seen</th></tr></thead>
                    <tbody>
                        @forelse ($devices as $device)
                            <tr>
                                <td><strong>{{ $device->device_uid }}</strong><div class="muted">{{ $device->model ?: 'Shutter sensor' }}</div></td>
                                <td>{{ $device->shop_name ?: '—' }}</td>
                                <td>{{ $device->company_name ?: '—' }}</td>
                                <td>{{ $device->owner_name ?: 'Unassigned' }}</td>
                                <td><span class="badge {{ $device->lifecycle_status }}">{{ str_replace('_', ' ', ucfirst($device->lifecycle_status)) }}</span></td>
                                <td>{{ ucfirst($device->connectivity_status) }}</td>
                                <td>{{ $device->last_seen_at ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">{{ request()->filled('search') ? 'No products match your search.' : 'No products are assigned in your access scope.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="pagination">{{ $devices->links() }}</div>
            </div>

            <h2 class="section-title">Recent shutter alerts</h2>
            <div class="table-wrap">
                @include('components.table-search', ['searchId' => 'portal-alerts', 'placeholder' => 'Search recent alerts…', 'hint' => 'Search recent alerts'])
                <table>
                    <thead><tr><th>Product</th><th>Alert</th><th>Severity</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                        @forelse ($recentAlerts as $alert)
                            <tr>
                                <td>{{ $alert->device_uid }}</td>
                                <td>{{ $alert->title }}</td>
                                <td>{{ ucfirst($alert->severity) }}</td>
                                <td><span class="badge {{ $alert->status }}">{{ ucfirst($alert->status) }}</span></td>
                                <td>{{ $alert->triggered_at }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">{{ request()->filled('search') ? 'No alerts match your search.' : 'No alerts for products in your access scope.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
