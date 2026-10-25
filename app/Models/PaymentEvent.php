<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stored webhook (idempotency log). Not to be confused with the
 * App\Payments\Dto\PaymentEvent DTO returned by PaymentGateway::parseEvent().
 *
 * Deliberately NOT org-scoped: webhooks arrive before the org is known and this
 * table is a provider-level log. Never list it in org-facing UI; reach org data
 * through registration().
 */
class PaymentEvent extends Model
{
    protected $fillable = ['provider', 'event_id', 'type', 'registration_id', 'payload', 'processed_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'immutable_datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
