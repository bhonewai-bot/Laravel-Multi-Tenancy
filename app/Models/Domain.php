<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Domain as ModelsDomain;

class Domain extends ModelsDomain
{
    protected $fillable = [
        'domain',
        'tenant_id',
        'cf_hostname_id',
        'cf_hostname_status',
        'cf_ssl_status',
        'cf_last_checked_at',
        'cf_error',
        'cf_payload',
        'verified_at',
        'verification_code'
    ];

    protected $casts = [
        'cf_last_checked_at' => 'datetime',
        'cf_payload' => 'array',
        'verified_at' => 'datetime'
    ];
}
