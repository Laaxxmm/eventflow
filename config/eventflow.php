<?php

/*
 * EventFlow shared configuration. Owned by Session 1 (contracts):
 * other streams read these keys but change them only via a Session 1 PR.
 */

return [

    'currency' => 'INR',

    /*
     * Directory holding event YAML configs validated by events/schema.json.
     */
    'events_path' => base_path('events'),
    'schema_path' => base_path('events/schema.json'),
    'sports_path' => base_path('events/sports'),
    'sport_pack_schema_path' => base_path('events/sports/schema.json'),

    'payment' => [
        // Default provider; orgs.payment_provider overrides per org.
        'provider' => env('PAYMENT_PROVIDER', 'cashfree'),

        'providers' => [
            'razorpay' => [
                'key_id' => env('RAZORPAY_KEY_ID'),
                'key_secret' => env('RAZORPAY_KEY_SECRET'),
                'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            ],
            'cashfree' => [
                'app_id' => env('CASHFREE_APP_ID'),
                'secret_key' => env('CASHFREE_SECRET_KEY'),
                'env' => env('CASHFREE_ENV', 'sandbox'), // sandbox|production
                'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
            ],
        ],
    ],

    'pass' => [
        // Pass number format kept from the prototype: EF- + 12 uppercase hex chars.
        'prefix' => 'EF-',
        'pattern' => '/^EF-[A-F0-9]{12}$/',
        // HMAC key for signed check-in QR payloads and pass download links.
        'signing_key' => env('PASS_SIGNING_KEY'),
        'disk' => env('PASS_DISK', 'local'),
    ],

    'roles' => ['owner', 'staff', 'volunteer'],

    /*
     * Season Pass: orgs buy a season (season_passes row) that includes a set of
     * modules; owners toggle optional modules in Org Settings (orgs.modules).
     * No per-event one-shot invoices. See App\Contracts\Entitlements.
     */
    'season_pass' => [
        'length_days' => 365,
    ],

    'modules' => [
        // Always on, with or without a Season Pass; cannot be toggled off.
        'core' => ['registration', 'payments', 'passes'],
        // Need an active pass that includes them; toggled per org in Org Settings.
        'optional' => ['checkin', 'race_sheets', 'certificates'],
    ],

    'race_sheets' => [
        'heat_size_presets' => [6, 8, 10], // any N in 2..64 is also accepted
        'default_heat_size' => 8,
    ],

    'certificates' => [
        // Fixed A4. Uploaded backgrounds must match these pixel sizes exactly (300 dpi).
        'paper' => 'a4',
        'dpi' => 300,
        'px' => [
            'portrait' => [2480, 3508],
            'landscape' => [3508, 2480],
        ],
        'upload_mimes' => ['png', 'jpg', 'jpeg'],
        'upload_max_kb' => 8192,
        'disk' => env('CERTIFICATE_DISK', 'local'),

        // Built-in template gallery (views under resources/views/certificates/<key>).
        'gallery' => [
            'classic' => ['label' => 'Classic', 'orientations' => ['landscape', 'portrait']],
            'podium' => ['label' => 'Podium', 'orientations' => ['landscape']],
            'minimal' => ['label' => 'Minimal', 'orientations' => ['landscape', 'portrait']],
        ],

        // Custom layouts are not built in-app: organisers are pointed to Indefine.
        'custom_contact' => env('CUSTOM_TEMPLATE_CONTACT'),
    ],

];
