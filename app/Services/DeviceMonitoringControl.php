<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeviceMonitoringControl
{
    public function requestState(Request $request, User $user, int $deviceId, string $newState): array
    {
        return DB::transaction(function () use ($request, $user, $deviceId, $newState): array {
            $scope = app(PortalDeviceScope::class);
            $device = DB::table('devices')
                ->where('devices.device_id', $deviceId)
                ->whereIn('devices.device_id', $scope->visibleDeviceIds($user))
                ->lockForUpdate()
                ->first();
            if (!$device) {
                abort(404);
            }

            $expiredAt = now()->subMinutes(5);
            DB::table('device_commands')
                ->where('device_id', $deviceId)
                ->whereIn('status', ['pending', 'sent'])
                ->where(function ($query) use ($expiredAt): void {
                    $query->where('expires_at', '<=', now())
                        ->orWhere(function ($legacyQuery) use ($expiredAt): void {
                            $legacyQuery->whereNull('expires_at')
                                ->where('requested_at', '<=', $expiredAt);
                        });
                })
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Command expired before the device acknowledged it.',
                ]);

            $pendingCommands = DB::table('device_commands')
                ->where('device_id', $deviceId)
                ->whereIn('status', ['pending', 'sent'])
                ->where(function ($query): void {
                    $query->where('expires_at', '>', now())
                        ->orWhere(function ($legacyQuery): void {
                            $legacyQuery->whereNull('expires_at')
                                ->where('requested_at', '>', now()->subMinutes(5));
                        });
                })
                ->lockForUpdate()
                ->get();

            $latest = DB::table('device_configurations')
                ->where('device_id', $deviceId)
                ->orderByDesc('config_version')
                ->lockForUpdate()
                ->first();
            $previousState = $latest
                ? ($latest->manual_override ?: $latest->monitoring_mode)
                : 'DISARMED';

            if ($pendingCommands->isNotEmpty() && $previousState === $newState) {
                throw ValidationException::withMessages([
                    'state' => 'This state change is already waiting for the device.',
                ]);
            }
            if ($previousState === $newState) {
                return ['changed' => false, 'state' => $newState];
            }

            if ($pendingCommands->isNotEmpty()) {
                DB::table('device_commands')
                    ->whereIn('command_id', $pendingCommands->pluck('command_id'))
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Superseded by a newer monitoring-state request.',
                    ]);
            }

            $version = ($latest?->config_version ?? 0) + 1;
            $now = now();
            $configuration = [
                'device_id' => $deviceId,
                'monitoring_mode' => $latest?->monitoring_mode ?? 'DISARMED',
                'manual_override' => $newState,
                'schedule_enabled' => $latest?->schedule_enabled ?? false,
                'timezone' => $latest?->timezone ?? 'Asia/Kolkata',
                'config_version' => $version,
                'hooter_enabled' => $latest?->hooter_enabled ?? true,
                'alert_on_open' => $latest?->alert_on_open ?? true,
                'alert_on_close' => $latest?->alert_on_close ?? false,
                'effective_from' => $now,
                'effective_to' => null,
                'updated_by' => $user->user_id,
                'updated_at' => $now,
                'acknowledged_version' => null,
                'acknowledged_at' => null,
                'config_payload' => $latest?->config_payload ?? '{}',
            ];
            $configId = DB::table('device_configurations')->insertGetId($configuration, 'config_id');
            $commandId = DB::table('device_commands')->insertGetId([
                'device_id' => $deviceId,
                'command_type' => 'SYNC_CONFIG',
                'requested_payload' => json_encode([
                    'config_id' => $configId,
                    'config_version' => $version,
                ], JSON_THROW_ON_ERROR),
                'status' => 'pending',
                'requested_by' => $user->user_id,
                'requested_at' => $now,
                'expires_at' => $now->copy()->addMinutes(5),
            ], 'command_id');

            app(AdminAuditLogger::class)->record(
                $request,
                'monitoring_state_requested',
                'device_command',
                $commandId,
                before: ['monitoring_state' => $previousState],
                after: ['monitoring_state' => $newState, 'config_version' => $version, 'status' => 'pending'],
                companyId: $device->company_id,
            );

            return ['changed' => true, 'state' => $newState];
        });
    }
}
