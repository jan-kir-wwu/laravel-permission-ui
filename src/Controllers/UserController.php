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
            'roles' => ['array'],
        ]);
        $roles = $request->input('roles');
        if(!$roles)
            $roles = [];
        //load user roles

        $userModel = SystemAdminService::getUserModel();
        $changingUser = $userModel::find($user->id);
        if(!SystemAdminService::isSystemAdmin(Auth::user())) {
            foreach($roles as $role){
                if(!$changingUser->hasRole($role->name)) // responde with 403
                    abort(403, 'Unauthorized action.');
            }
            $user->load('roles');
            foreach($user->roles as $role){
                if(!$changingUser->hasRole($role->name)) // responde with 403
                    abort(403, 'Unauthorized action.');
            }
        }

        $user->syncRoles($request->input('roles'));

        return redirect()->route(config('permission_ui.route_name_prefix') . 'users.index');
    }
}
