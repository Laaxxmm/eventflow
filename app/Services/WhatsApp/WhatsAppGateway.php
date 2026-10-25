<?php

namespace App\Services\WhatsApp;

/**
 * Sends pre-approved WhatsApp template messages. One adapter per provider
 * (first: MetaCloudGateway). Business-initiated messages must use templates.
 */
interface WhatsAppGateway
{
    /**
     * @throws WhatsAppException when the provider rejects the message or is unreachable
     */
    public function sendTemplate(WhatsAppMessage $message): WhatsAppResult;
}
