<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'public_id',
        'actor_user_id',
        'actor_role_snapshot',
        'action',
        'target_type',
        'target_id',
        'application_id',
        'request_id',
        'ip',
        'user_agent_hash',
        'before_data',
        'after_data',
        'reason',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit records are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit records are immutable.');
        });
    }
}
