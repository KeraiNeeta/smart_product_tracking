<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notification_reads')) {
            return;
        }

        Schema::create('user_notification_reads', function (Blueprint $table): void {
            $table->bigIncrements('user_notification_read_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('notification_job_id');
            $table->timestampTz('read_at')->useCurrent();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('notification_job_id')->references('notification_job_id')->on('notification_jobs')->cascadeOnDelete();
            $table->unique(['user_id', 'notification_job_id'], 'user_notification_reads_user_job_unique');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_reads');
    }
};
