<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'public_id',
    'agent_id',
    'type',
    'amount_cents',
    'balance_after_cents',
    'reference_type',
    'reference_id',
    'note',
    'metadata',
])]
class AgentTransaction extends Model
{
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'agent_id');
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'balance_after_cents' => 'integer',
            'metadata' => 'array',
        ];
    }
}
