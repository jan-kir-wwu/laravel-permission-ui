<?php

namespace LaravelDaily\PermissionsUI\Tests\Factories;

use Orchestra\Testbench\Factories\UserFactory as TestbenchUserFactory;
use LaravelDaily\PermissionsUI\Tests\Models\User as User;

class TestUserFactory extends TestbenchUserFactory
{
    public function modelName()
    {
        return User::class; // Ihr benutzerdefiniertes Test-User-Modell
    }
}