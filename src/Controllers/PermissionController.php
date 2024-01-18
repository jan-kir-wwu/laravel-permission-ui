<?php

namespace LaravelDaily\PermissionsUI\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use LaravelDaily\PermissionsUI\Services\SystemAdminService;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::all();

        return view('PermissionsUI::permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $roles = Role::pluck('name', 'id');

        return view('PermissionsUI::permissions.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $data = $request->validate([
            'name' => ['required', 'string'],
            'roles' => ['array'],
        ]);

        // fix from dfumagalli/laravel-permission-ui fork
        $permissionAttribute = ['name' => $request->input('name')];
        $permission = Permission::create($permissionAttribute);
        $roles = $request->input('roles');

        if (!empty($roles)) {
            foreach ($roles as $roleId) {
                $role = Role::findById($roleId);
                $role->givePermissionTo([$permission]);
            }
        }

        return redirect()->route(config('permission_ui.route_name_prefix') . 'permissions.index');
    }

    public function edit(Permission $permission): View
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $roles = Role::pluck('name', 'id');

        return view('PermissionsUI::permissions.edit', compact('permission', 'roles'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $data = $request->validate([
            'name' => ['required', 'string', 'unique:permissions'],
            'roles' => ['array'],
        ]);

        $permissionAttribute = ['name' => $request->input('name')];
        $permission->update($permissionAttribute);
        $roles = $request->input('roles');

        // fix from dfumagalli/laravel-permission-ui fork
        // If some roles have been checked off, then all roles need to have their permissions cleared first
        $allRoles = Role::with('permissions')->get();

        foreach ($allRoles as $role) {
            // Remove permissions before eventually assigning the new one
            if ($role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }

        if (!empty($roles)) {
            foreach ($roles as $roleId) {
                $role = Role::findById($roleId);
                $role->givePermissionTo([$permission]);
            }
        }

        return redirect()->route(config('permission_ui.route_name_prefix') . 'permissions.index');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $permission->delete();

        return redirect()->route(config('permission_ui.route_name_prefix') . 'permissions.index');
    }
}
