<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NotificationRuleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $rules = DB::table('notification_rules')
            ->leftJoin('companies', 'companies.company_id', '=', 'notification_rules.company_id')
            ->leftJoin('users', 'users.user_id', '=', 'notification_rules.user_id')
            ->leftJoin('devices', 'devices.device_id', '=', 'notification_rules.device_id')
            ->select('notification_rules.*', 'companies.company_name', 'users.full_name', 'devices.device_uid');
        TableSearch::apply($rules, $search, [
            'notification_rules.alert_type', 'notification_rules.channel',
            'notification_rules.recipient_value', 'notification_rules.priority',
            'companies.company_name', 'users.full_name', 'devices.device_uid',
        ]);

        return view('master-admin.notifications.rules', [
            'rules' => $rules->orderBy('notification_rules.priority')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('master-admin.notifications.rule-form', $this->formOptions());
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['created_by'] = $request->user()->getAuthIdentifier();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $ruleId = DB::table('notification_rules')->insertGetId($data, 'notification_rule_id');
        $audit->record($request, 'created', 'notification_rule', $ruleId, after: $data, companyId: $data['company_id'] ?? null);

        return redirect()->route('master-admin.notification-rules.index')
            ->with('status', 'Notification rule created successfully.');
    }

    public function edit(int $notificationRule): View
    {
        return view('master-admin.notifications.rule-form', [
            ...$this->formOptions(),
            'rule' => DB::table('notification_rules')->where('notification_rule_id', $notificationRule)->firstOrFail(),
        ]);
    }

    public function update(Request $request, int $notificationRule, AdminAuditLogger $audit): RedirectResponse
    {
        $current = DB::table('notification_rules')->where('notification_rule_id', $notificationRule)->firstOrFail();
        $data = $this->validated($request);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['updated_at'] = now();

        DB::table('notification_rules')->where('notification_rule_id', $notificationRule)->update($data);
        $audit->record($request, 'updated', 'notification_rule', $notificationRule, (array) $current, $data, $data['company_id'] ?? null);

        return redirect()->route('master-admin.notification-rules.index')
            ->with('status', 'Notification rule updated successfully.');
    }

    public function destroy(Request $request, int $notificationRule, AdminAuditLogger $audit): RedirectResponse
    {
        $record = DB::table('notification_rules')->where('notification_rule_id', $notificationRule)->firstOrFail();
        DB::table('notification_rules')->where('notification_rule_id', $notificationRule)->delete();
        $audit->record($request, 'deleted', 'notification_rule', $notificationRule, (array) $record, companyId: $record->company_id);

        return redirect()->route('master-admin.notification-rules.index')
            ->with('status', 'Notification rule deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'rule' => null,
            'companies' => DB::table('companies')->orderBy('company_name')->get(),
            'users' => DB::table('users')->whereNull('deleted_at')->orderBy('full_name')->get(['user_id', 'full_name', 'company_id']),
            'devices' => DB::table('devices')->whereNull('deleted_at')->orderBy('device_uid')->get(['device_id', 'device_uid', 'company_id']),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,company_id'],
            'user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'device_id' => ['nullable', 'integer', 'exists:devices,device_id'],
            'alert_type' => ['required', 'string', 'max:60'],
            'channel' => ['required', Rule::in(['SMS', 'WHATSAPP', 'EMAIL', 'VOICE_CALL', 'PUSH'])],
            'recipient_value' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'between:0,32767'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
        ]);

        if (!$data['company_id'] && !$data['user_id'] && !$data['device_id']) {
            throw ValidationException::withMessages([
                'company_id' => 'Choose at least one company, user, or device scope.',
            ]);
        }

        return $data;
    }
}
