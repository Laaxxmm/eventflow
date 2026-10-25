<?php

namespace App\RaceSheets;

final readonly class RaceSheet
{
    /**
     * @param  list<Heat>  $heats
     */
    public function __construct(
        public int $eventId,
        public RaceSheetConfig $config,
        public array $heats,
    ) {}
}
