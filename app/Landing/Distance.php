<?php

namespace App\Landing;

final readonly class Distance
{
    public function __construct(
        public string $code,
        public string $label,
        public int $price_inr,
        public ?int $capacity = null,
    ) {}

    public function pricePaise(): int
    {
        return $this->price_inr * 100;
    }
}
