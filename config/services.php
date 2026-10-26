<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Participant notifications. Org credentials (Meta token, phone number id) are
    // stored encrypted per org in notification_settings, not here.
    'whatsapp' => [
        'meta' => [
            'graph_url' => env('WHATSAPP_META_GRAPH_URL', 'https://graph.facebook.com'),
            'version' => env('WHATSAPP_META_GRAPH_VERSION', 'v21.0'),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Stock photo APIs for `php artisan imagery:fetch` (Daylight Track brief §5). Optional.
    'unsplash' => ['key' => env('UNSPLASH_ACCESS_KEY')],
    'pexels' => ['key' => env('PEXELS_API_KEY')],

];
