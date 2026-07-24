<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'manual_issue_id',
    'card_id',
    'unit_cost_cents',
    'delivered_at',
    'exported_at',
    'delivery_note',
])]
class ManualIssueItem extends Model
{
    public $incrementing = false;

    /**
     * @return BelongsTo<ManualIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(ManualIssue::class, 'manual_issue_id');
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    protected function casts(): array
    {
        return [
            'unit_cost_cents' => 'integer',
            'delivered_at' => 'immutable_datetime',
            'exported_at' => 'immutable_datetime',
        ];
    }
}
