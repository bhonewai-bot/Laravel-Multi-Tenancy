<?php

namespace App\Support;

/**
 * Resolves the correct post-auth landing path for the current app context.
 */
class AppHome
{
    public static function path(): string
    {
        return tenant() ? '/dashboard' : '/tenants';
    }
}
