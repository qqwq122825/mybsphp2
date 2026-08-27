<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $risk_score
 * @property string $status
 * @property CarbonImmutable|null $blocked_until
 * @property CarbonImmutable|null $last_event_at
 */
class ClientSecuritySubjectState extends Model
{
    protected $fillable = [
        'application_id', 'device_hash', 'risk_score', 'status', 'blocked_until',
        'last_event_at', 'reason_code', 'recent_card_probes',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until' => 'immutable_datetime',
            'last_event_at' => 'immutable_datetime',
            'risk_score' => 'integer',
            'recent_card_probes' => 'array',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
