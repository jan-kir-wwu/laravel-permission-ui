<?php

namespace LaravelDaily\PermissionsUI\Tests;

use LaravelDaily\PermissionsUI\Tests\Factories\TestUserFactory;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use LaravelDaily\PermissionsUI\Tests\Models\User;

class PermissionsTest extends TestCase
{
    protected function userAsSystemadmin() {    
        $userFactory = new TestUserFactory();
        $user = $userFactory->create();
        $role = Role::create(['name' => config('permission_ui.system_admin_role')]);
        $user->assignRole($role);
        return $user;
    }
    
    public function testDenyRoleManagementWithoutSystemAdmin()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route(config('permission_ui.route_name_prefix') . 'roles.store'), [
            'name' => 'test role',
        ]);

        $response->assertStatus(403);
    }

    public function testDenyPermissionManagementWithoutSystemAdmin()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route(config('permission_ui.route_name_prefix') . 'permissions.store'), [
            'name' => 'test permission',
        ]);

        $response->assertStatus(403);
    }

    public function testRedirectUrlPrefixToUsersList()
    {
        $user = $this->userAsSystemadmin();

        $response = $this->actingAs($user)->get(config('permission_ui.url_prefix'));

        $response->assertRedirect(route(config('permission_ui.route_name_prefix') . 'users.index'));
    }

    public function testPermissionCanBeAttachedToRole()
    {
        $user = $this->userAsSystemadmin();

        $permission = Permission::create(['name' => 'permission']);

        $response = $this->actingAs($user)->post(route(config('permission_ui.route_name_prefix') . 'roles.store'), [
            'name'        => 'role',
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect(route(config('permission_ui.route_name_prefix') . 'roles.index'));
        
        $this->assertTrue(Role::findByName('role')->hasPermissionTo($permission));
    }

    public function testPermissionsShowsOnCreateAndEditRolePages()
    {
        $user = $this->userAsSystemadmin();

        Permission::create(['name' => 'create user']);

        $response = $this->actingAs($user)->get(route(config('permission_ui.route_name_prefix') . 'roles.create'));

        $response->assertOk()
            ->assertViewHas('permissions', function (Collection $permissions) {
                foreach ($permissions as $permission) {
                    return $permission === 'create user';
                }
            });

        $role = Role::create(['name' => 'admin']);

        $response = $this->actingAs($user)->get(route(config('permission_ui.route_name_prefix') . 'roles.edit', $role));

        $response->assertOk()
            ->assertViewHas('permissions', function (Collection $permissions) {
                foreach ($permissions as $permission) {
                    return $permission === 'create user';
                }
            });
    }

    public function testWhenCreatingPermissionItCanBeAssignedToRole()
    {
        $user = $this->userAsSystemadmin();

        $role = Role::create(['name' => 'admin']);

        $response = $this->actingAs($user)->post(route(config('permission_ui.route_name_prefix') . 'permissions.store'), [
            'name'  => 'create user',
            'roles' => [$role->id],
        ]);

        $response->assertRedirect(route(config('permission_ui.route_name_prefix') . 'permissions.index'));

        $this->assertTrue(Permission::first()->hasRole($role));
    }

    public function testWhenEditingPermissionItCanBeAssignedToRole()
    {
        $user = $this->userAsSystemadmin();

        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'create user']);

        $response = $this->actingAs($user)->patch(route(config('permission_ui.route_name_prefix') . 'permissions.update', $permission), [
            'name'  => 'create_user',
            'roles' => [$role->id],
        ]);

        $response->assertRedirect(route(config('permission_ui.route_name_prefix') . 'permissions.index'));

        $this->assertTrue(Permission::first()->hasRole($role));

        $this->assertDatabaseHas('permissions', [
            'name' => 'create_user',
        ]);
    }
}
