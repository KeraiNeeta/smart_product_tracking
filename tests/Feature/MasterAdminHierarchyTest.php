<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterAdminHierarchyTest extends TestCase
{
    public function test_super_admin_dashboard_shows_hierarchical_users_and_their_devices(): void
    {
        DB::beginTransaction();

        try {
            $suffix = Str::lower(Str::random(10));
            $roles = DB::table('roles')->pluck('role_id', 'role_code');
            $companyId = DB::table('companies')->insertGetId([
                'company_code' => 'ADMIN_TREE_'.$suffix,
                'company_name' => 'Admin Tree Company '.$suffix,
                'status' => 'active',
            ], 'company_id');

            $createUser = function (string $role, string $key, ?int $parentId, ?int $userCompanyId) use ($roles, $suffix): User {
                return User::create([
                    'company_id' => $userCompanyId,
                    'parent_user_id' => $parentId,
                    'role_id' => $roles[$role],
                    'full_name' => 'Tree '.$key.' '.$suffix,
                    'email' => $key.'-'.$suffix.'@example.test',
                    'password_hash' => Hash::make('ShopGuardTest!123'),
                    'status' => 'active',
                ]);
            };

            $superAdmin = $createUser('SUPER_ADMIN', 'admin', null, null);
            $companyAdmin = $createUser('COMPANY_ADMIN', 'company', $superAdmin->user_id, $companyId);
            $dealer = $createUser('DEALER', 'dealer', $companyAdmin->user_id, $companyId);
            $subDealer = $createUser('SUB_DEALER', 'sub', $dealer->user_id, $companyId);
            $customer = $createUser('END_CUSTOMER', 'shop-user', $subDealer->user_id, $companyId);

            $deviceId = DB::table('devices')->insertGetId([
                'device_uid' => 'ADMIN-TREE-DEVICE-'.$suffix,
                'model' => 'Shop Guard shutter sensor',
                'company_id' => $companyId,
                'lifecycle_status' => 'active',
                'connectivity_status' => 'online',
            ], 'device_id');
            DB::table('device_assignments')->insert([
                'device_id' => $deviceId,
                'owner_user_id' => $customer->user_id,
                'assigned_by' => $companyAdmin->user_id,
                'assigned_at' => now(),
                'is_current' => true,
            ]);

            $this->actingAs($superAdmin)
                ->get(route('master-admin.dashboard'))
                ->assertOk()
                ->assertSee('User hierarchy')
                ->assertSee('Hide user tree')
                ->assertSee('Tree company '.$suffix)
                ->assertSee('Tree dealer '.$suffix)
                ->assertSee('Tree sub '.$suffix)
                ->assertSee('Tree shop-user '.$suffix)
                ->assertSee('ADMIN-TREE-DEVICE-'.$suffix)
                ->assertSee('Admin Tree Company '.$suffix);
        } finally {
            DB::rollBack();
        }
    }
}
