<?php

namespace LaravelDaily\PermissionsUI\Services;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class PermHelperService
{
    public static function authCanChangeRolePermissions($permissions)
    {
        $changingUser = PermHelperService::getCorrectUserModel(Auth::user());

        if(!$changingUser->hasRole('system admin')) {
            foreach($permissions as $permission){
                if(!$changingUser->can($permission)) // responde with 403
                    abort(403, 'Unauthorized action. You can only change permission you have.');
            }
        }
    }

    public static function authCanChangeUsersRoles($changedUser, $roles)
    {
        $changingUser = PermHelperService::getCorrectUserModel(Auth::user());

        $changingUser->load('roles');
        if(!$changingUser->hasRole('system admin')) {
            foreach($roles as $role){
                if(!$changingUser->hasRole($role)) // responde with 403
                    abort(403, 'Unauthorized action. You can only change roles you have.');
            }
            $changedUser->load('roles');
            foreach($changedUser->roles as $role){
                if(!$changingUser->hasRole($role->name)) // responde with 403
                    abort(403, 'Unauthorized action. You can only change users with roles you have too.');
            }
        }
    }

    public static function getCorrectUserModel($user) {
        $userModel = self::getUserModel();
        return $userModel::find($user->id);
    }

    public static function isSystemAdmin($user): bool
    {
        $user = PermHelperService::getCorrectUserModel($user);

        return $user->hasRole(config('permission_ui.system_admin_role'));
    }

    public static function getUserModel()
    {
        if (App::runningUnitTests()) {
            return 'LaravelDaily\PermissionsUI\Tests\Models\User';
        }

        return 'App\Models\User'; 
    }
}