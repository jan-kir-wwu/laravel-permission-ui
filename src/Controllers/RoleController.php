<?php

namespace LaravelDaily\PermissionsUI\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use LaravelDaily\PermissionsUI\Services\PermHelperService;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->get();

        return view('PermissionsUI::roles.index', compact('roles'));
    }

    public function create(): View
    {
        if(!PermHelperService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');
        
        $permissions = Permission::pluck('name', 'id');

        return view('PermissionsUI::roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles'],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissionsIds = $request->input('permissions');
        if(!$permissionsIds) $permissionsIds = [];
        else {
            $newPerms = Permission::whereIn('id', $permissionsIds)->pluck('name');
            PermHelperService::authCanChangeRolePermissions($newPerms);
        }

        $role = Role::create(['name' => $request->input('name')]);

        $role->givePermissionTo($permissionsIds);

        return redirect()->route(config('permission_ui.route_name_prefix') . 'roles.index');
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::pluck('name', 'id');

        return view('PermissionsUI::roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string'],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissionsIds = $request->input('permissions');
        if(!$permissionsIds) $permissionsIds = [];
        else {
            $newPerms = Permission::whereIn('id', $permissionsIds)->pluck('name');
            PermHelperService::authCanChangeRolePermissions($newPerms);
        }
 
        $role->update(['name' => $request->input('name')]);

        $role->syncPermissions($permissionsIds);

        return redirect()->route(config('permission_ui.route_name_prefix') . 'roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if(!PermHelperService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $role->delete();

        return redirect()->route(config('permission_ui.route_name_prefix') . 'roles.index');
    }
}
