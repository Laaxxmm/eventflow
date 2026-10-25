<?php

namespace App\Services\WhatsApp;

final readonly class WhatsAppMessage
{
    /**
     * @param  string  $to  E.164 digits without "+", e.g. 919876543210
     * @param  list<string>  $bodyParameters  Values for {{1}}, {{2}}, ... in the template body
     * @param  string|null  $documentUrl  Public URL for a DOCUMENT header (the pass PDF link)
     */
    public function __construct(
        public string $to,
        public string $template,
        public string $language = 'en',
        public array $bodyParameters = [],
        public ?string $documentUrl = null,
        public ?string $documentFilename = null,
    ) {}
}
