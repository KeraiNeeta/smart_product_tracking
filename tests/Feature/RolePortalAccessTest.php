<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePortalAccessTest extends TestCase
{
    public function test_dealer_can_monitor_devices_across_multiple_sub_dealers_and_direct_customers(): void
    {
        DB::beginTransaction();

        try {
            $suffix = Str::lower(Str::random(10));
            $roles = DB::table('roles')->pluck('role_id', 'role_code');
            $companyId = DB::table('companies')->insertGetId([
                'company_code' => 'BRANCH_'.$suffix,
                'company_name' => 'Dealer Branch Scope '.$suffix,
                'status' => 'active',
            ], 'company_id');

            $createUser = function (string $role, string $key, ?int $parentId = null) use ($roles, $companyId, $suffix): User {
                return User::create([
                    'company_id' => $companyId,
                    'parent_user_id' => $parentId,
                    'role_id' => $roles[$role],
                    'full_name' => 'Branch '.$key.' '.$suffix,
                    'email' => $key.'-'.$suffix.'@example.test',
                    'password_hash' => Hash::make('ShopGuardTest!123'),
                    'status' => 'active',
                ]);
            };

            $companyAdmin = $createUser('COMPANY_ADMIN', 'admin');
            $dealer = $createUser('DEALER', 'dealer', $companyAdmin->user_id);
            $subDealerOne = $createUser('SUB_DEALER', 'sub-one', $dealer->user_id);
            $subDealerTwo = $createUser('SUB_DEALER', 'sub-two', $dealer->user_id);
            $customerOne = $createUser('END_CUSTOMER', 'customer-one', $subDealerOne->user_id);
            $customerTwo = $createUser('END_CUSTOMER', 'customer-two', $subDealerTwo->user_id);
            $directCustomer = $createUser('END_CUSTOMER', 'direct-customer', $dealer->user_id);

            $deviceOwners = [
                'BRANCH-SUB-ONE-'.$suffix => $subDealerOne,
                'BRANCH-CUSTOMER-ONE-'.$suffix => $customerOne,
                'BRANCH-SUB-TWO-'.$suffix => $subDealerTwo,
                'BRANCH-CUSTOMER-TWO-'.$suffix => $customerTwo,
                'BRANCH-DIRECT-CUSTOMER-'.$suffix => $directCustomer,
            ];

            foreach ($deviceOwners as $uid => $owner) {
                $deviceId = DB::table('devices')->insertGetId([
                    'device_uid' => $uid,
                    'model' => 'Shop Guard shutter sensor',
                    'company_id' => $companyId,
                    'lifecycle_status' => 'active',
                    'connectivity_status' => 'online',
                ], 'device_id');
                DB::table('device_assignments')->insert([
                    'device_id' => $deviceId,
                    'owner_user_id' => $owner->user_id,
                    'assigned_by' => $companyAdmin->user_id,
                    'assigned_at' => now(),
                    'is_current' => true,
                ]);
            }

            $dealerDashboard = $this->actingAs($dealer)
                ->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('My network')
                ->assertSee('dealer-sidebar')
                ->assertSee('Hide user tree')
                ->assertSee('Branch sub-one '.$suffix)
                ->assertSee('Branch sub-two '.$suffix)
                ->assertSee('Branch direct-customer '.$suffix)
                ->assertSee('Branch customer-one '.$suffix)
                ->assertSee('Branch customer-two '.$suffix)
                ->assertSee('BRANCH-SUB-ONE-'.$suffix)
                ->assertSee('BRANCH-CUSTOMER-ONE-'.$suffix)
                ->assertSee('BRANCH-SUB-TWO-'.$suffix)
                ->assertSee('BRANCH-CUSTOMER-TWO-'.$suffix)
                ->assertSee('BRANCH-DIRECT-CUSTOMER-'.$suffix);

            auth()->logout();
            $this->actingAs($subDealerOne)
                ->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('BRANCH-SUB-ONE-'.$suffix)
                ->assertSee('BRANCH-CUSTOMER-ONE-'.$suffix)
                ->assertDontSee('BRANCH-SUB-TWO-'.$suffix)
                ->assertDontSee('BRANCH-DIRECT-CUSTOMER-'.$suffix);

            auth()->logout();
            $this->actingAs($directCustomer)
                ->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('BRANCH-DIRECT-CUSTOMER-'.$suffix)
                ->assertDontSee('BRANCH-SUB-ONE-'.$suffix)
                ->assertDontSee('BRANCH-CUSTOMER-TWO-'.$suffix);
        } finally {
            DB::rollBack();
        }
    }

    public function test_role_logins_are_redirected_to_scoped_portals_and_notifications(): void
    {
        DB::beginTransaction();

        try {
            $suffix = Str::lower(Str::random(10));
            $roles = DB::table('roles')->pluck('role_id', 'role_code');
            $companyA = DB::table('companies')->insertGetId([
                'company_code' => 'PORTAL_A_'.$suffix,
                'company_name' => 'Portal Scope A',
                'status' => 'active',
            ], 'company_id');
            $companyB = DB::table('companies')->insertGetId([
                'company_code' => 'PORTAL_B_'.$suffix,
                'company_name' => 'Portal Scope B',
                'status' => 'active',
            ], 'company_id');

            $createUser = function (
                string $roleCode,
                string $name,
                ?int $companyId,
                ?int $parentUserId = null,
            ) use ($roles, $suffix): User {
                return User::create([
                    'company_id' => $companyId,
                    'parent_user_id' => $parentUserId,
                    'role_id' => $roles[$roleCode],
                    'full_name' => $name,
                    'email' => Str::slug($name).'-'.$suffix.'@example.test',
                    'mobile' => '+1555'.random_int(1000000000, 9999999999),
                    'password_hash' => Hash::make('ShopGuardTest!123'),
                    'status' => 'active',
                ]);
            };

            $superAdmin = $createUser('SUPER_ADMIN', 'Portal Test Super Admin', null);
            $companyAdmin = $createUser('COMPANY_ADMIN', 'Portal Test Company Admin', $companyA);
            $dealer = $createUser('DEALER', 'Portal Test Dealer', $companyA, $companyAdmin->user_id);
            $subDealer = $createUser('SUB_DEALER', 'Portal Test Sub Dealer', $companyA, $dealer->user_id);
            $shopUser = $createUser('END_CUSTOMER', 'Portal Test Shop User', $companyA, $subDealer->user_id);
            $support = $createUser('SUPPORT_OPERATOR', 'Portal Test Support', null);

            $shopId = DB::table('shops')->insertGetId([
                'company_id' => $companyA,
                'customer_user_id' => $shopUser->user_id,
                'shop_name' => 'Portal Test Shop',
                'shop_code' => 'PORTAL_SHOP_'.$suffix,
                'timezone' => 'Asia/Kolkata',
                'status' => 'active',
            ], 'shop_id');
            $deviceA = DB::table('devices')->insertGetId([
                'device_uid' => 'PORTAL-A-'.$suffix,
                'model' => 'Shop Guard shutter sensor',
                'company_id' => $companyA,
                'lifecycle_status' => 'active',
                'connectivity_status' => 'online',
            ], 'device_id');
            $deviceB = DB::table('devices')->insertGetId([
                'device_uid' => 'PORTAL-B-'.$suffix,
                'model' => 'Shop Guard shutter sensor',
                'company_id' => $companyB,
                'lifecycle_status' => 'in_stock',
                'connectivity_status' => 'unknown',
            ], 'device_id');
            DB::table('device_assignments')->insert([
                'device_id' => $deviceA,
                'owner_user_id' => $shopUser->user_id,
                'shop_id' => $shopId,
                'assigned_by' => $companyAdmin->user_id,
                'assigned_at' => now(),
                'is_current' => true,
            ]);
            $alertId = DB::table('alerts')->insertGetId([
                'device_id' => $deviceA,
                'shop_id' => $shopId,
                'alert_type' => 'ARMED',
                'severity' => 'info',
                'title' => 'Shutter armed',
                'message' => 'Portal test armed notification',
                'status' => 'resolved',
                'triggered_at' => now(),
            ], 'alert_id');
            DB::table('notification_jobs')->insert([
                'alert_id' => $alertId,
                'channel' => 'EMAIL',
                'recipient' => $shopUser->email,
                'template_code' => 'DEVICE_EVENT',
                'payload' => json_encode([
                    'title' => 'Shutter armed',
                    'message' => 'Portal test armed notification',
                    'user_ids' => [(int) $shopUser->user_id],
                    'device_id' => $deviceA,
                    'event_type' => 'ARMED',
                ], JSON_THROW_ON_ERROR),
                'status' => 'queued',
                'next_attempt_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([
                [$companyAdmin, 'PORTAL-A-'.$suffix, 'PORTAL-B-'.$suffix],
                [$dealer, 'PORTAL-A-'.$suffix, 'PORTAL-B-'.$suffix],
                [$subDealer, 'PORTAL-A-'.$suffix, 'PORTAL-B-'.$suffix],
                [$shopUser, 'PORTAL-A-'.$suffix, 'PORTAL-B-'.$suffix],
                [$support, 'PORTAL-A-'.$suffix, 'PORTAL-B-'.$suffix],
            ] as [$user, $visibleDevice, $hiddenDevice]) {
                $this->post(route('master-admin.login.submit'), [
                    'email' => $user->email,
                    'password' => 'ShopGuardTest!123',
                ])->assertRedirect(route('portal.dashboard'));

                $this->get(route('master-admin.login'))->assertRedirect(route('portal.dashboard'));
                $dashboard = $this->get(route('portal.dashboard'))->assertOk();
                $dashboard->assertSee($visibleDevice);
                if ($user->role_id === $support->role_id) {
                    $dashboard->assertSee($hiddenDevice);
                } else {
                    $dashboard->assertDontSee($hiddenDevice);
                }

                $this->get(route('master-admin.companies.index'))
                    ->assertRedirect(route('portal.dashboard'));
                $this->get(route('portal.dashboard'))->assertOk();
                $this->post(route('portal.logout'))->assertRedirect(route('master-admin.login'));
            }

            $this->post(route('master-admin.login.submit'), [
                'email' => $companyAdmin->email,
                'password' => 'ShopGuardTest!123',
            ])->assertRedirect(route('portal.dashboard'));
            $this->get(route('portal.notifications'))
                ->assertOk()
                ->assertDontSee('Portal test armed notification');
            $this->get(route('portal.dashboard'))
                ->assertOk()
                ->assertDontSee('<span class="notification-count">', false);

            $this->post(route('portal.logout'))->assertRedirect(route('master-admin.login'));
            $this->post(route('master-admin.login.submit'), [
                'email' => $shopUser->email,
                'password' => 'ShopGuardTest!123',
            ])->assertRedirect(route('portal.dashboard'));
            $this->get(route('portal.notifications'))
                ->assertOk()
                ->assertSee('Shutter armed')
                ->assertSee('PORTAL-A-'.$suffix)
                ->assertSee('Unread');
            $this->get(route('portal.dashboard'))
                ->assertOk()
                ->assertSee('<span class="notification-count">1</span>', false);
            $this->post(route('portal.notifications.read'))
                ->assertRedirect(route('portal.notifications'));
            $this->get(route('portal.dashboard'))
                ->assertOk()
                ->assertDontSee('<span class="notification-count">', false);

            $this->post(route('portal.logout'))->assertRedirect(route('master-admin.login'));
            $this->post(route('master-admin.login.submit'), [
                'email' => $superAdmin->email,
                'password' => 'ShopGuardTest!123',
            ])->assertRedirect(route('master-admin.dashboard'));
            $this->get(route('portal.dashboard'))
                ->assertRedirect(route('master-admin.dashboard'));
        } finally {
            DB::rollBack();
        }
    }
}
