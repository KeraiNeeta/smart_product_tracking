<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserNotificationInbox;
use App\Services\PortalDeviceScope;
use App\Support\TableSearch;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $deviceIds = (clone $devices)->select('devices.device_id');
        $search = TableSearch::term($request);
        $deviceRows = clone $devices;
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
