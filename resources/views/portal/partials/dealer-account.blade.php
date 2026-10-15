<div class="network-account {{ ($depth ?? 0) > 0 ? 'nested' : '' }}">
    <details open>
        <summary>
            <span class="network-account-label">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 19a5.5 5.5 0 0 1 11 0"></path><path d="M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 4.5"></path></svg>
                <strong>{{ $branch['account']->full_name }}</strong>
            </span>
            <span class="network-account-count">{{ count($branch['devices']) }}</span>
        </summary>
        <div class="network-account-role">{{ str_replace('_', ' ', ucwords(strtolower($branch['account']->role_name))) }}</div>
        <div class="network-account-content">
            @if ($branch['account']->email)
                <span class="network-account-email" title="{{ $branch['account']->email }}">{{ $branch['account']->email }}</span>
            @endif
            @if (count($branch['devices']))
                <div class="network-device-list" aria-label="Devices assigned to {{ $branch['account']->full_name }}">
                    @foreach ($branch['devices'] as $device)
                        <div class="network-device" title="{{ $device->shop_name ?: ($device->model ?: 'Shop Guard shutter sensor') }}">
                            <span class="network-device-name">
                                <span class="network-device-dot {{ $device->connectivity_status }}" aria-hidden="true"></span>
                                <strong>{{ $device->device_uid }}</strong>
                            </span>
                            <small>{{ ucfirst($device->connectivity_status) }}</small>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="network-empty">No devices assigned.</p>
            @endif
            @foreach ($branch['children'] as $child)
                @include('portal.partials.dealer-account', ['branch' => $child, 'depth' => ($depth ?? 0) + 1])
            @endforeach
        </div>
    </details>
</div>
