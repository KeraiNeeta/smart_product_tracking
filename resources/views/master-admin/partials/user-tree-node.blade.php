<div class="admin-tree-node {{ ($depth ?? 0) > 0 ? 'nested' : '' }}">
    <details open>
        <summary>
            <span class="admin-tree-account">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 19a5.5 5.5 0 0 1 11 0"></path><path d="M16 6a3 3 0 0 1 0 5.6M17 14a5 5 0 0 1 3.5 4.5"></path></svg>
                <strong>{{ $node['account']->full_name }}</strong>
            </span>
            <span class="admin-tree-count">{{ count($node['devices']) }}</span>
        </summary>
        <div class="admin-tree-role">
            {{ $node['account']->role_name }}
            <span class="badge {{ $node['account']->status }}">{{ ucfirst($node['account']->status) }}</span>
        </div>
        <div class="admin-tree-content">
            @if ($node['account']->email)
                <span class="admin-tree-email" title="{{ $node['account']->email }}">{{ $node['account']->email }}</span>
            @endif
            @if ($node['account']->company_name)
                <span class="admin-tree-company">{{ $node['account']->company_name }}</span>
            @endif
            @if (count($node['devices']))
                <div class="admin-tree-devices" aria-label="Devices assigned to {{ $node['account']->full_name }}">
                    @foreach ($node['devices'] as $device)
                        <div class="admin-tree-device" title="{{ $device->shop_name ?: ($device->model ?: 'Shop Guard shutter sensor') }}">
                            <span class="admin-tree-device-name">
                                <span class="admin-tree-device-dot {{ $device->connectivity_status }}" aria-hidden="true"></span>
                                <strong>{{ $device->device_uid }}</strong>
                            </span>
                            <small>{{ ucfirst($device->connectivity_status) }}</small>
                        </div>
                    @endforeach
                </div>
            @endif
            @foreach ($node['children'] as $child)
                @include('master-admin.partials.user-tree-node', ['node' => $child, 'depth' => ($depth ?? 0) + 1])
            @endforeach
        </div>
    </details>
</div>
