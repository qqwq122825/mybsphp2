<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'public_id', 'application_id', 'token_hash', 'auth_mode', 'card_id', 'application_account_id',
    'device_id_ciphertext', 'device_mask', 'status', 'last_heartbeat_at', 'logged_out_at',
])]
class ApplicationClientSession extends Model
{
    public function application(): BelongsTo { return $this->belongsTo(Application::class); }
    public function card(): BelongsTo { return $this->belongsTo(Card::class); }
    public function account(): BelongsTo { return $this->belongsTo(ApplicationAccount::class, 'application_account_id'); }

    protected function casts(): array
    {
        return [
            'device_id_ciphertext' => 'encrypted',
            'last_heartbeat_at' => 'immutable_datetime',
            'logged_out_at' => 'immutable_datetime',
        ];
    }
}
