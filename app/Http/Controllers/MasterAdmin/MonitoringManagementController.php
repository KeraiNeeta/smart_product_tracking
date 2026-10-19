<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AlertNotificationQueue;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonitoringManagementController extends Controller
{
    public function configurations(Request $request): View
    {
        $search = TableSearch::term($request);
        $configurations = DB::table('device_configurations')
            ->join('devices', 'devices.device_id', '=', 'device_configurations.device_id')
            ->leftJoin('users', 'users.user_id', '=', 'device_configurations.updated_by')
            ->select('device_configurations.*', 'devices.device_uid', 'users.full_name as updated_by_name');
        TableSearch::apply($configurations, $search, [
            'devices.device_uid', 'device_configurations.monitoring_mode',
            'device_configurations.manual_override', 'device_configurations.timezone',
            'device_configurations.config_version', 'users.full_name',
        ]);

        return view('master-admin.monitoring.configurations', [
            'configurations' => $configurations->orderByDesc('device_configurations.config_version')->paginate(20)->withQueryString(),
        ]);
    }

    public function createConfiguration(): View
    {
        return view('master-admin.monitoring.configuration-form', [
            'devices' => $this->devices(),
        ]);
    }

    public function storeConfiguration(
        Request $request,
        AdminAuditLogger $audit,
        AlertNotificationQueue $notifications,
    ): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,device_id'],
            'monitoring_mode' => ['required', Rule::in(['ARMED', 'DISARMED', 'SCHEDULED'])],
            'manual_override' => ['nullable', Rule::in(['ARMED', 'DISARMED'])],
            'timezone' => ['required', 'timezone'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'config_payload' => ['nullable', 'json'],
        ]);

        $configuration = DB::transaction(function () use ($request, $data, $audit, $notifications): array {
            $device = DB::table('devices')->where('device_id', $data['device_id'])->lockForUpdate()->firstOrFail();
            $latest = DB::table('device_configurations')
                ->where('device_id', $device->device_id)
                ->orderByDesc('config_version')
                ->lockForUpdate()
                ->first();
            $version = ($latest?->config_version ?? 0) + 1;
            $previousState = $latest
                ? ($latest->manual_override ?: $latest->monitoring_mode)
                : 'DISARMED';
            $newState = $data['manual_override'] ?? $data['monitoring_mode'];
            $new = [
                'device_id' => $device->device_id,
                'monitoring_mode' => $data['monitoring_mode'],
                'manual_override' => $data['manual_override'] ?? null,
                'schedule_enabled' => $request->boolean('schedule_enabled'),
                'timezone' => $data['timezone'],
                'config_version' => $version,
                'hooter_enabled' => $request->boolean('hooter_enabled'),
                'alert_on_open' => $request->boolean('alert_on_open'),
                'alert_on_close' => $request->boolean('alert_on_close'),
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'updated_by' => $request->user()->getAuthIdentifier(),
                'updated_at' => now(),
                'acknowledged_version' => null,
                'acknowledged_at' => null,
                'config_payload' => $data['config_payload'] ?: '{}',
            ];
            $configId = DB::table('device_configurations')->insertGetId($new, 'config_id');
            $commandId = DB::table('device_commands')->insertGetId([
                'device_id' => $device->device_id,
                'command_type' => 'SYNC_CONFIG',
                'requested_payload' => json_encode(['config_id' => $configId, 'config_version' => $version], JSON_THROW_ON_ERROR),
                'status' => 'pending',
                'requested_by' => $request->user()->getAuthIdentifier(),
                'requested_at' => now(),
                'expires_at' => now()->addMinutes(5),
            ], 'command_id');
            $audit->record(
                $request,
                'configuration_created',
                'device_configuration',
                $configId,
                after: $new,
                companyId: $device->company_id,
            );
            $audit->record(
                $request,
                'configuration_sync_requested',
                'device_command',
                $commandId,
                after: ['device_id' => $device->device_id, 'config_version' => $version, 'status' => 'pending'],
                companyId: $device->company_id,
            );

            $notificationCount = 0;
            if (
                in_array($newState, ['ARMED', 'DISARMED'], true)
                && $newState !== $previousState
            ) {
                $eventType = $newState;
                $title = $newState === 'ARMED' ? 'Shutter armed' : 'Shutter disarmed';
                $message = 'Shutter device '.$device->device_uid.' was '
                    .strtolower($newState).' by '.$request->user()->full_name.'.';
                $occurredAt = now();
                $assignment = DB::table('device_assignments')
                    ->where('device_id', $device->device_id)
                    ->where('is_current', true)
                    ->first();
                $eventId = DB::table('device_events')->insertGetId([
                    'device_id' => $device->device_id,
                    'event_type' => $eventType,
                    'event_occurred_at' => $occurredAt,
                    'server_received_at' => $occurredAt,
                    'monitoring_state' => $newState,
                    'severity' => 'info',
                    'payload' => json_encode([
                        'source' => 'admin_configuration',
                        'previous_state' => $previousState,
                        'new_state' => $newState,
                        'changed_by' => $request->user()->getAuthIdentifier(),
                    ], JSON_THROW_ON_ERROR),
                    'processing_status' => 'processed',
                ], 'event_id');
                $alertId = DB::table('alerts')->insertGetId([
                    'device_id' => $device->device_id,
                    'shop_id' => $assignment?->shop_id,
                    'source_event_id' => $eventId,
                    'alert_type' => $eventType,
                    'severity' => 'info',
                    'title' => $title,
                    'message' => $message,
                    'status' => 'resolved',
                    'triggered_at' => $occurredAt,
                    'resolved_by' => $request->user()->getAuthIdentifier(),
                    'resolved_at' => $occurredAt,
                    'context' => json_encode([
                        'previous_state' => $previousState,
                        'new_state' => $newState,
                        'changed_by' => $request->user()->getAuthIdentifier(),
                    ], JSON_THROW_ON_ERROR),
                ], 'alert_id');

                $notificationCount = $notifications->queueForCompanyUsers(
                    $alertId,
                    (int) $device->device_id,
                    (int) $device->company_id,
                    $eventType,
                    ['title' => $title, 'message' => $message],
                );
            }

            return ['version' => $version, 'notification_count' => $notificationCount];
        });

        return redirect()->route('master-admin.configurations.index')
            ->with(
                'status',
                'Configuration version '.$configuration['version'].' queued for device sync.'
                .($configuration['notification_count'] > 0
                    ? ' Notifications queued for '.$configuration['notification_count'].' recipient(s).'
                    : ''),
            );
    }

    public function schedules(Request $request): View
    {
        $search = TableSearch::term($request);
        $schedules = DB::table('device_schedules')
            ->join('devices', 'devices.device_id', '=', 'device_schedules.device_id')
            ->leftJoin('users', 'users.user_id', '=', 'device_schedules.updated_by')
            ->select('device_schedules.*', 'devices.device_uid', 'users.full_name as updated_by_name');
        TableSearch::apply($schedules, $search, [
            'device_schedules.schedule_name', 'devices.device_uid',
            'device_schedules.timezone', 'device_schedules.priority',
            'users.full_name',
        ]);

        return view('master-admin.monitoring.schedules', [
            'schedules' => $schedules
                ->orderBy('devices.device_uid')
                ->orderBy('device_schedules.priority')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function createSchedule(): View
    {
        return view('master-admin.monitoring.schedule-form', [
            'devices' => $this->devices(),
            'schedule' => null,
        ]);
    }

    public function storeSchedule(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validatedSchedule($request);
        $record = $this->scheduleRecord($request, $data);
        $scheduleId = DB::table('device_schedules')->insertGetId($record, 'schedule_id');
        $audit->record($request, 'schedule_created', 'device_schedule', $scheduleId, after: $record);

        return redirect()->route('master-admin.schedules.index')
            ->with('status', 'Monitoring schedule created successfully.');
    }

    public function editSchedule(int $schedule): View
    {
        $record = DB::table('device_schedules')->where('schedule_id', $schedule)->firstOrFail();
        $record->weekdays = $this->parseWeekdays($record->weekdays);

        return view('master-admin.monitoring.schedule-form', [
            'devices' => $this->devices(),
            'schedule' => $record,
        ]);
    }

    public function updateSchedule(Request $request, int $schedule, AdminAuditLogger $audit): RedirectResponse
    {
        $current = DB::table('device_schedules')->where('schedule_id', $schedule)->firstOrFail();
        $data = $this->validatedSchedule($request);
        $record = $this->scheduleRecord($request, $data, false);

        DB::table('device_schedules')->where('schedule_id', $schedule)->update($record);
        $audit->record($request, 'schedule_updated', 'device_schedule', $schedule, (array) $current, $record);

        return redirect()->route('master-admin.schedules.index')
            ->with('status', 'Monitoring schedule updated successfully.');
    }

    public function destroySchedule(Request $request, int $schedule, AdminAuditLogger $audit): RedirectResponse
    {
        $record = DB::table('device_schedules')->where('schedule_id', $schedule)->firstOrFail();
        DB::table('device_schedules')->where('schedule_id', $schedule)->delete();
        $audit->record($request, 'schedule_deleted', 'device_schedule', $schedule, (array) $record);

        return redirect()->route('master-admin.schedules.index')
            ->with('status', 'Monitoring schedule deleted successfully.');
    }

    private function devices()
    {
        return DB::table('devices')
            ->whereNull('deleted_at')
            ->orderBy('device_uid')
            ->get(['device_id', 'device_uid', 'model']);
    }

    private function validatedSchedule(Request $request): array
    {
        return $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,device_id'],
            'schedule_name' => ['required', 'string', 'max:150'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['required', 'integer', 'between:1,7'],
            'timezone' => ['required', 'timezone'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'priority' => ['required', 'integer', 'between:0,32767'],
        ]);
    }

    private function scheduleRecord(Request $request, array $data, bool $creating = true): array
    {
        $record = [
            'device_id' => $data['device_id'],
            'schedule_name' => $data['schedule_name'],
            'is_enabled' => $request->boolean('is_enabled'),
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'weekdays' => '{'.implode(',', array_unique(array_map('intval', $data['weekdays']))).'}',
            'timezone' => $data['timezone'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to' => $data['valid_to'] ?? null,
            'priority' => $data['priority'],
            'updated_by' => $request->user()->getAuthIdentifier(),
            'updated_at' => now(),
        ];

        if ($creating) {
            $record['created_by'] = $request->user()->getAuthIdentifier();
            $record['created_at'] = now();
        }

        return $record;
    }

    private function parseWeekdays(string|array|null $weekdays): array
    {
        if (is_array($weekdays)) {
            return array_map('intval', $weekdays);
        }

        return array_map('intval', explode(',', trim((string) $weekdays, '{}')));
    }
}
