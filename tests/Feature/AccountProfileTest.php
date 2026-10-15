<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    public function test_user_can_update_own_profile_and_change_password(): void
    {
        DB::beginTransaction();

        try {
            $roleId = DB::table('roles')->where('role_code', 'END_CUSTOMER')->value('role_id');
            $user = User::create([
                'role_id' => $roleId,
                'full_name' => 'Profile Test User',
                'email' => Str::lower(Str::random(12)).'@example.test',
                'mobile' => '+15550001111',
                'password_hash' => Hash::make('ShopGuardTest!123'),
                'status' => 'active',
            ]);

            $this->actingAs($user)
                ->get(route('portal.profile'))
                ->assertOk()
                ->assertSee('Change password');

            $this->put(route('portal.profile.update'), [
                'full_name' => 'Updated Profile User',
                'email' => 'updated-'.Str::lower(Str::random(8)).'@example.test',
                'mobile' => '+15550002222',
            ])->assertRedirect();

            $this->assertDatabaseHas('users', [
                'user_id' => $user->user_id,
                'full_name' => 'Updated Profile User',
                'mobile' => '+15550002222',
            ]);

            $this->put(route('portal.profile.password'), [
                'current_password' => 'wrong-password',
                'new_password' => 'ShopGuardNewPass!2026',
                'new_password_confirmation' => 'ShopGuardNewPass!2026',
            ])->assertSessionHasErrors('current_password');

            $this->put(route('portal.profile.password'), [
                'current_password' => 'ShopGuardTest!123',
                'new_password' => 'ShopGuardNewPass!2026',
                'new_password_confirmation' => 'ShopGuardNewPass!2026',
            ])->assertRedirect();

            $updatedUser = User::find($user->user_id);
            $this->assertTrue(Hash::check('ShopGuardNewPass!2026', $updatedUser->password_hash));
        } finally {
            DB::rollBack();
        }
    }

    public function test_super_admin_can_open_own_profile_page(): void
    {
        DB::beginTransaction();

        try {
            $roleId = DB::table('roles')->where('role_code', 'SUPER_ADMIN')->value('role_id');
            $user = User::create([
                'role_id' => $roleId,
                'full_name' => 'Admin Profile Test',
                'email' => Str::lower(Str::random(12)).'@example.test',
                'password_hash' => Hash::make('ShopGuardTest!123'),
                'status' => 'active',
            ]);

            $this->actingAs($user)
                ->get(route('master-admin.profile'))
                ->assertOk()
                ->assertSee('Profile details')
                ->assertSee(route('master-admin.profile.update'));
        } finally {
            DB::rollBack();
        }
    }
}
