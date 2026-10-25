<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Org extends Model
{
    protected $fillable = ['slug', 'name', 'colours', 'payment_provider', 'modules'];

    protected function casts(): array
    {
        return [
            'colours' => 'array',
            'modules' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function seasonPasses(): HasMany
    {
        return $this->hasMany(SeasonPass::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
