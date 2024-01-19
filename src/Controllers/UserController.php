<?php

namespace LaravelDaily\PermissionsUI\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use LaravelDaily\PermissionsUI\Services\PermHelperService;
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

        $user->load('roles');
        if(PermHelperService::authCanChangeUsersRoles($user, $user->roles)){
            abort(403, 'Unauthorized action. You can only change users with roles you have too.');
        }

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
        $newUserRoles = Role::whereIn('id', $roleIds)->pluck('name');

        PermHelperService::authCanChangeUsersRoles($user, $newUserRoles);

        $user->syncRoles($newUserRoles);

        return redirect()->route(config('permission_ui.route_name_prefix') . 'users.index');
    }
}
