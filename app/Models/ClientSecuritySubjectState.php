<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSecuritySubjectState extends Model
{
    protected $fillable = ['application_id', 'device_hash', 'risk_score', 'status', 'blocked_until', 'last_event_at', 'reason_code'];

    protected function casts(): array
    {
        return ['blocked_until' => 'immutable_datetime', 'last_event_at' => 'immutable_datetime', 'risk_score' => 'integer'];
    }
}
