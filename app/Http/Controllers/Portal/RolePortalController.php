<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceMonitoringControl;
use App\Services\UserNotificationInbox;
use App\Services\PortalDeviceScope;
use App\Support\TableSearch;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolePortalController extends Controller
{
    private const PORTAL_ROLES = [
        'COMPANY_ADMIN',
        'DEALER',
        'SUB_DEALER',
        'END_CUSTOMER',
        'SUPPORT_OPERATOR',
    ];

    public function dashboard(Request $request, UserNotificationInbox $inbox): View|RedirectResponse
    {
        $user = $this->portalUser();
        if ($user instanceof RedirectResponse) {
            return $user;
        }
        $this->validatePortalRole($user);
        $devices = $this->visibleDevices($user);
        $isDealer = $user->role->role_code === 'DEALER';
        $isEndCustomer = $user->role->role_code === 'END_CUSTOMER';
        $deviceIds = (clone $devices)->select('devices.device_id');
        $latestConfigurations = DB::table('device_configurations')
            ->select('device_id')
            ->selectRaw('MAX(config_version) as latest_config_version')
            ->groupBy('device_id');
        $search = TableSearch::term($request);
        $deviceRows = clone $devices;
        $deviceRows
            ->leftJoinSub($latestConfigurations, 'latest_configurations', function ($join): void {
                $join->on('latest_configurations.device_id', '=', 'devices.device_id');
            })
            ->leftJoin('device_configurations as current_configuration', function ($join): void {
                $join->on('current_configuration.device_id', '=', 'devices.device_id')
                    ->on('current_configuration.config_version', '=', 'latest_configurations.latest_config_version');
            })
            ->addSelect(DB::raw("COALESCE(current_configuration.manual_override, current_configuration.monitoring_mode, 'DISARMED') as monitoring_state"))
            ->addSelect([
                'command_pending' => DB::table('device_commands')
                    ->selectRaw('1')
                    ->whereColumn('device_commands.device_id', 'devices.device_id')
                    ->whereIn('device_commands.status', ['pending', 'sent'])
                    ->where(function ($query): void {
                        $query->where('device_commands.expires_at', '>', now())
                            ->orWhere(function ($legacyQuery): void {
                                $legacyQuery->whereNull('device_commands.expires_at')
                                    ->where('device_commands.requested_at', '>', now()->subMinutes(5));
                            });
                    })
                    ->limit(1),
            ]);
        TableSearch::apply($deviceRows, $search, [
            'devices.device_uid', 'devices.model', 'devices.lifecycle_status',
            'devices.connectivity_status', 'companies.company_name',
            'owners.full_name', 'shops.shop_name',
        ]);
        $recentAlerts = DB::table('alerts')
            ->join('devices', 'devices.device_id', '=', 'alerts.device_id')
            ->whereIn('alerts.device_id', $deviceIds)
            ->select('alerts.alert_id', 'alerts.title', 'alerts.severity', 'alerts.status', 'alerts.triggered_at', 'devices.device_uid');
        TableSearch::apply($recentAlerts, $search, [
            'devices.device_uid', 'alerts.title', 'alerts.severity', 'alerts.status',
        ]);

        return view('portal.dashboard', [
            'roleName' => $user->role->role_name,
            'unreadNotificationCount' => $inbox->unreadCount($user),
            'isDealer' => $isDealer,
            'isEndCustomer' => $isEndCustomer,
            'dealerHierarchy' => $isDealer
                ? app(PortalDeviceScope::class)->dealerHierarchy($user, $devices)
                : [],
            'deviceCount' => (clone $devices)->count('devices.device_id'),
            'assignedCount' => (clone $devices)->whereIn('devices.lifecycle_status', ['assigned', 'active'])->count('devices.device_id'),
            'openAlertCount' => DB::table('alerts')
                ->whereIn('device_id', $deviceIds)
                ->whereIn('status', ['open', 'acknowledged'])
                ->count(),
            'devices' => $deviceRows
                ->orderBy('devices.device_uid')
                ->paginate(15, ['*'], 'devices_page')
                ->withQueryString(),
            'recentAlerts' => $recentAlerts->orderByDesc('alerts.triggered_at')->limit(8)->get(),
        ]);
    }

    public function setMonitoringState(Request $request, int $device, DeviceMonitoringControl $control): RedirectResponse
    {
        $user = $this->portalUser();
        if ($user instanceof RedirectResponse) {
            return $user;
        }
        if ($user->role?->role_code !== 'END_CUSTOMER') {
            abort(403);
        }

        $data = $request->validate([
            'state' => ['required', Rule::in(['ARMED', 'DISARMED'])],
        ]);

        $result = $control->requestState($request, $user, $device, $data['state']);

        $message = $result['changed']
            ? 'The '.$result['state'].' command has been queued. The device state will update after it confirms the change.'
            : 'This device is already '.$result['state'].'.';

        return redirect()->route('portal.dashboard')->with('status', $message);
    }

    public function notifications(Request $request, UserNotificationInbox $inbox): View|RedirectResponse
    {
        $user = $this->portalUser();
        if ($user instanceof RedirectResponse) {
            return $user;
        }
        $this->validatePortalRole($user);
        $search = TableSearch::term($request);
        $notifications = $inbox->query($user)
            ->leftJoin('user_notification_reads as notification_reads', function ($join) use ($user): void {
                $join->on('notification_reads.notification_job_id', '=', 'notification_jobs.notification_job_id')
                    ->where('notification_reads.user_id', '=', $user->user_id);
            })
            ->orderByDesc('notification_jobs.created_at')
            ->select(
                'notification_jobs.notification_job_id',
                'notification_jobs.channel',
                'notification_jobs.status as delivery_status',
                'notification_jobs.created_at',
                'notification_jobs.payload',
                'devices.device_uid',
                'notification_reads.read_at',
            );
        TableSearch::apply($notifications, $search, [
            'devices.device_uid', 'notification_jobs.channel',
            'notification_jobs.status', 'notification_jobs.created_at',
            'notification_jobs.payload',
        ]);
        $notifications = $notifications->paginate(20)->withQueryString();

        return view('portal.notifications', [
            'roleName' => $user->role->role_name,
            'unreadNotificationCount' => $inbox->unreadCount($user),
            'notifications' => $notifications,
        ]);
    }

    public function markNotificationsRead(UserNotificationInbox $inbox): RedirectResponse
    {
        $user = $this->portalUser();
        if ($user instanceof RedirectResponse) {
            return $user;
        }
        $this->validatePortalRole($user);
        $count = $inbox->markAllRead($user);

        return redirect()->route('portal.notifications')
            ->with('status', $count > 0 ? 'Notifications marked as read.' : 'There are no unread notifications.');
    }

    private function portalUser(): User|RedirectResponse
    {
        $user = request()->user()->loadMissing('role');
        if ($user->status !== 'active') {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('master-admin.login')
                ->withErrors(['email' => 'Your account is not active.']);
        }
        if ($user->role?->role_code === 'SUPER_ADMIN') {
            return redirect()->route('master-admin.dashboard');
        }

        return $user;
    }

    private function validatePortalRole(User $user): void
    {
        if (!in_array($user->role?->role_code, self::PORTAL_ROLES, true)) {
            abort(403, 'No portal is configured for this account role.');
        }
    }

    private function visibleDevices(User $user): Builder
    {
        return app(PortalDeviceScope::class)->visibleDevices($user);
    }
}
