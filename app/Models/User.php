<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return strtolower($this->role?->name ?? '') === strtolower($roleName);
    }

    // supports "product.read", "user.manage", etc
    public function hasPermission(string $permissionKey): bool
    {
        if (! $this->role) {
            return false;
        }

        $this->loadMissing('role.permissions.feature');

        if (! str_contains($permissionKey, '.')) {
            return $this->role->permissions->contains(function (Permission $permission) use ($permissionKey) {
                return strtolower($permission->name) === strtolower($permissionKey);
            });
        }

        [$featureName, $permissionName] = explode('.', $permissionKey, 2);

        return $this->role->permissions->contains(function (Permission $permission) use ($featureName, $permissionName) {
            return strtolower($permission->name) === strtolower($permissionName)
                && strtolower($permission->feature?->name ?? '') === strtolower($featureName);
        });
    }
}
