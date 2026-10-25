<?php

namespace App\Landing;

final readonly class Branding
{
    public function __construct(
        public string $primary,
        public string $secondary,
        public ?string $logo = null,
    ) {}
}
