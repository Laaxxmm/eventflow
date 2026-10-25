<?php

namespace App\Models;

use Database\Factories\OrgFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Org extends Model
{
    /** @use HasFactory<OrgFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'name', 'phone', 'colours', 'payment_provider', 'modules'];

    protected function casts(): array
    {
        return [
            'colours' => 'array',
            'modules' => 'array',
        ];
    }

    /** URL-safe slug from $name, suffixed -2, -3, ... until unused. */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';
        $base = Str::limit($base, 70, '');
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
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

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
