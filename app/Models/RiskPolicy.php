<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskPolicy extends Model
{
    protected $fillable = [
        'application_id', 'enabled', 'window_seconds', 'low_threshold', 'high_threshold',
        'block_duration_minutes', 'low_action', 'decoy_value', 'block_http_status',
        'block_code', 'block_message', 'restart_grace_seconds',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'window_seconds' => 'integer',
            'low_threshold' => 'integer',
            'high_threshold' => 'integer',
            'block_duration_minutes' => 'integer',
            'block_http_status' => 'integer',
            'restart_grace_seconds' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
