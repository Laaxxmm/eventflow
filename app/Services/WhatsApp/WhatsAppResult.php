<?php

namespace App\Services\WhatsApp;

final readonly class WhatsAppResult
{
    public function __construct(public string $messageId) {}
}
