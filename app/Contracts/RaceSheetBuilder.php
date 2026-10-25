<?php

namespace App\Contracts;

use App\Models\Event;
use App\RaceSheets\RaceSheet;

/**
 * Splits an event's paid registrations into heats using the event's race_sheet
 * config (heat_size, group_by, seeding). Race sheets are configuration, not code:
 * no sport-specific branches in implementations.
 */
interface RaceSheetBuilder
{
    /**
     * @throws \DomainException when the event has no race_sheet config
     */
    public function build(Event $event): RaceSheet;
}
