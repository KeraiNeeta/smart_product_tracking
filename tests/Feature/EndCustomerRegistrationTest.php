<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\DefaultCompany;
use Tests\TestCase;

class EndCustomerRegistrationTest extends TestCase
{
    public function test_public_registration_always_creates_an_end_customer_account(): void
    {
        DB::beginTransaction();

        try {
            $this->get(route('register'))
                ->assertOk()
                ->assertSee('Create your customer account')
                ->assertSee('Device serial number');

            $email = 'new-customer-'.Str::lower(Str::random(10)).'@example.test';
            $serialNumber = 'SG-SERIAL-'.Str::upper(Str::random(10));
            $deviceId = DB::table('devices')->insertGetId([
                'device_uid' => 'SG-UID-'.Str::upper(Str::random(10)),
                'serial_number' => $serialNumber,
                'lifecycle_status' => 'in_stock',
                'connectivity_status' => 'unknown',
            ], 'device_id');
            $this->post(route('register.submit'), [
                'full_name' => 'New Customer',
                'email' => $email,
                'mobile' => '+15551234567',
                'serial_number' => $serialNumber,
                'password' => 'ShopGuardTest!123',
                'password_confirmation' => 'ShopGuardTest!123',
                'role_id' => DB::table('roles')->where('role_code', 'SUPER_ADMIN')->value('role_id'),
            ])->assertRedirect(route('portal.dashboard'));

            $user = DB::table('users')->where('email', $email)->first();
            $this->assertNotNull($user);
            $this->assertSame(
                'END_CUSTOMER',
                DB::table('roles')->where('role_id', $user->role_id)->value('role_code'),
            );
            $this->assertSame('active', $user->status);
            $defaultCompanyId = app(DefaultCompany::class)->requireId();
            $this->assertSame($defaultCompanyId, (int) $user->company_id);
            $this->assertTrue(Hash::check('ShopGuardTest!123', $user->password_hash));
            $this->assertAuthenticatedAs(\App\Models\User::find($user->user_id));
            $this->assertDatabaseHas('device_assignments', [
                'device_id' => $deviceId,
                'owner_user_id' => $user->user_id,
                'is_current' => true,
            ]);
            $this->assertDatabaseHas('devices', [
                'device_id' => $deviceId,
                'company_id' => $defaultCompanyId,
                'lifecycle_status' => 'assigned',
            ]);

            auth()->logout();
            $this->post(route('register.submit'), [
                'full_name' => 'Second Customer',
                'email' => 'second-'.Str::lower(Str::random(10)).'@example.test',
                'serial_number' => $serialNumber,
                'password' => 'ShopGuardTest!123',
                'password_confirmation' => 'ShopGuardTest!123',
            ])->assertSessionHasErrors('serial_number');
        } finally {
            DB::rollBack();
        }
    }
}
