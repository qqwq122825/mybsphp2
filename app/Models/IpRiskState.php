<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpRiskState extends Model
{
    protected $fillable = [
        'public_id', 'application_id', 'ip', 'ip_hash', 'level', 'window_attempts', 'recent_attempt_timestamps',
        'recent_card_probes', 'total_attempts', 'window_started_at', 'last_seen_at', 'blocked_until',
        'permanently_blocked', 'last_device_hash', 'last_subject_hash', 'last_endpoint', 'last_attempt_succeeded', 'note',
    ];

    protected function casts(): array
    {
        return [
            'window_attempts' => 'integer',
            'recent_attempt_timestamps' => 'array',
            'recent_card_probes' => 'array',
            'total_attempts' => 'integer',
            'window_started_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
            'blocked_until' => 'immutable_datetime',
            'permanently_blocked' => 'boolean',
            'last_attempt_succeeded' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(RiskEvent::class);
    }
}
