<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class MasterAdminAuthTest extends TestCase
{
    public function test_guest_is_redirected_to_the_master_admin_login(): void
    {
        $this->get(route('master-admin.companies.index'))
            ->assertRedirect(route('master-admin.login'));
    }

    public function test_super_admin_can_access_the_dashboard(): void
    {
        $user = new User([
            'full_name' => 'Test Administrator',
            'status' => 'active',
        ]);
        $user->setAttribute('user_id', 1);
        $user->setRelation('role', new Role([
            'role_code' => 'SUPER_ADMIN',
        ]));

        $this->actingAs($user)
            ->get(route('master-admin.dashboard'))
            ->assertOk()
            ->assertSee('Your shutter operations,')
            ->assertSee('main-sidebar-toggle')
            ->assertSee('Collapse main navigation')
            ->assertSee('shopguard-admin-nav-collapsed')
            ->assertSee('Companies');
    }

    public function test_super_admin_can_open_every_management_module(): void
    {
        $user = new User([
            'full_name' => 'Test Administrator',
            'status' => 'active',
        ]);
        $user->setAttribute('user_id', 1);
        $user->setRelation('role', new Role([
            'role_code' => 'SUPER_ADMIN',
        ]));

        $routes = [
            'master-admin.companies.index',
            'master-admin.companies.create',
            'master-admin.users.index',
            'master-admin.users.create',
            'master-admin.roles.index',
            'master-admin.roles.create',
            'master-admin.shops.index',
            'master-admin.shops.create',
            'master-admin.devices.index',
            'master-admin.devices.create',
            'master-admin.configurations.index',
            'master-admin.configurations.create',
            'master-admin.schedules.index',
            'master-admin.schedules.create',
            'master-admin.alerts.index',
            'master-admin.notification-rules.index',
            'master-admin.notification-rules.create',
        ];

        foreach (['inventory', 'events', 'heartbeats', 'commands', 'notification-jobs', 'notification-logs', 'push-tokens', 'audit'] as $module) {
            $routes[] = ['master-admin.operations.index', $module];
        }

        $this->actingAs($user);

        foreach ($routes as $route) {
            $name = is_array($route) ? $route[0] : $route;
            $parameters = is_array($route) ? [$route[1]] : [];
            $this->get(route($name, $parameters))->assertOk();
        }
    }
}
