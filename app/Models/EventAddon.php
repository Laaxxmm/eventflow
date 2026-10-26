<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A per-event add-on bought for one event (race_sheet, certificates). */
class EventAddon extends Model
{
    use BelongsToOrg;

    protected $fillable = ['org_id', 'event_id', 'addon', 'amount_paise', 'note'];

    protected function casts(): array
    {
        return ['amount_paise' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
