<?php

namespace LaravelDaily\PermissionsUI\Services;
use App\Models\User;
use Illuminate\Support\Facades\App;

class SystemAdminService
{
    public static function isSystemAdmin($user): bool
    {
        $userModel = self::getUserModel();
        $user = $userModel::find($user->id);

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