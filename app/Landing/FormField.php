<?php

namespace App\Landing;

final readonly class FormField
{
    /**
     * @param  string  $type  text|email|tel|number|date|select|radio|checkbox|textarea
     * @param  list<string>  $options  Choices for select/radio/checkbox
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $required = false,
        public array $options = [],
    ) {}
}
