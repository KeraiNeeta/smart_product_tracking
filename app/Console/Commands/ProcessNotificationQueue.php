<?php

namespace App\Console\Commands;

use App\Services\NotificationDeliveryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessNotificationQueue extends Command
{
    protected $signature = 'notifications:process {--limit=100 : Maximum number of due notifications to process}';

    protected $description = 'Deliver due Shop Guard notification jobs through configured providers';

    public function handle(NotificationDeliveryService $delivery): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000],
        ]);
        if ($limit === false) {
            $this->error('The limit must be an integer between 1 and 1000.');

            return self::INVALID;
        }

        $now = now();
        $jobIds = DB::table('notification_jobs')
            ->where('status', 'queued')
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
            })
            ->orderBy('notification_job_id')
            ->limit($limit)
            ->pluck('notification_job_id');

        $sent = 0;
        $failed = 0;
        foreach ($jobIds as $jobId) {
            $result = DB::transaction(function () use ($jobId, $delivery): ?bool {
                $job = DB::table('notification_jobs')
                    ->where('notification_job_id', $jobId)
                    ->lockForUpdate()
                    ->first();
                if (
                    !$job
                    || $job->status !== 'queued'
                    || ($job->next_attempt_at !== null && Carbon::parse($job->next_attempt_at)->isFuture())
                ) {
                    return null;
                }

                return $delivery->deliver((int) $jobId);
            });

            if ($result === true) {
                $sent++;
            } elseif ($result === false) {
                $failed++;
            }
        }

        $this->info("Processed {$jobIds->count()} due jobs: {$sent} sent, {$failed} failed or retried.");

        return self::SUCCESS;
    }
}
