<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AlertManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $alerts = DB::table('alerts')
            ->join('devices', 'devices.device_id', '=', 'alerts.device_id')
            ->leftJoin('shops', 'shops.shop_id', '=', 'alerts.shop_id')
            ->leftJoin('users as acknowledged_users', 'acknowledged_users.user_id', '=', 'alerts.acknowledged_by')
            ->select(
                'alerts.*',
                'devices.device_uid',
                'shops.shop_name',
                'acknowledged_users.full_name as acknowledged_by_name',
            );
        TableSearch::apply($alerts, $search, [
            'alerts.title', 'alerts.message', 'alerts.alert_type', 'alerts.severity',
            'alerts.status', 'devices.device_uid', 'shops.shop_name', 'acknowledged_users.full_name',
        ]);

        return view('master-admin.alerts.index', [
            'alerts' => $alerts->orderByDesc('alerts.triggered_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function acknowledge(Request $request, int $alert, AdminAuditLogger $audit): RedirectResponse
    {
        $current = DB::table('alerts')->where('alert_id', $alert)->firstOrFail();

        if ($current->status !== 'open') {
            return back()->withErrors(['alert' => 'Only open alerts can be acknowledged.']);
        }

        DB::table('alerts')->where('alert_id', $alert)->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $request->user()->getAuthIdentifier(),
            'acknowledged_at' => now(),
        ]);
        $audit->record(
            $request,
            'acknowledged',
            'alert',
            $alert,
            (array) $current,
            ['status' => 'acknowledged', 'acknowledged_by' => $request->user()->getAuthIdentifier()],
        );

        return back()->with('status', 'Alert acknowledged.');
    }

    public function resolve(Request $request, int $alert, AdminAuditLogger $audit): RedirectResponse
    {
        $current = DB::table('alerts')->where('alert_id', $alert)->firstOrFail();

        if (!in_array($current->status, ['open', 'acknowledged'], true)) {
            return back()->withErrors(['alert' => 'This alert is already resolved or closed.']);
        }

        DB::table('alerts')->where('alert_id', $alert)->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->getAuthIdentifier(),
            'resolved_at' => now(),
        ]);
        $audit->record(
            $request,
            'resolved',
            'alert',
            $alert,
            (array) $current,
            ['status' => 'resolved', 'resolved_by' => $request->user()->getAuthIdentifier()],
        );

        return back()->with('status', 'Alert resolved.');
    }
}
