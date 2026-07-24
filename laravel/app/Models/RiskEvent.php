<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'public_id', 'application_id', 'ip_risk_state_id', 'ip', 'level', 'action',
        'endpoint', 'window_attempts', 'threshold', 'device_hash', 'reason',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(IpRiskState::class, 'ip_risk_state_id');
    }
}
