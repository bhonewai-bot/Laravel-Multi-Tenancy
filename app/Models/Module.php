<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Module extends Model
{
    use CentralConnection;
    
    protected $fillable = [
        'name',
        'slug',
        'version',
        'description',
        'icon_path',
        'price',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(ModuleRequest::class);
    }
}
