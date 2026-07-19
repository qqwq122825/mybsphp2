<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id',
    'code',
    'name',
    'description',
    'mode',
    'entitlement_kind',
    'status',
    'payload_mode',
    'payload_value_ciphertext',
    'client_secret_hash',
    'bind_device_enabled',
    'max_bound_devices',
    'max_concurrent_sessions',
    'version_name',
    'announcement',
    'created_by_administrator_id',
])]
class Application extends Model
{
    use SoftDeletes;

    public function verificationData(): string
    {
        return (string) ($this->payload_value_ciphertext ?? '');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return HasOne<ApplicationLegacyConfig, $this>
     */
    public function legacyConfig(): HasOne
    {
        return $this->hasOne(ApplicationLegacyConfig::class);
    }

    /**
     * @return HasMany<CardType, $this>
     */
    public function cardTypes(): HasMany
    {
        return $this->hasMany(CardType::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    protected function casts(): array
    {
        return [
            'payload_value_ciphertext' => 'encrypted',
            'bind_device_enabled' => 'boolean',
            'max_bound_devices' => 'integer',
            'max_concurrent_sessions' => 'integer',
        ];
    }
}
