<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MIGRATION_KEY = 'shop_guard_domain';
    private const OWNERSHIP_TABLE = 'shop_guard_migration_ownership';

    public function up(): void
    {
        $this->createOwnedTable('shops', function (Blueprint $table): void {
            $table->bigIncrements('shop_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_user_id');
            $table->string('shop_name', 200);
            $table->string('shop_code', 80)->nullable()->unique();
            $table->string('address_line1', 250)->nullable();
            $table->string('address_line2', 250)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 80)->default('Asia/Kolkata');
            $table->string('contact_name', 200)->nullable();
            $table->string('contact_mobile', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->foreign('customer_user_id')->references('user_id')->on('users');
            $table->index('customer_user_id');
        });

        $this->createOwnedTable('devices', function (Blueprint $table): void {
            $table->bigIncrements('device_id');
            $table->string('device_uid', 120)->unique();
            $table->string('serial_number', 120)->nullable()->unique();
            $table->string('model', 100)->nullable();
            $table->string('firmware_version', 100)->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('manufacturing_batch', 100)->nullable();
            $table->string('lifecycle_status', 25)->default('in_stock');
            $table->string('connectivity_status', 20)->default('unknown');
            $table->timestampTz('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestampTz('installed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->index(['company_id', 'lifecycle_status']);
            $table->index('last_seen_at');
        });

        $assignmentsCreated = $this->createOwnedTable('device_assignments', function (Blueprint $table): void {
            $table->bigIncrements('assignment_id');
            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('owner_user_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('unassigned_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('owner_user_id')->references('user_id')->on('users');
            $table->foreign('shop_id')->references('shop_id')->on('shops');
            $table->foreign('assigned_by')->references('user_id')->on('users');
            $table->index(['owner_user_id', 'is_current']);
            $table->index(['shop_id', 'is_current']);
        });
        if ($assignmentsCreated && in_array(DB::connection()->getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX uq_device_current_assignment ON device_assignments (device_id) WHERE is_current = true');
        }

        $this->createOwnedTable('device_inventory_transactions', function (Blueprint $table): void {
            $table->bigIncrements('inventory_txn_id');
            $table->unsignedBigInteger('device_id');
            $table->string('transaction_type', 30);
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->unsignedBigInteger('from_company_id')->nullable();
            $table->unsignedBigInteger('to_company_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->timestampTz('performed_at')->useCurrent();
            $table->jsonb('metadata')->default('{}');
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('from_user_id')->references('user_id')->on('users');
            $table->foreign('to_user_id')->references('user_id')->on('users');
            $table->foreign('from_company_id')->references('company_id')->on('companies');
            $table->foreign('to_company_id')->references('company_id')->on('companies');
            $table->foreign('performed_by')->references('user_id')->on('users');
            $table->index(['device_id', 'performed_at']);
            $table->index(['to_user_id', 'performed_at']);
        });

        $this->createOwnedTable('device_configurations', function (Blueprint $table): void {
            $table->bigIncrements('config_id');
            $table->unsignedBigInteger('device_id');
            $table->string('monitoring_mode', 20)->default('DISARMED');
            $table->string('manual_override', 20)->nullable();
            $table->boolean('schedule_enabled')->default(false);
            $table->string('timezone', 80)->default('Asia/Kolkata');
            $table->integer('config_version');
            $table->boolean('hooter_enabled')->default(true);
            $table->boolean('alert_on_open')->default(true);
            $table->boolean('alert_on_close')->default(false);
            $table->timestampTz('effective_from')->useCurrent();
            $table->timestampTz('effective_to')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestampTz('updated_at')->useCurrent();
            $table->integer('acknowledged_version')->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->jsonb('config_payload')->default('{}');
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('updated_by')->references('user_id')->on('users');
            $table->unique(['device_id', 'config_version']);
            $table->index(['device_id', 'config_version']);
        });

        $schedulesCreated = $this->createOwnedTable('device_schedules', function (Blueprint $table): void {
            $table->bigIncrements('schedule_id');
            $table->unsignedBigInteger('device_id');
            $table->string('schedule_name', 150)->default('Default Schedule');
            $table->boolean('is_enabled')->default(true);
            $table->time('start_time');
            $table->time('end_time');
            $table->text('weekdays')->default('{1,2,3,4,5,6,7}');
            $table->string('timezone', 80)->default('Asia/Kolkata');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->smallInteger('priority')->default(100);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('created_by')->references('user_id')->on('users');
            $table->foreign('updated_by')->references('user_id')->on('users');
            $table->index(['device_id', 'is_enabled']);
        });
        if ($schedulesCreated && DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE device_schedules ALTER COLUMN weekdays DROP DEFAULT');
            DB::statement("ALTER TABLE device_schedules ALTER COLUMN weekdays TYPE smallint[] USING string_to_array(trim(both '{}' from weekdays), ',')::smallint[]");
            DB::statement('ALTER TABLE device_schedules ALTER COLUMN weekdays SET DEFAULT ARRAY[1,2,3,4,5,6,7]::smallint[]');
        }

        $this->createOwnedTable('device_events', function (Blueprint $table): void {
            $table->bigIncrements('event_id');
            $table->unsignedBigInteger('device_id');
            $table->uuid('event_uuid')->nullable()->unique();
            $table->unsignedBigInteger('device_sequence')->nullable();
            $table->string('event_type', 50);
            $table->timestampTz('event_occurred_at')->nullable();
            $table->timestampTz('server_received_at')->useCurrent();
            $table->string('shutter_state', 20)->nullable();
            $table->string('monitoring_state', 20)->nullable();
            $table->string('severity', 20)->default('info');
            $table->jsonb('payload')->default('{}');
            $table->string('processing_status', 20)->default('received');
            $table->text('processing_error')->nullable();
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->unique(['device_id', 'device_sequence']);
            $table->index(['device_id', 'server_received_at']);
            $table->index(['event_type', 'server_received_at']);
        });

        $this->createOwnedTable('device_heartbeats', function (Blueprint $table): void {
            $table->bigIncrements('heartbeat_id');
            $table->unsignedBigInteger('device_id');
            $table->timestampTz('reported_at')->nullable();
            $table->timestampTz('received_at')->useCurrent();
            $table->smallInteger('battery_percent')->nullable();
            $table->smallInteger('signal_strength')->nullable();
            $table->string('firmware_version', 100)->nullable();
            $table->jsonb('connectivity_details')->default('{}');
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->index(['device_id', 'received_at']);
        });

        $this->createOwnedTable('device_commands', function (Blueprint $table): void {
            $table->bigIncrements('command_id');
            $table->unsignedBigInteger('device_id');
            $table->uuid('command_uuid')->nullable()->unique();
            $table->string('command_type', 40);
            $table->jsonb('requested_payload')->default('{}');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestampTz('requested_at')->useCurrent();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('requested_by')->references('user_id')->on('users');
            $table->index(['device_id', 'status', 'requested_at']);
        });

        $this->createOwnedTable('alerts', function (Blueprint $table): void {
            $table->bigIncrements('alert_id');
            $table->uuid('alert_uuid')->nullable()->unique();
            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('source_event_id')->nullable();
            $table->string('alert_type', 60);
            $table->string('severity', 20)->default('warning');
            $table->string('title', 200);
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->timestampTz('triggered_at')->useCurrent();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->jsonb('context')->default('{}');
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('shop_id')->references('shop_id')->on('shops');
            $table->foreign('source_event_id')->references('event_id')->on('device_events');
            $table->foreign('acknowledged_by')->references('user_id')->on('users');
            $table->foreign('resolved_by')->references('user_id')->on('users');
            $table->index(['device_id', 'triggered_at']);
            $table->index(['shop_id', 'triggered_at']);
            $table->index(['status', 'triggered_at']);
        });

        $this->createOwnedTable('notification_rules', function (Blueprint $table): void {
            $table->bigIncrements('notification_rule_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('alert_type', 60);
            $table->string('channel', 20);
            $table->boolean('is_enabled')->default(true);
            $table->string('recipient_value', 255)->nullable();
            $table->smallInteger('priority')->default(100);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->foreign('created_by')->references('user_id')->on('users');
            $table->index(['alert_type', 'is_enabled', 'priority']);
        });

        $this->createOwnedTable('notification_jobs', function (Blueprint $table): void {
            $table->bigIncrements('notification_job_id');
            $table->unsignedBigInteger('alert_id');
            $table->string('channel', 20);
            $table->string('recipient', 255);
            $table->string('template_code', 100)->nullable();
            $table->jsonb('payload')->default('{}');
            $table->string('status', 20)->default('queued');
            $table->integer('attempt_count')->default(0);
            $table->timestampTz('next_attempt_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->foreign('alert_id')->references('alert_id')->on('alerts');
            $table->index(['status', 'next_attempt_at']);
        });

        $this->createOwnedTable('notification_logs', function (Blueprint $table): void {
            $table->bigIncrements('notification_log_id');
            $table->unsignedBigInteger('notification_job_id')->nullable();
            $table->unsignedBigInteger('alert_id');
            $table->string('channel', 20);
            $table->string('recipient_masked', 255)->nullable();
            $table->string('provider_name', 100)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('status', 20);
            $table->integer('attempt_no')->default(1);
            $table->jsonb('request_summary')->default('{}');
            $table->jsonb('response_summary')->default('{}');
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('attempted_at')->useCurrent();
            $table->timestampTz('delivered_at')->nullable();
            $table->foreign('notification_job_id')->references('notification_job_id')->on('notification_jobs');
            $table->foreign('alert_id')->references('alert_id')->on('alerts');
            $table->index(['alert_id', 'attempted_at']);
        });

        $this->createOwnedTable('push_tokens', function (Blueprint $table): void {
            $table->bigIncrements('push_token_id');
            $table->unsignedBigInteger('user_id');
            $table->string('platform', 20);
            $table->text('token')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_seen_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->index(['user_id', 'is_active']);
        });

        $this->createOwnedTable('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('audit_log_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->string('entity_id', 120)->nullable();
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign('actor_user_id')->references('user_id')->on('users');
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $this->dropOwnedTables([
            'audit_logs',
            'push_tokens',
            'notification_logs',
            'notification_jobs',
            'notification_rules',
            'alerts',
            'device_commands',
            'device_heartbeats',
            'device_events',
            'device_schedules',
            'device_configurations',
            'device_inventory_transactions',
            'device_assignments',
            'devices',
            'shops',
        ]);
    }

    private function createOwnedTable(string $tableName, Closure $definition): bool
    {
        if (Schema::hasTable($tableName)) {
            return false;
        }

        Schema::create($tableName, $definition);
        DB::table(self::OWNERSHIP_TABLE)->insertOrIgnore([
            'migration_key' => self::MIGRATION_KEY,
            'table_name' => $tableName,
        ]);

        return true;
    }

    private function dropOwnedTables(array $orderedTables): void
    {
        if (!Schema::hasTable(self::OWNERSHIP_TABLE)) {
            return;
        }

        $ownedTables = DB::table(self::OWNERSHIP_TABLE)
            ->where('migration_key', self::MIGRATION_KEY)
            ->pluck('table_name')
            ->all();

        foreach ($orderedTables as $tableName) {
            if (in_array($tableName, $ownedTables, true)) {
                Schema::dropIfExists($tableName);
            }
        }

        DB::table(self::OWNERSHIP_TABLE)->where('migration_key', self::MIGRATION_KEY)->delete();
    }
};
