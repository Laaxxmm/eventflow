<?php

namespace App\Landing;

final readonly class AgeBand
{
    public function __construct(
        public string $label,
        public int $min,
        public int $max,
    ) {}
}
