<?php

namespace App\RaceSheets;

final readonly class Heat
{
    /**
     * @param  array<string, string>  $group  Values of the group_by keys, e.g. ['distance' => '500M', 'age_band' => 'U-10']
     * @param  int  $number  1-based heat number within its group
     * @param  list<int>  $registrationIds  In lane/start order; count <= heat_size
     */
    public function __construct(
        public array $group,
        public int $number,
        public array $registrationIds,
    ) {}
}
