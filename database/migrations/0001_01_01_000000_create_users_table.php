<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MIGRATION_KEY = 'shop_guard_foundation';
    private const OWNERSHIP_TABLE = 'shop_guard_migration_ownership';

    public function up(): void
    {
        $ownershipTableCreated = !Schema::hasTable(self::OWNERSHIP_TABLE);
        if ($ownershipTableCreated) {
            Schema::create(self::OWNERSHIP_TABLE, function (Blueprint $table): void {
                $table->string('migration_key', 120);
                $table->string('table_name', 120);
                $table->unique(['migration_key', 'table_name']);
            });
            $this->recordOwnedTable(self::OWNERSHIP_TABLE);
        }

        $rolesCreated = $this->createOwnedTable('roles', function (Blueprint $table): void {
            $table->bigIncrements('role_id');
            $table->string('role_code', 50)->unique();
            $table->string('role_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->timestampTz('created_at')->useCurrent();
        });

        $this->createOwnedTable('companies', function (Blueprint $table): void {
            $table->bigIncrements('company_id');
            $table->string('company_code', 50)->unique();
            $table->string('company_name', 200);
            $table->string('legal_name', 250)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();
        });

        $this->createOwnedTable('users', function (Blueprint $table): void {
            $table->bigIncrements('user_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('parent_user_id')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->string('full_name', 200);
            $table->string('email')->nullable()->unique();
            $table->string('mobile', 30)->nullable()->unique();
            $table->text('password_hash')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('mobile_verified_at')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['company_id', 'role_id']);
            $table->index('parent_user_id');
            $table->foreign('company_id')->references('company_id')->on('companies')->nullOnDelete();
            $table->foreign('parent_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('role_id')->references('role_id')->on('roles');
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('user_id')->on('users')->nullOnDelete();
        });

        if ($rolesCreated) {
            DB::table('roles')->insert([
                ['role_code' => 'SUPER_ADMIN', 'role_name' => 'Super Admin', 'description' => 'Full platform access', 'is_system_role' => true],
                ['role_code' => 'COMPANY_ADMIN', 'role_name' => 'Company Admin', 'description' => 'Company-scoped administration', 'is_system_role' => true],
                ['role_code' => 'DEALER', 'role_name' => 'Dealer', 'description' => 'Dealer account and assigned products', 'is_system_role' => true],
                ['role_code' => 'SUB_DEALER', 'role_name' => 'Sub-Dealer', 'description' => 'Sub-dealer account and assigned products', 'is_system_role' => true],
                ['role_code' => 'END_CUSTOMER', 'role_name' => 'End Customer', 'description' => 'Shop and assigned shutter products', 'is_system_role' => true],
                ['role_code' => 'SUPPORT_OPERATOR', 'role_name' => 'Support Operator', 'description' => 'Read-only platform operations', 'is_system_role' => true],
            ]);
        }

        $this->createOwnedTable('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        $this->createOwnedTable('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        $this->dropOwnedTables([
            'sessions',
            'password_reset_tokens',
            'users',
            'companies',
            'roles',
        ], true);
    }

    private function createOwnedTable(string $tableName, Closure $definition): bool
    {
        if (Schema::hasTable($tableName)) {
            return false;
        }

        Schema::create($tableName, $definition);
        $this->recordOwnedTable($tableName);

        return true;
    }

    private function recordOwnedTable(string $tableName): void
    {
        DB::table(self::OWNERSHIP_TABLE)->insertOrIgnore([
            'migration_key' => self::MIGRATION_KEY,
            'table_name' => $tableName,
        ]);
    }

    private function dropOwnedTables(array $orderedTables, bool $dropOwnershipTable = false): void
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

        if (
            $dropOwnershipTable
            && in_array(self::OWNERSHIP_TABLE, $ownedTables, true)
            && DB::table(self::OWNERSHIP_TABLE)->doesntExist()
        ) {
            Schema::drop(self::OWNERSHIP_TABLE);
        }
    }
};
