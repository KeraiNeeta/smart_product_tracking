<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlertNotificationQueue
{
    public function queueForCompanyUsers(
        int $alertId,
        int $deviceId,
        int $companyId,
        string $alertType,
        array $message,
    ): int {
        $users = DB::table('users')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('user_id')
            ->get(['user_id', 'full_name', 'email', 'mobile']);

        if ($users->isEmpty()) {
            return 0;
        }

        $rules = DB::table('notification_rules')
            ->where('is_enabled', true)
            ->where('alert_type', $alertType)
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->where(function ($query) use ($deviceId): void {
                $query->whereNull('device_id')->orWhere('device_id', $deviceId);
            })
            ->orderBy('priority')
            ->orderBy('notification_rule_id')
            ->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        $pushTokens = DB::table('push_tokens')
            ->where('is_active', true)
            ->whereIn('user_id', $users->pluck('user_id'))
            ->orderBy('push_token_id')
            ->get(['user_id', 'token'])
            ->groupBy('user_id');

        $jobs = [];

        foreach ($users as $user) {
            foreach ($rules as $rule) {
                if ($rule->user_id !== null && (int) $rule->user_id !== (int) $user->user_id) {
                    continue;
                }

                foreach ($this->recipientsFor($rule, $user, $pushTokens) as $recipient) {
                    $key = $rule->channel.'|'.$recipient;
                    if (!isset($jobs[$key])) {
                        $jobs[$key] = [
                            'channel' => $rule->channel,
                            'recipient' => $recipient,
                            'user_ids' => [],
                            'user_names' => [],
                        ];
                    }

                    $jobs[$key]['user_ids'][$user->user_id] = (int) $user->user_id;
                    $jobs[$key]['user_names'][$user->user_id] = $user->full_name;
                }
            }
        }

        foreach ($jobs as $job) {
            $userIds = array_values($job['user_ids']);
            DB::table('notification_jobs')->insert([
                'alert_id' => $alertId,
                'channel' => $job['channel'],
                'recipient' => $job['recipient'],
                'template_code' => 'DEVICE_EVENT',
                'payload' => json_encode([
                    ...$message,
                    'user_ids' => $userIds,
                    'user_names' => array_values($job['user_names']),
                    'user_id' => count($userIds) === 1 ? $userIds[0] : null,
                    'device_id' => $deviceId,
                    'event_type' => $alertType,
                ], JSON_THROW_ON_ERROR),
                'status' => 'queued',
                'attempt_count' => 0,
                'next_attempt_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return count($jobs);
    }

    private function recipientsFor(object $rule, object $user, Collection $pushTokens): array
    {
        if ($rule->recipient_value !== null && trim($rule->recipient_value) !== '') {
            return [trim($rule->recipient_value)];
        }

        return match ($rule->channel) {
            'EMAIL' => $user->email ? [$user->email] : [],
            'SMS', 'WHATSAPP', 'VOICE_CALL' => $user->mobile ? [$user->mobile] : [],
            'PUSH' => ($pushTokens->get($user->user_id) ?? collect())
                ->pluck('token')
                ->filter()
                ->values()
                ->all(),
            default => [],
        };
    }
}
