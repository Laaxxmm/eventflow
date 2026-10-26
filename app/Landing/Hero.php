<?php

namespace App\Landing;

final readonly class Hero
{
    public function __construct(
        public string $title,
        public ?string $subtitle = null,
        public ?string $image = null,
        public ?string $og_image = null,
    ) {}
}
