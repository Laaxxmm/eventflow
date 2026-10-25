<?php

namespace App\Landing;

final readonly class Faq
{
    public function __construct(
        public string $q,
        public string $a,
    ) {}
}
