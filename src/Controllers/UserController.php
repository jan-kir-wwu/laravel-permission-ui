<?php

namespace LaravelDaily\PermissionsUI\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemAdminService;

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
        $changingUser = Auth::user();

        $request->validate([
            'roles' => ['required', 'array'],
        ]);

        $roles = $request->input('roles');

        if(!$changingUser->hasRole($roles))
            abort(403, 'Unauthorized action.');

        $user->syncRoles($request->input('roles'));

        return redirect()->route(config('permission_ui.route_name_prefix') . 'users.index');
    }
}
