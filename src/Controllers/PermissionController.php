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

        $permission = Permission::create($data);

        $permission->syncRoles($request->input('roles'));

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
            'name' => ['required', 'string'],
            'roles' => ['array'],
        ]);

        $permission->update($data);

        $permission->syncRoles($request->input('roles'));

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
