<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSecurityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['public_id', 'application_id', 'application_client_session_id', 'card_id', 'application_account_id', 'event_id', 'event_code', 'severity', 'ip_hash', 'device_hash', 'evidence', 'action', 'created_at'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'created_at' => 'immutable_datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ApplicationClientSession::class, 'application_client_session_id');
    }
}
