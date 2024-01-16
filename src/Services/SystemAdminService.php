<?php

namespace App\Services;

class SystemAdminService
{
    public static function isSystemAdmin($user): bool
    {
        return $user->hasRole(config('permission_ui.system_admin_role'));
    }
}