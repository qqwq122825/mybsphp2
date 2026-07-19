<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id',
    'issue_no',
    'application_id',
    'operator_administrator_id',
    'target_type',
    'offline_recipient_ciphertext',
    'recipient_display_mask',
    'status',
    'quantity',
    'total_cost_cents',
    'idempotency_key',
    'note',
    'committed_at',
    'cancelled_at',
])]
class ManualIssue extends Model
{
    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<ManualIssueItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ManualIssueItem::class);
    }

    protected function casts(): array
    {
        return [
            'offline_recipient_ciphertext' => 'encrypted',
            'quantity' => 'integer',
            'total_cost_cents' => 'integer',
            'committed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
