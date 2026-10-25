<?php

namespace App\Contracts;

use App\Landing\EventConfig;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Loads an event landing configuration (events/schema.json shape) for an org + event slug.
 */
interface LandingSource
{
    /**
     * @throws ModelNotFoundException when no such event exists
     */
    public function load(string $org, string $slug): EventConfig;
}
