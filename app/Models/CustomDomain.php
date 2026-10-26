<?php

namespace App\Models;

use App\Models\Tenancy\BelongsToOrg;
use App\Models\Tenancy\OrgScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An organiser's own host for an event page or their workspace page (Session P1). */
class CustomDomain extends Model
{
    use BelongsToOrg;

    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    protected $fillable = ['org_id', 'event_id', 'host', 'status', 'verified_at', 'last_checked_at', 'last_error'];

    protected function casts(): array
    {
        return [
            'verified_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withoutGlobalScope(OrgScope::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /** Path the host's "/" serves: the event page, or the workspace page. */
    public function targetPath(): string
    {
        $org = $this->org()->first();

        return $this->event_id !== null && ($event = $this->event()->first())
            ? '/'.$org->slug.'/'.$event->slug
            : '/'.$org->slug;
    }
}
