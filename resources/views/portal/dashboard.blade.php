@extends('portal.layout')

@section('title', 'My dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <h1>{{ $isEndCustomer ? 'Your shutter' : 'Shutter dashboard' }}</h1>
            <p class="muted">{{ $isEndCustomer ? 'Check your device and control its monitoring status.' : 'Your Shop Guard devices and recent activity.' }}</p>
        </div>
        @if ($isDealer)
            <button type="button" class="button secondary" data-sidebar-toggle="dealer-network" aria-controls="dealer-network-sidebar" aria-expanded="true">Hide user tree</button>
        @endif
    </div>

    @if ($isEndCustomer)
        @php($customerDevice = $devices->first())
        @if ($customerDevice)
            <section class="customer-device-card" aria-labelledby="customer-device-title">
                <div class="customer-device-top">
                    <span class="customer-eyebrow">YOUR SECURITY DEVICE</span>
                    <span class="device-connection {{ strtolower($customerDevice->connectivity_status) }}">
                        <span class="connection-dot"></span>{{ ucfirst($customerDevice->connectivity_status) }}
                    </span>
                </div>
                <div class="customer-device-identity">
                    <div class="customer-device-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 7.5h16M5 7.5v11h14v-11M7 7.5V5h10v2.5M8 11v4m4-4v4m4-4v4"></path></svg>
                    </div>
                    <div>
                        <h2 id="customer-device-title">{{ $customerDevice->device_uid }}</h2>
                        <p>{{ $customerDevice->model ?: 'Shop Guard shutter device' }}</p>
                    </div>
                </div>

                <div class="customer-device-meta">
                    <div><span>Serial number</span><strong>{{ $customerDevice->serial_number ?: 'Not available' }}</strong></div>
                    <div><span>Last connection</span><strong>{{ $customerDevice->last_seen_at ?: 'Not reported yet' }}</strong></div>
                </div>

                <div class="monitoring-panel {{ strtolower($customerDevice->monitoring_state) }}">
                    <div class="monitoring-state-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.2 7 10 4.1-1.8 7-5.4 7-10V6l-7-3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                    </div>
                    <div class="monitoring-state-copy">
                        <span>{{ $customerDevice->command_pending ? 'REQUEST IN PROGRESS' : 'MONITORING STATUS' }}</span>
                        <strong>{{ ucfirst(strtolower($customerDevice->monitoring_state)) }}</strong>
                        <small>
                            @if ($customerDevice->command_pending)
                                Waiting for your device to confirm this change.
                            @else
                                {{ $customerDevice->monitoring_state === 'ARMED' ? 'Your shutter monitoring is on.' : 'Your shutter monitoring is off.' }}
                            @endif
                        </small>
                    </div>
                    @if ($customerDevice->command_pending)
                        <span class="request-pending"><span></span>Syncing</span>
                    @endif
                </div>

                <div class="customer-device-actions" aria-label="Shutter controls">
                    <form method="POST" action="{{ route('portal.devices.monitoring-state', $customerDevice->device_id) }}">
                        @csrf
                        <input type="hidden" name="state" value="ARMED">
                        <button class="arm-button {{ $customerDevice->monitoring_state === 'ARMED' ? 'is-selected' : '' }}" type="submit" aria-pressed="{{ $customerDevice->monitoring_state === 'ARMED' ? 'true' : 'false' }}" @disabled($customerDevice->command_pending && $customerDevice->monitoring_state === 'ARMED')>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.2 7 10 4.1-1.8 7-5.4 7-10V6l-7-3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                            Arm shutter
                        </button>
                    </form>
                    <form method="POST" action="{{ route('portal.devices.monitoring-state', $customerDevice->device_id) }}">
                        @csrf
                        <input type="hidden" name="state" value="DISARMED">
                        <button class="disarm-button {{ $customerDevice->monitoring_state === 'DISARMED' ? 'is-selected' : '' }}" type="submit" aria-pressed="{{ $customerDevice->monitoring_state === 'DISARMED' ? 'true' : 'false' }}" @disabled($customerDevice->command_pending && $customerDevice->monitoring_state === 'DISARMED')>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.2 7 10 4.1-1.8 7-5.4 7-10V6l-7-3Z"></path><path d="M9 12h6"></path></svg>
                            Disarm shutter
                        </button>
                    </form>
                </div>
                <p class="customer-device-note">Your request is sent to the device. The status updates when the device responds.</p>
            </section>
        @else
            <section class="customer-empty-card">
                <span class="customer-empty-icon" aria-hidden="true">!</span>
                <h2>No device linked yet</h2>
                <p>There is no shutter assigned to this account. Contact your Shop Guard administrator for help.</p>
            </section>
        @endif
    @else
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
                        <thead><tr><th>Device</th><th>Serial number</th><th>Shop</th><th>Company</th><th>Owner</th><th>Lifecycle</th><th>Connection</th><th>Last seen</th></tr></thead>
                        <tbody>
                            @forelse ($devices as $device)
                                <tr>
                                    <td><strong>{{ $device->device_uid }}</strong><div class="muted">{{ $device->model ?: 'Shutter sensor' }}</div></td>
                                    <td>{{ $device->serial_number ?: '—' }}</td>
                                    <td>{{ $device->shop_name ?: '—' }}</td>
                                    <td>{{ $device->company_name ?: '—' }}</td>
                                    <td>{{ $device->owner_name ?: 'Unassigned' }}</td>
                                    <td><span class="badge {{ $device->lifecycle_status }}">{{ str_replace('_', ' ', ucfirst($device->lifecycle_status)) }}</span></td>
                                    <td>{{ ucfirst($device->connectivity_status) }}</td>
                                    <td>{{ $device->last_seen_at ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8">{{ request()->filled('search') ? 'No products match your search.' : 'No products are assigned in your access scope.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="pagination">{{ $devices->links() }}</div>
                </div>
            </div>
        </div>
    @endif

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
@endsection
