<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'username',
    'password',
    'secondary_password',
    'secondary_password_required',
    'role',
    'status',
    'agent_balance_cents',
    'agent_level',
    'agent_discount_basis_points',
    'agent_note',
    'contact_qq',
    'contact_email',
    'contact_phone',
    'agent_all_applications',
    'last_login_at',
    'last_login_ip_hash',
])]
#[Hidden(['password', 'secondary_password', 'admin_session_token_hash'])]
class Administrator extends Authenticatable
{
    protected static function booted(): void
    {
        static::updating(function (Administrator $administrator): void {
            if ($administrator->isDirty(['username', 'password', 'secondary_password', 'secondary_password_required', 'status', 'role'])) {
                $administrator->admin_session_token_hash = null;
            }
        });
    }

    public function agentTransactions(): HasMany
    {
        return $this->hasMany(AgentTransaction::class, 'agent_id');
    }

    /**
     * @return BelongsToMany<Application, $this>
     */
    public function agentApplications(): BelongsToMany
    {
        return $this->belongsToMany(
            Application::class,
            'agent_application_access',
            'agent_id',
            'application_id',
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<CardType, $this>
     */
    public function agentCardTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            CardType::class,
            'agent_card_type_access',
            'agent_id',
            'card_type_id',
        )->withTimestamps();
    }

    public function cardBatches(): HasMany
    {
        return $this->hasMany(CardBatch::class, 'created_by_administrator_id');
    }

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
        $this->loadMissing('roles.permissions');

        return $this->roles->contains(
            static fn (Role $role): bool => $role->code === $code,
        );
    }

    public function hasPermission(string $code): bool
    {
        $this->loadMissing('roles.permissions');

        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roles->contains(
            static fn (Role $role): bool => $role->permissions->contains(
                static fn (Permission $permission): bool => $permission->code === $code,
            ),
        );
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'secondary_password' => 'hashed',
            'secondary_password_required' => 'boolean',
            'agent_balance_cents' => 'integer',
            'agent_level' => 'integer',
            'agent_discount_basis_points' => 'integer',
            'agent_all_applications' => 'boolean',
            'last_login_at' => 'immutable_datetime',
        ];
    }
}
