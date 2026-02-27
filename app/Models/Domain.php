<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Domain as ModelsDomain;

class Domain extends ModelsDomain
{
    protected $fillable = [
        'domain',
        'tenant_id',
        'verified_at',
        'verification_code'
    ];

    protected $casts = [
        'verified_at' => 'datetime'
    ];
}
