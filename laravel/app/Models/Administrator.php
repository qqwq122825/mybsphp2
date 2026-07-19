<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'username',
    'password',
    'secondary_password',
    'secondary_password_required',
    'role',
    'status',
    'last_login_at',
    'last_login_ip_hash',
])]
#[Hidden(['password', 'secondary_password'])]
class Administrator extends Authenticatable
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'administrator_roles',
        )->withPivot('assigned_by_administrator_id')->withTimestamps();
    }

    public function hasRole(string $code): bool
    {
        return $this->roles()->where('code', $code)->exists();
    }

    public function hasPermission(string $code): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('code', $code))
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'secondary_password' => 'hashed',
            'secondary_password_required' => 'boolean',
            'last_login_at' => 'immutable_datetime',
        ];
    }
}
