<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TableSearchTest extends TestCase
{
    public function test_admin_can_search_users_roles_and_inventory_across_server_pagination(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::where('email', 'admin@example.com')->firstOrFail();
            $this->actingAs($admin);

            $this->get(route('master-admin.users.index', [
                'search' => 'company-admin@demo.shopguard.local',
            ]))
                ->assertOk()
                ->assertSee('company-admin@demo.shopguard.local')
                ->assertDontSee('dealer@demo.shopguard.local');

            $this->get(route('master-admin.roles.index', ['search' => 'COMPANY_ADMIN']))
                ->assertOk()
                ->assertSee('COMPANY_ADMIN')
                ->assertDontSee('SUPPORT_OPERATOR');

            $this->get(route('master-admin.operations.index', [
                'module' => 'inventory',
                'search' => 'SG-DEMO-TRANSFER-001',
            ]))
                ->assertOk()
                ->assertSee('SG-DEMO-TRANSFER-001');
        } finally {
            DB::rollBack();
        }
    }
}
