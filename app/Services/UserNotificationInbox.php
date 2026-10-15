<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UserNotificationInbox
{
    public function query(User $user): Builder
    {
        $visibleDevices = app(PortalDeviceScope::class)->visibleDeviceIds($user);

        return DB::table('notification_jobs')
            ->join('alerts', 'alerts.alert_id', '=', 'notification_jobs.alert_id')
            ->join('devices', 'devices.device_id', '=', 'alerts.device_id')
            ->whereIn('alerts.device_id', $visibleDevices)
            ->where(function ($audience) use ($user): void {
                $audience
                    ->whereRaw(
                        'notification_jobs.payload @> ?::jsonb',
                        [json_encode(['user_ids' => [(int) $user->user_id]], JSON_THROW_ON_ERROR)],
                    )
                    ->orWhereRaw(
                        'notification_jobs.payload @> ?::jsonb',
                        [json_encode(['user_id' => (int) $user->user_id], JSON_THROW_ON_ERROR)],
                    );
            });
    }

    public function unreadCount(User $user): int
    {
        return $this->query($user)
            ->whereNotExists(function (Builder $read) use ($user): void {
                $read->selectRaw('1')
                    ->from('user_notification_reads')
                    ->whereColumn('user_notification_reads.notification_job_id', 'notification_jobs.notification_job_id')
                    ->where('user_notification_reads.user_id', $user->user_id);
            })
            ->count('notification_jobs.notification_job_id');
    }

    public function markAllRead(User $user): int
    {
        $now = now();
        $rows = $this->query($user)
            ->whereNotExists(function (Builder $read) use ($user): void {
                $read->selectRaw('1')
                    ->from('user_notification_reads')
                    ->whereColumn('user_notification_reads.notification_job_id', 'notification_jobs.notification_job_id')
                    ->where('user_notification_reads.user_id', $user->user_id);
            })
            ->pluck('notification_jobs.notification_job_id')
            ->map(fn ($jobId): array => [
                'user_id' => $user->user_id,
                'notification_job_id' => $jobId,
                'read_at' => $now,
            ])
            ->all();

        if ($rows === []) {
            return 0;
        }

        DB::table('user_notification_reads')->insertOrIgnore($rows);

        return count($rows);
    }
}
