<?php

namespace App\RaceSheets;

final readonly class RaceSheetConfig
{
    /**
     * @param  int  $heatSize  Max entries per heat. Presets 6, 8, 10; any N >= 2 allowed.
     * @param  list<string>  $groupBy  Heats never mix groups. Keys: distance, age_band, gender, or any form field key.
     * @param  string  $seeding  registration_order|random
     */
    public function __construct(
        public int $heatSize,
        public array $groupBy = ['distance'],
        public string $seeding = 'registration_order',
    ) {}

    /** @param  array{heat_size: int, group_by?: list<string>, seeding?: string}  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            heatSize: $data['heat_size'],
            groupBy: $data['group_by'] ?? ['distance'],
            seeding: $data['seeding'] ?? 'registration_order',
        );
    }
}
