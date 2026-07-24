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
    'closed_message',
    'payload_mode',
    'payload_value_ciphertext',
    'client_secret_hash',
    'gateway_token',
    'communication_key_ciphertext',
    'encryption_mode',
    'server_public_key',
    'server_private_key_ciphertext',
    'legacy_packet_key_ciphertext',
    'bind_device_enabled',
    'max_bound_devices',
    'max_concurrent_sessions',
    'heartbeat_interval_seconds',
    'connection_timeout_seconds',
    'registration_bonus_value',
    'registration_bonus_unit',
    'rebind_deduction_value',
    'rebind_deduction_unit',
    'invitation_bonus_value',
    'invitation_bonus_unit',
    'version_name',
    'announcement',
    'created_by_administrator_id',
])]
class Application extends Model
{
    use SoftDeletes;

    public function invitationBonusSeconds(): int
    {
        return $this->durationSeconds($this->invitation_bonus_value, $this->invitation_bonus_unit, 'day');
    }

    public function registrationBonusSeconds(): int
    {
        return $this->durationSeconds($this->registration_bonus_value, $this->registration_bonus_unit, 'hour');
    }

    public function rebindDeductionSeconds(): int
    {
        return $this->durationSeconds($this->rebind_deduction_value, $this->rebind_deduction_unit, 'hour');
    }

    private function durationSeconds(mixed $rawValue, mixed $rawUnit, string $defaultUnit): int
    {
        $value = max(0, (int) $rawValue);
        $unit = is_string($rawUnit) ? $rawUnit : $defaultUnit;

        return $value * match ($unit) {
            'minute' => 60,
            'hour' => 3600,
            'month' => 2592000,
            'year' => 31536000,
            default => 86400,
        };
    }

    public function defaultLoginApiName(): string
    {
        return $this->mode === 'account_recharge' ? 'login.lg' : 'login.ic';
    }

    /**
     * @return array{login_api_name: string, credential_param: string, credential_secret_param: string}
     */
    public function legacyLoginDefaults(): array
    {
        return [
            'login_api_name' => $this->defaultLoginApiName(),
            'credential_param' => $this->mode === 'account_recharge' ? 'user' : 'icid',
            'credential_secret_param' => $this->mode === 'account_recharge' ? 'pwd' : 'icpwd',
        ];
    }

    public function verificationData(): string
    {
        return (string) ($this->payload_value_ciphertext ?? '');
    }

    public function communicationKey(): string
    {
        return (string) ($this->communication_key_ciphertext ?? '');
    }

    public function serverPrivateKey(): string
    {
        return (string) ($this->server_private_key_ciphertext ?? '');
    }

    public function legacyPacketKey(): string
    {
        return (string) ($this->legacy_packet_key_ciphertext ?? '');
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
     * @return HasOne<RiskPolicy, $this>
     */
    public function riskPolicy(): HasOne
    {
        return $this->hasOne(RiskPolicy::class);
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

    /**
     * @return HasMany<ApplicationAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(ApplicationAccount::class);
    }

    protected function casts(): array
    {
        return [
            'payload_value_ciphertext' => 'encrypted',
            'communication_key_ciphertext' => 'encrypted',
            'server_private_key_ciphertext' => 'encrypted',
            'legacy_packet_key_ciphertext' => 'encrypted',
            'bind_device_enabled' => 'boolean',
            'max_bound_devices' => 'integer',
            'max_concurrent_sessions' => 'integer',
            'heartbeat_interval_seconds' => 'integer',
            'connection_timeout_seconds' => 'integer',
            'registration_bonus_value' => 'integer',
            'rebind_deduction_value' => 'integer',
            'invitation_bonus_value' => 'integer',
        ];
    }

    public function closedMessage(): string
    {
        return filled($this->closed_message) ? (string) $this->closed_message : '系统维护，暂时关闭，请稍后再访问！';
    }
}
