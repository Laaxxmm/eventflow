<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stored webhook (idempotency log). Not to be confused with the
 * App\Payments\Dto\PaymentEvent DTO returned by PaymentGateway::parseEvent().
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
