<?php

namespace App\Policies;

use App\Models\User;

class ModuleRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('module.read');
    }

    public function request(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('module.request');
    }

    public function install(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('module.install');
    }

    public function uninstall(User $user): bool
    {
        return $this->install($user);
    }
}
