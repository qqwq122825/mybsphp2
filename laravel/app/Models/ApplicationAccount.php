<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'public_id',
    'application_id',
    'username',
    'password_hash',
    'status',
    'balance_cents',
    'points',
    'expires_at',
    'bound_device_ciphertext',
    'bound_device_mask',
    'last_login_at',
    'login_count',
    'failed_attempt_count',
])]
class ApplicationAccount extends Model
{
    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'balance_cents' => 'integer',
            'points' => 'integer',
            'expires_at' => 'datetime',
            'bound_device_ciphertext' => 'encrypted',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
            'failed_attempt_count' => 'integer',
        ];
    }
}
