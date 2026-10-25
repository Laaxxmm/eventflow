<?php

namespace App\Models;

use App\Enums\Sport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'org_id', 'slug', 'name', 'sport', 'description', 'date', 'venue', 'price_paise', 'capacity',
        'distances', 'min_age', 'max_age', 'colours', 'form_fields', 'status', 'config',
    ];

    protected function casts(): array
    {
        return [
            'sport' => Sport::class,
            'date' => 'immutable_datetime',
            'price_paise' => 'integer',
            'capacity' => 'integer',
            'distances' => 'array',
            'min_age' => 'integer',
            'max_age' => 'integer',
            'colours' => 'array',
            'form_fields' => 'array',
            'config' => 'array',
        ];
    }

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
