<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something an organiser buys from Indefine (Session P1): a participant pack or a per-event
 * add-on. status: requested (no online price; Indefine follows up), pending (checkout started),
 * paid, failed. App\Billing\Fulfilment applies a paid purchase once.
 */
class Purchase extends Model
{
    use BelongsToOrg;

    public const REQUESTED = 'requested';

    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    protected $fillable = [
        'org_id', 'event_id', 'user_id', 'sku', 'quantity', 'amount_paise', 'status',
        'gateway', 'gateway_order_id', 'gateway_payment_id', 'paid_at', 'fulfilled_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'amount_paise' => 'integer',
            'paid_at' => 'immutable_datetime',
            'fulfilled_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
