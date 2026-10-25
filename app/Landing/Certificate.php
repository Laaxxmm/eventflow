<?php

namespace App\Landing;

final readonly class Certificate
{
    /**
     * @param  string  $template  A key of config('eventflow.certificates.gallery') (or a deprecated alias), or "upload"
     * @param  string|null  $background  Uploaded A4 background (required when template is "upload")
     * @param  string  $orientation  portrait|landscape
     * @param  string|null  $palette  brand|gold|slate|ink (null: brand colours if set, else gold)
     * @param  string|null  $credential  completion|participation|merit
     * @param  string|null  $variant  screen|print (midnight-elite only: print swaps the dark field for ivory)
     * @param  array<int, array{name?: string|null, role?: string|null}>  $signatories  Up to two signature slots
     * @param  bool|null  $stamp  Show the round organiser stamp
     */
    public function __construct(
        public string $template,
        public ?string $background = null,
        public string $orientation = 'landscape',
        public ?string $palette = null,
        public ?string $credential = null,
        public ?string $variant = null,
        public array $signatories = [],
        public ?bool $stamp = null,
    ) {}

    public function isUpload(): bool
    {
        return $this->template === 'upload';
    }
}
