<?php

namespace LaravelDaily\PermissionsUI\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use LaravelDaily\PermissionsUI\Services\SystemAdminService;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->get();

        return view('PermissionsUI::roles.index', compact('roles'));
    }

    public function create(): View
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');
        
        $permissions = Permission::pluck('name', 'id');

        return view('PermissionsUI::roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $request->validate([
            'name' => ['required', 'string', 'unique:roles'],
            'permissions' => ['array'],
        ]);

        $role = Role::create(['name' => $request->input('name')]);

        $role->givePermissionTo($request->input('permissions'));

        return redirect()->route(config('permission_ui.route_name_prefix') . 'roles.index');
    }

    public function edit(Role $role): View
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $permissions = Permission::pluck('name', 'id');

        return view('PermissionsUI::roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $request->validate([
            'name' => ['required', 'string'],
            'permissions' => ['array'],
        ]);

        $role->update(['name' => $request->input('name')]);

        $role->syncPermissions($request->input('permissions'));

        return redirect()->route(config('permission_ui.route_name_prefix') . 'roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if(!SystemAdminService::isSystemAdmin(Auth::user()))
            abort(403, 'Unauthorized action.');

        $role->delete();

        return redirect()->route(config('permission_ui.route_name_prefix') . 'roles.index');
    }
}
