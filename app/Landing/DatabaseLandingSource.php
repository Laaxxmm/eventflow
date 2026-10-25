<?php

namespace App\Landing;

use App\Contracts\LandingSource;
use App\Models\Event;
use App\Models\Org;
use App\Models\Tenancy\CurrentOrg;

/**
 * Published events served from events.config (written by events:sync or the organiser editor).
 * Public pages run as the event's org (CurrentOrg), whoever is signed in.
 */
final class DatabaseLandingSource implements LandingSource
{
    public function __construct(private ConfigCompiler $compiler) {}

    public function load(string $org, string $slug): EventConfig
    {
        return $this->compiler->compile($this->event($org, $slug)->config ?? [], "{$org}/{$slug}");
    }

    /** The org by slug, made the current org for the rest of the request. */
    public function org(string $slug): Org
    {
        $org = Org::where('slug', $slug)->firstOrFail();
        app(CurrentOrg::class)->set($org);

        return $org;
    }

    public function event(string $org, string $slug): Event
    {
        return $this->org($org)->events()
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->firstOrFail();
    }
}
