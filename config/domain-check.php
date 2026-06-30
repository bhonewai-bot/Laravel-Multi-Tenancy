<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain Check Token
    |--------------------------------------------------------------------------
    |
    | Shared secret token used by Caddy's on-demand TLS check to validate
    | whether a domain is permitted to route traffic to this application.
    |
    */

    'token' => env('DOMAIN_CHECK_TOKEN', ''),

];
