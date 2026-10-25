<?php

namespace App\Landing;

use App\Contracts\LandingSource;
use App\Models\Event;
use App\Models\Org;

/**
 * Published events served from events.config (written by events:sync or the organiser editor).
 */
final class DatabaseLandingSource implements LandingSource
{
    public function __construct(private ConfigCompiler $compiler) {}

    public function load(string $org, string $slug): EventConfig
    {
        return $this->compiler->compile($this->event($org, $slug)->config ?? [], "{$org}/{$slug}");
    }

    public function event(string $org, string $slug): Event
    {
        $orgId = Org::where('slug', $org)->valueOrFail('id');

        return Event::where('org_id', $orgId)
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->firstOrFail();
    }
}
