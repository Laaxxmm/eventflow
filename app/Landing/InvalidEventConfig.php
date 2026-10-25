<?php

namespace App\Landing;

use RuntimeException;

/**
 * An event config that fails events/schema.json (after sport pack defaults are applied).
 */
final class InvalidEventConfig extends RuntimeException
{
    /**
     * @param  list<array{property: string, message: string}>  $errors
     */
    public function __construct(
        public readonly string $source,
        public readonly array $errors,
    ) {
        $lines = array_map(
            fn (array $e) => ($e['property'] !== '' ? $e['property'].': ' : '').$e['message'],
            $errors,
        );

        parent::__construct("{$source} is not a valid event config: ".implode('; ', $lines));
    }
}
