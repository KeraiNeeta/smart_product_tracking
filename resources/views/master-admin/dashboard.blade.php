@extends('master-admin.layout')

@section('title', 'Dashboard')

@section('content')
    @php
        $onlinePercent = $metrics['devices'] > 0
            ? (int) round(($metrics['online_devices'] / $metrics['devices']) * 100)
            : 0;
    @endphp

    <div class="admin-dashboard-workspace" data-sidebar-layout data-collapsed="false">
        <aside class="admin-user-tree" id="admin-user-tree-sidebar" aria-label="Platform user hierarchy" data-sidebar-panel="admin-user-tree">
            <div class="admin-tree-heading">
                <div>
                    <span class="section-eyebrow">PLATFORM ACCOUNTS</span>
                    <h2>User hierarchy</h2>
                </div>
                <span class="admin-tree-total">{{ number_format($metrics['users']) }}</span>
            </div>
            <p class="admin-tree-caption">Accounts, their parent-child relationships, and assigned devices.</p>
            <div class="admin-tree-list">
                @forelse ($userHierarchy as $account)
                    @include('master-admin.partials.user-tree-node', ['node' => $account])
                @empty
                    <p class="admin-tree-empty">No accounts found.</p>
                @endforelse
            </div>
            <a class="admin-tree-link" href="{{ route('master-admin.users.index') }}">Manage all users <span aria-hidden="true">↗</span></a>
        </aside>

        <div class="admin-dashboard-main">
    <div class="tree-toggle-row">
        <button type="button" class="button secondary" data-sidebar-toggle="admin-user-tree" aria-controls="admin-user-tree-sidebar" aria-expanded="true">Hide user tree</button>
    </div>
    <section class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <div class="dashboard-kicker"><span class="status-pulse"></span> SHOP GUARD CONTROL CENTER</div>
            <h1>Your shutter operations,<br>at a glance.</h1>
            <p>Monitor device health, stay ahead of alerts, and keep every location secure.</p>
            <div class="hero-actions">
                <a class="button hero-primary" href="{{ route('master-admin.devices.create') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Register a device
                </a>
                <a class="button hero-secondary" href="{{ route('master-admin.alerts.index') }}">Review alerts <span aria-hidden="true">→</span></a>
            </div>
        </div>
        <div class="dashboard-hero-aside">
            <div class="hero-date">{{ now()->format('l, F j') }}</div>
            <div class="fleet-card">
                <div class="fleet-card-heading">
                    <span class="fleet-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/><path d="M7 4v16M17 4v16"/></svg>
                    </span>
                    <span><strong>Device health</strong><small>Current fleet status</small></span>
                </div>
                <div class="fleet-health">
                    <div class="fleet-ring" style="--health: {{ $onlinePercent }}%">
                        <span><strong>{{ $onlinePercent }}%</strong><small>online</small></span>
                    </div>
                    <div class="fleet-numbers">
                        <div><span class="health-dot online"></span><span>Online</span><strong>{{ number_format($metrics['online_devices']) }}</strong></div>
                        <div><span class="health-dot offline"></span><span>Offline</span><strong>{{ number_format($metrics['offline_devices']) }}</strong></div>
                        <div><span class="health-dot unknown"></span><span>Unknown</span><strong>{{ number_format($metrics['unknown_devices']) }}</strong></div>
                    </div>
                </div>
                <a href="{{ route('master-admin.devices.index') }}" class="fleet-link">Open device inventory <span aria-hidden="true">↗</span></a>
            </div>
        </div>
        <div class="hero-decoration" aria-hidden="true"><span></span><span></span><span></span></div>
    </section>

    <section class="dashboard-section">
        <div class="dashboard-section-heading">
            <div>
                <span class="section-eyebrow">AT A GLANCE</span>
                <h2>Operational overview</h2>
            </div>
            <span class="section-caption">Live totals from your platform</span>
        </div>
        <div class="overview-grid">
            <a class="overview-card" href="{{ route('master-admin.devices.index') }}">
                <span class="overview-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M9 7h6M9 11h6M9 15h.01M12 15h.01M15 15h.01M9 18h6"/></svg></span>
                <span class="overview-copy"><span>Total devices</span><strong>{{ number_format($metrics['devices']) }}</strong><small>{{ number_format($metrics['assigned_devices']) }} assigned to customers</small></span>
                <span class="overview-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="overview-card" href="{{ route('master-admin.alerts.index') }}">
                <span class="overview-icon {{ $metrics['open_alerts'] ? 'icon-amber' : 'icon-green' }}" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg></span>
                <span class="overview-copy"><span>Open alerts</span><strong>{{ number_format($metrics['open_alerts']) }}</strong><small>{{ number_format($metrics['alerts_today']) }} triggered today</small></span>
                <span class="overview-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="overview-card" href="{{ route('master-admin.operations.index', 'commands') }}">
                <span class="overview-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h13M13 6l6 6-6 6"/><path d="M4 5v14"/></svg></span>
                <span class="overview-copy"><span>Commands in progress</span><strong>{{ number_format($metrics['pending_commands']) }}</strong><small>Awaiting device response</small></span>
                <span class="overview-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="overview-card" href="{{ route('master-admin.operations.index', 'notification-jobs') }}">
                <span class="overview-icon {{ $metrics['failed_notifications'] ? 'icon-red' : 'icon-violet' }}" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="m4 7 8 6 8-6M17 16h.01"/></svg></span>
                <span class="overview-copy"><span>Notification queue</span><strong>{{ number_format($metrics['queued_notifications']) }}</strong><small>{{ number_format($metrics['failed_notifications']) }} failed deliveries</small></span>
                <span class="overview-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
    </section>

    <section class="dashboard-insights">
        <article class="insight-panel trend-panel">
            <div class="insight-heading">
                <div>
                    <span class="section-eyebrow">LAST 7 DAYS</span>
                    <h2>Alert activity</h2>
                    <p>Daily alerts across your monitored devices.</p>
                </div>
                <a class="quiet-link" href="{{ route('master-admin.alerts.index') }}">All alerts <span aria-hidden="true">↗</span></a>
            </div>
            <div class="trend-summary">
                <strong>{{ number_format($alertTrend->sum('total')) }}</strong>
                <span>alerts this week</span>
                <span class="trend-today">{{ number_format($metrics['alerts_today']) }} today</span>
            </div>
            <div class="trend-chart">
                <div class="trend-y-labels" aria-hidden="true"><span>{{ $alertTrend->max('total') }}</span><span>{{ (int) floor($alertTrend->max('total') / 2) }}</span><span>0</span></div>
                <svg viewBox="0 0 600 120" role="img" aria-label="Alert counts for the last seven days">
                    <defs>
                        <linearGradient id="alert-area-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#21a77d" stop-opacity=".22"/>
                            <stop offset="100%" stop-color="#21a77d" stop-opacity=".015"/>
                        </linearGradient>
                    </defs>
                    <path class="trend-grid-line" d="M0 26H600M0 67H600M0 108H600"/>
                    <polygon class="trend-area" points="0,108 {{ $trendPoints }} 600,108"/>
                    <polyline class="trend-line" points="{{ $trendPoints }}"/>
                    @foreach ($alertTrend as $index => $day)
                        <circle class="trend-point" cx="{{ $index * 100 }}" cy="{{ 108 - (int) round(($day['total'] / max(1, (int) $alertTrend->max('total'))) * 82) }}" r="3.5">
                            <title>{{ $day['date'] }}: {{ $day['total'] }} {{ $day['total'] === 1 ? 'alert' : 'alerts' }}</title>
                        </circle>
                    @endforeach
                </svg>
                <div class="trend-x-labels">
                    @foreach ($alertTrend as $day)
                        <span><strong>{{ $day['label'] }}</strong><small>{{ $day['date'] }}</small></span>
                    @endforeach
                </div>
            </div>
        </article>

        <aside class="insight-panel quick-panel">
            <div class="insight-heading">
                <div>
                    <span class="section-eyebrow">SHORTCUTS</span>
                    <h2>Quick actions</h2>
                    <p>Jump straight into common tasks.</p>
                </div>
            </div>
            <nav class="quick-links" aria-label="Quick actions">
                <a href="{{ route('master-admin.devices.create') }}"><span class="quick-link-icon"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><span><strong>Register device</strong><small>Add to your inventory</small></span><span class="quick-chevron" aria-hidden="true">→</span></a>
                <a href="{{ route('master-admin.users.create') }}"><span class="quick-link-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.4-3.5 2.7-5.5 6.5-5.5s6.1 2 6.5 5.5M19 8v6M16 11h6"/></svg></span><span><strong>Invite a user</strong><small>Create an account and role</small></span><span class="quick-chevron" aria-hidden="true">→</span></a>
                <a href="{{ route('master-admin.configurations.create') }}"><span class="quick-link-icon"><svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h16"/><path d="m6 15 4-4 3 2 6-7"/></svg></span><span><strong>Configure monitoring</strong><small>Update device protection</small></span><span class="quick-chevron" aria-hidden="true">→</span></a>
                <a href="{{ route('master-admin.notification-rules.index') }}"><span class="quick-link-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8M8 12h5"/></svg></span><span><strong>Notification settings</strong><small>Manage alerts and channels</small></span><span class="quick-chevron" aria-hidden="true">→</span></a>
            </nav>
        </aside>
    </section>

    <section class="dashboard-section platform-section">
        <div class="dashboard-section-heading">
            <div>
                <span class="section-eyebrow">YOUR WORKSPACE</span>
                <h2>Platform at a glance</h2>
            </div>
            <span class="section-caption">People and locations under management</span>
        </div>
        <div class="workspace-strip">
            <a href="{{ route('master-admin.companies.index') }}"><span class="workspace-icon"><svg viewBox="0 0 24 24"><path d="M4 20V7l8-4 8 4v13M2.5 20h19M8 9h.01M12 9h.01M16 9h.01M8 13h.01M12 13h.01M16 13h.01"/></svg></span><span><small>Companies</small><strong>{{ number_format($metrics['companies']) }}</strong></span><span class="workspace-arrow">↗</span></a>
            <a href="{{ route('master-admin.users.index') }}"><span class="workspace-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.4-3.5 2.7-5.5 6.5-5.5s6.1 2 6.5 5.5M16 5.2a3.5 3.5 0 0 1 0 6.7M18 14.7c2.1.7 3.3 2.4 3.5 5.3"/></svg></span><span><small>Active users</small><strong>{{ number_format($metrics['users']) }}</strong></span><span class="workspace-arrow">↗</span></a>
            <a href="{{ route('master-admin.shops.index') }}"><span class="workspace-icon"><svg viewBox="0 0 24 24"><path d="M3 10v10h18V10M2 10l2-6h16l2 6M9 20v-6h6v6"/></svg></span><span><small>Shops</small><strong>{{ number_format($metrics['shops']) }}</strong></span><span class="workspace-arrow">↗</span></a>
            <a href="{{ route('master-admin.operations.index', 'inventory') }}"><span class="workspace-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg></span><span><small>Assigned devices</small><strong>{{ number_format($metrics['assigned_devices']) }}</strong></span><span class="workspace-arrow">↗</span></a>
        </div>
    </section>

    <section class="dashboard-section recent-alerts-section">
        <div class="dashboard-section-heading">
            <div>
                <span class="section-eyebrow">NEEDS YOUR ATTENTION</span>
                <h2>Recent alerts</h2>
                <p class="section-description">The latest events reported by Shop Guard devices.</p>
            </div>
            <a class="button secondary" href="{{ route('master-admin.alerts.index') }}">View all alerts <span aria-hidden="true">→</span></a>
        </div>
        <div class="panel table-panel">
            @include('components.table-search', ['searchId' => 'recent-alerts', 'placeholder' => 'Search recent alerts…', 'hint' => 'Search recent alerts'])
            <table>
                <thead><tr><th>Alert</th><th>Device</th><th>Severity</th><th>Status</th><th>Triggered</th></tr></thead>
                <tbody>
                    @forelse ($recentAlerts as $alert)
                        <tr>
                            <td><span class="alert-row-title"><span class="alert-indicator {{ strtolower($alert->severity) }}"></span>{{ $alert->title }}</span></td>
                            <td><code>{{ $alert->device_uid }}</code></td>
                            <td><span class="severity-label {{ strtolower($alert->severity) }}">{{ ucfirst($alert->severity) }}</span></td>
                            <td><span class="badge {{ $alert->status }}">{{ ucfirst($alert->status) }}</span></td>
                            <td>{{ $alert->triggered_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ request()->filled('search') ? 'No recent alerts match your search.' : 'No alerts received yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
        </div>
    </div>
@endsection
