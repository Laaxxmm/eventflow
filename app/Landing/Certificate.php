<?php

namespace App\Landing;

final readonly class Certificate
{
    /**
     * @param  string  $template  A key of config('eventflow.certificates.gallery'), or "upload"
     * @param  string|null  $background  Uploaded A4 background (required when template is "upload")
     * @param  string  $orientation  portrait|landscape
     */
    public function __construct(
        public string $template,
        public ?string $background = null,
        public string $orientation = 'landscape',
    ) {}

    public function isUpload(): bool
    {
        return $this->template === 'upload';
    }
}
