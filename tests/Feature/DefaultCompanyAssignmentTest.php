<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DefaultCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DefaultCompanyAssignmentTest extends TestCase
{
    public function test_admin_created_dealer_hierarchy_accounts_and_shops_use_default_company(): void
    {
        DB::beginTransaction();

        try {
            $companyId = app(DefaultCompany::class)->requireId();
            $roles = DB::table('roles')->pluck('role_id', 'role_code');
            $suffix = Str::lower(Str::random(10));
            $admin = User::create([
                'role_id' => $roles['SUPER_ADMIN'],
                'full_name' => 'Default Company Test Admin '.$suffix,
                'email' => 'default-admin-'.$suffix.'@example.test',
                'password_hash' => Hash::make('ShopGuardTest!123'),
                'status' => 'active',
            ]);

            $this->actingAs($admin);
            $parentId = null;
            $customerId = null;
            foreach ([
                'DEALER' => 'Dealer',
                'SUB_DEALER' => 'Sub Dealer',
                'END_CUSTOMER' => 'Shop User',
            ] as $roleCode => $label) {
                $email = Str::lower(Str::slug($label)).'-'.$suffix.'@example.test';
                $this->post(route('master-admin.users.store'), [
                    'full_name' => $label.' '.$suffix,
                    'email' => $email,
                    'role_id' => $roles[$roleCode],
                    'parent_user_id' => $parentId,
                    'password' => 'ShopGuardTest!123',
                    'password_confirmation' => 'ShopGuardTest!123',
                    'status' => 'active',
                ])->assertRedirect(route('master-admin.users.index'));

                $created = DB::table('users')->where('email', $email)->firstOrFail();
                $this->assertSame($companyId, (int) $created->company_id);
                $parentId = (int) $created->user_id;
                if ($roleCode === 'END_CUSTOMER') {
                    $customerId = $parentId;
                }
            }

            $this->post(route('master-admin.shops.store'), [
                'customer_user_id' => $customerId,
                'shop_name' => 'Default Company Shop '.$suffix,
                'shop_code' => 'DEFAULT_'.$suffix,
                'timezone' => 'Asia/Kolkata',
                'status' => 'active',
            ])->assertRedirect(route('master-admin.shops.index'));

            $this->assertDatabaseHas('shops', [
                'shop_code' => 'DEFAULT_'.$suffix,
                'company_id' => $companyId,
                'customer_user_id' => $customerId,
            ]);
        } finally {
            DB::rollBack();
        }
    }
}
