<?php

namespace App\Models;

use App\Enums\Sport;
use App\Models\Tenancy\BelongsToOrg;
use App\Models\Tenancy\OrgScope;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use BelongsToOrg, HasFactory;

    protected $fillable = [
        'org_id', 'slug', 'name', 'sport', 'template_id', 'certificate_template_id', 'description', 'date', 'venue', 'price_paise', 'capacity',
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
            'payment_reminders' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Auto slug from the name, unique per org (kept from the prototype).
        static::creating(function (Event $event) {
            $event->slug ??= static::uniqueSlug($event->org_id, $event->name);
        });
    }

    public static function uniqueSlug(int $orgId, string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'event', 70, '');
        $taken = fn (string $slug) => static::withoutGlobalScope(OrgScope::class)
            ->where('org_id', $orgId)->where('slug', $slug)->exists();

        $slug = $base;
        for ($i = 2; $taken($slug); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
