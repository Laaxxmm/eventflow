<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Extra participant places for one race, on top of the plan's per-event cap. */
class ParticipantPack extends Model
{
    use BelongsToOrg;

    protected $fillable = ['org_id', 'event_id', 'participants', 'amount_paise', 'note'];

    protected function casts(): array
    {
        return ['participants' => 'integer', 'amount_paise' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
