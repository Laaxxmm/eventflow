<?php

namespace App\Passes;

/**
 * Paths (relative to config('eventflow.pass.disk')) of a generated pass.
 */
final readonly class PassFiles
{
    public function __construct(
        public string $pngPath,
        public string $pdfPath,
    ) {}
}
