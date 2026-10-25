<?php

namespace App\Contracts;

use App\Enums\Sport;
use App\Sports\SportPack;

/**
 * Loads sport pack defaults from events/sports/<sport>.yaml (shape: events/sports/schema.json).
 */
interface SportPackRepository
{
    public function get(Sport $sport): SportPack;
}
