<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Tracks the current module operation state per tenant.
 */
class ModuleOperation extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'module_slug',
        'action',
        'status',
        'message',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
