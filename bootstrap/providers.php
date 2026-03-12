<?php

declare(strict_types=1);

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
];

if (
    class_exists(App\Providers\TelescopeServiceProvider::class) &&
    filter_var(env('TELESCOPE_ENABLED', false), FILTER_VALIDATE_BOOL)
) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
