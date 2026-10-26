<?php

namespace App\Billing;

use App\Models\EventAddon;
use App\Models\ParticipantPack;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

/**
 * Applies a paid purchase exactly once: a participant pack raises the race's cap
 * (participant_packs row), an add-on unlocks it for the event (event_addons row).
 * Safe to call from the webhook and the return page at the same time.
 */
final class Fulfilment
{
    public static function paid(Purchase $purchase, ?string $paymentId = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $paymentId) {
            $locked = Purchase::withoutGlobalScopes()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if ($locked->fulfilled_at !== null) {
                return $locked;
            }

            $locked->forceFill([
                'status' => Purchase::PAID,
                'paid_at' => $locked->paid_at ?? now(),
                'gateway_payment_id' => $paymentId ?? $locked->gateway_payment_id,
            ]);

            if ($locked->event_id !== null) {
                $note = 'Purchase #'.$locked->id.($locked->gateway_order_id ? ' ('.$locked->gateway_order_id.')' : '');
                if ($locked->sku === Catalog::PACK) {
                    ParticipantPack::withoutGlobalScopes()->create([
                        'org_id' => $locked->org_id,
                        'event_id' => $locked->event_id,
                        'participants' => $locked->quantity,
                        'amount_paise' => (int) $locked->amount_paise,
                        'note' => $note,
                    ]);
                } else {
                    EventAddon::withoutGlobalScopes()->updateOrCreate(
                        ['event_id' => $locked->event_id, 'addon' => $locked->sku],
                        ['org_id' => $locked->org_id, 'amount_paise' => (int) $locked->amount_paise, 'note' => $note],
                    );
                }
            }

            $locked->fulfilled_at = now();
            $locked->save();

            return $locked;
        });
    }
}
