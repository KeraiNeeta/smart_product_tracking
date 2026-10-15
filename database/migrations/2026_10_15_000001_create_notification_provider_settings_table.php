<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_provider_settings')) {
            return;
        }

        Schema::create('notification_provider_settings', function (Blueprint $table): void {
            $table->bigIncrements('provider_setting_id');
            $table->string('channel', 20)->unique();
            $table->string('provider', 40);
            $table->boolean('is_enabled')->default(false);
            $table->text('encrypted_settings');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->foreign('updated_by')->references('user_id')->on('users')->nullOnDelete();
        });

        if (Schema::hasTable('shop_guard_migration_ownership')) {
            DB::table('shop_guard_migration_ownership')->insertOrIgnore([
                'migration_key' => 'notification_provider_settings',
                'table_name' => 'notification_provider_settings',
            ]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('shop_guard_migration_ownership')
            && DB::table('shop_guard_migration_ownership')
                ->where('migration_key', 'notification_provider_settings')
                ->where('table_name', 'notification_provider_settings')
                ->exists()
        ) {
            Schema::dropIfExists('notification_provider_settings');
            DB::table('shop_guard_migration_ownership')
                ->where('migration_key', 'notification_provider_settings')
                ->where('table_name', 'notification_provider_settings')
                ->delete();
        }
    }
};
