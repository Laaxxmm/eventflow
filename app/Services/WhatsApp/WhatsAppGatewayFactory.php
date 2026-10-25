<?php

namespace App\Services\WhatsApp;

use App\Notifications\NotificationSettings;
use InvalidArgumentException;

/** Builds the org's gateway from its encrypted settings. */
class WhatsAppGatewayFactory
{
    public const PROVIDERS = ['meta' => 'Meta WhatsApp Cloud API'];

    public function for(NotificationSettings $settings): WhatsAppGateway
    {
        return match ($settings->whatsapp_provider) {
            'meta' => new MetaCloudGateway(
                (string) $settings->whatsapp_phone_number_id,
                (string) $settings->whatsapp_token,
                (string) config('services.whatsapp.meta.graph_url'),
                (string) config('services.whatsapp.meta.version'),
            ),
            default => throw new InvalidArgumentException("Unsupported WhatsApp provider [{$settings->whatsapp_provider}]"),
        };
    }
}
