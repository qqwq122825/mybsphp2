<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'code',
    'module',
    'name',
    'description',
    'risk_level',
    'agent_assignable',
])]
class Permission extends Model
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
        )->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'agent_assignable' => 'boolean',
        ];
    }
}
