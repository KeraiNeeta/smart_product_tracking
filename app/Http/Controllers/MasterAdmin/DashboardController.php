<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminUserHierarchy;
use App\Support\TableSearch;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminUserHierarchy $hierarchy): View
    {
        $search = TableSearch::term($request);
        $recentAlerts = DB::table('alerts')
            ->leftJoin('devices', 'devices.device_id', '=', 'alerts.device_id')
            ->select('alerts.alert_id', 'alerts.title', 'alerts.severity', 'alerts.status', 'alerts.triggered_at', 'devices.device_uid');
        TableSearch::apply($recentAlerts, $search, [
            'alerts.title', 'alerts.severity', 'alerts.status', 'devices.device_uid',
        ]);

        $today = CarbonImmutable::today();
        $trendStart = $today->subDays(6);
        $alertCountsByDay = DB::table('alerts')
            ->whereBetween('triggered_at', [$trendStart->startOfDay(), $today->endOfDay()])
            ->selectRaw('DATE(triggered_at) as alert_date, COUNT(*) as total')
            ->groupByRaw('DATE(triggered_at)')
            ->pluck('total', 'alert_date');
        $alertTrend = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($today, $alertCountsByDay): array {
                $date = $today->subDays($daysAgo);

                return [
                    'label' => $date->format('D'),
                    'date' => $date->format('M j'),
                    'total' => (int) ($alertCountsByDay->get($date->toDateString()) ?? 0),
                ];
            });
        $maxTrend = max(1, (int) $alertTrend->max('total'));
        $trendPoints = $alertTrend
            ->values()
            ->map(fn (array $day, int $index): string => ($index * 100).','.(108 - (int) round(($day['total'] / $maxTrend) * 82)))
            ->implode(' ');

        return view('master-admin.dashboard', [
            'metrics' => [
                'companies' => DB::table('companies')->count(),
                'users' => DB::table('users')->whereNull('deleted_at')->count(),
                'shops' => DB::table('shops')->count(),
                'devices' => DB::table('devices')->whereNull('deleted_at')->count(),
                'online_devices' => DB::table('devices')->whereNull('deleted_at')->where('connectivity_status', 'online')->count(),
                'offline_devices' => DB::table('devices')->whereNull('deleted_at')->where('connectivity_status', 'offline')->count(),
                'unknown_devices' => DB::table('devices')->whereNull('deleted_at')->whereNotIn('connectivity_status', ['online', 'offline'])->count(),
                'assigned_devices' => DB::table('device_assignments')->where('is_current', true)->count(),
                'open_alerts' => DB::table('alerts')->whereIn('status', ['open', 'acknowledged'])->count(),
                'alerts_today' => DB::table('alerts')->whereDate('triggered_at', $today->toDateString())->count(),
                'pending_commands' => DB::table('device_commands')->whereIn('status', ['pending', 'sent'])->count(),
                'queued_notifications' => DB::table('notification_jobs')->where('status', 'queued')->count(),
                'failed_notifications' => DB::table('notification_jobs')->where('status', 'failed')->count(),
            ],
            'userHierarchy' => $hierarchy->tree(),
            'alertTrend' => $alertTrend,
            'trendPoints' => $trendPoints,
            'recentAlerts' => $recentAlerts->orderByDesc('alerts.triggered_at')->limit(8)->get(),
        ]);
    }
}
