<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Business Platform Cloud API (Meta), the organiser's own WABA number.
 * POST {graph_url}/{version}/{phone_number_id}/messages with a template message.
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
 */
final class MetaCloudGateway implements WhatsAppGateway
{
    public function __construct(
        private string $phoneNumberId,
        #[\SensitiveParameter] private string $token,
        private string $graphUrl = 'https://graph.facebook.com',
        private string $version = 'v21.0',
    ) {}

    public function sendTemplate(WhatsAppMessage $message): WhatsAppResult
    {
        $components = [];

        if ($message->documentUrl !== null) {
            $components[] = [
                'type' => 'header',
                'parameters' => [[
                    'type' => 'document',
                    'document' => array_filter(['link' => $message->documentUrl, 'filename' => $message->documentFilename]),
                ]],
            ];
        }

        if ($message->bodyParameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn (string $text) => ['type' => 'text', 'text' => $text], $message->bodyParameters),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $message->to,
            'type' => 'template',
            'template' => array_filter([
                'name' => $message->template,
                'language' => ['code' => $message->language],
                'components' => $components ?: null,
            ]),
        ];

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(15)
                ->post(rtrim($this->graphUrl, '/')."/{$this->version}/{$this->phoneNumberId}/messages", $payload);
        } catch (ConnectionException $e) {
            throw new WhatsAppException('WhatsApp API unreachable: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new WhatsAppException("WhatsApp API error {$response->status()}: {$error}");
        }

        $id = $response->json('messages.0.id');
        if (! is_string($id)) {
            throw new WhatsAppException('WhatsApp API returned no message id');
        }

        return new WhatsAppResult($id);
    }
}
