<?php

namespace LaravelDaily\PermissionsUI\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use LaravelDaily\PermissionsUI\Services\SystemAdminService;
use Spatie\Permission\Models\Role;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->paginate();

        return view('PermissionsUI::users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        $roles = Role::pluck('name', 'id');

        return view('PermissionsUI::users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'roles' => ['nullable','array'],
        ]);

        $roleIds = $request->input('roles');
        //load user roles
        if(!$roleIds) $roleIds = [];
        $roles = Role::whereIn('id', $roleIds)->pluck('name');

        $userModel = SystemAdminService::getUserModel();
        $changingUser = $userModel::find($user->id);
        $changingUser->load('roles');
        if(!SystemAdminService::isSystemAdmin(Auth::user())) {
            foreach($roles as $role){
                if(!$changingUser->hasRole($role)) // responde with 403
                abort(403, 'Unauthorized action. You can only change roles you have.');
            }
            $user->load('roles');
            foreach($user->roles as $role){
                if(!$changingUser->hasRole($role->name)) // responde with 403
                abort(403, 'Unauthorized action. You can only change users with all roles you have too.');
            }
        }

        $user->syncRoles($request->input('roles'));

        return redirect()->route(config('permission_ui.route_name_prefix') . 'users.index');
    }
}
