<?php

namespace App\Models\Tenancy;

use App\Models\Org;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Auth;

/**
 * The org every org-owned query is filtered by (see OrgScope).
 *
 * Resolution, per request/job:
 *  1. an org set explicitly with set() / run() (public pages, jobs, webhooks);
 *  2. otherwise the authenticated user's org_id, once the guard has resolved the user
 *     (behind the `auth` middleware it always has);
 *  3. otherwise none: queries are not filtered.
 * An authenticated user without an org sees nothing.
 */
#[Scoped]
final class CurrentOrg
{
    private bool $explicit = false;

    private ?int $orgId = null;

    /** Org id to filter by, 0 to match nothing, or null for no filtering. */
    public function constraint(): ?int
    {
        if ($this->explicit) {
            return $this->orgId;
        }

        $guard = Auth::guard();

        if (! $guard->hasUser()) {
            return null;
        }

        return $guard->user()->org_id ?? 0;
    }

    public function id(): ?int
    {
        return $this->constraint() ?: null;
    }

    public function set(Org|int|null $org): void
    {
        $this->explicit = $org !== null;
        $this->orgId = $org instanceof Org ? $org->id : $org;
    }

    /**
     * Run $callback with the given org as the current org, then restore.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(Org|int $org, callable $callback): mixed
    {
        [$explicit, $orgId] = [$this->explicit, $this->orgId];
        $this->set($org);

        try {
            return $callback();
        } finally {
            [$this->explicit, $this->orgId] = [$explicit, $orgId];
        }
    }
}
