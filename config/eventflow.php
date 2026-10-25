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

];
