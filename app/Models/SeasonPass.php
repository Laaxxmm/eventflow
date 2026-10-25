<?php

namespace App\Models;

use App\Enums\Module;
use App\Models\Tenancy\BelongsToOrg;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;

class SeasonPass extends Model
{
    use BelongsToOrg;

    protected $fillable = [
        'org_id', 'plan', 'starts_on', 'ends_on', 'status', 'modules', 'max_events',
        'max_registrations', 'amount_paise', 'gateway', 'gateway_order_id', 'gateway_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'modules' => AsEnumCollection::of(Module::class),
            'max_events' => 'integer',
            'max_registrations' => 'integer',
            'amount_paise' => 'integer',
        ];
    }
}
