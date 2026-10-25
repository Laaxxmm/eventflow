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
        'optional' => ['checkin', 'race_sheets', 'certificates', 'email_branding', 'sms_whatsapp'],
    ],

    /*
     * Event landing templates (resources/views/landings/templates/<key>.blade.php).
     * Each is a different layout, not a recolour. `tone` drives the dark-logo hint.
     * Custom layouts are not built in-app: organisers are pointed to Indefine.
     */
    'landing_templates' => [
        'aurora' => ['label' => 'Aurora', 'tone' => 'light', 'description' => 'Light and athletic: airy hero, soft gradients, photo card.'],
        'midnight' => ['label' => 'Midnight', 'tone' => 'dark', 'description' => 'Race night: full-bleed dark hero, high contrast, lane-style distances.'],
        'clean' => ['label' => 'Clean', 'tone' => 'light', 'description' => 'Editorial minimal: one column, big type, lots of white space.'],
        'festival' => ['label' => 'Festival', 'tone' => 'light', 'description' => 'Bold colour blocks and big tiles. Energetic, still tidy.'],
        'heritage' => ['label' => 'Heritage', 'tone' => 'light', 'description' => 'Classic meet: crest, serif headings, order of events.'],
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

        // Premium gallery (resources/views/certificates/templates/<key>.blade.php, DomPDF).
        // Thumbnails: resources/certificates/thumbnails/<key>.png. Custom designs: contact Indefine.
        'gallery' => [
            'heritage-gold' => ['label' => 'Heritage Gold', 'description' => 'Classic medal feel: gold foil frame, crest, serif name.', 'orientations' => ['landscape', 'portrait']],
            'modern-minimal' => ['label' => 'Modern Minimal', 'description' => 'Asymmetric, bold sans name, accent bar, lots of air.', 'orientations' => ['landscape', 'portrait']],
            'midnight-elite' => ['label' => 'Midnight Elite', 'description' => 'Deep navy field, light type, fine gold line work. Print-safe light variant.', 'orientations' => ['landscape']],
            'stadium-strip' => ['label' => 'Stadium Strip', 'description' => 'Diagonal colour panel with distance and result. Sport energy.', 'orientations' => ['landscape']],
            'academic-seal' => ['label' => 'Academic Seal', 'description' => 'Guilloché medallion seal, formal. Clinics and workshops.', 'orientations' => ['landscape', 'portrait']],
        ],

        // Deprecated template keys (Session UX gallery) still found in saved events and packs.
        'aliases' => [
            'classic' => 'heritage-gold',
            'podium' => 'stadium-strip',
            'velocity' => 'stadium-strip',
            'minimal' => 'modern-minimal',
            'glide' => 'academic-seal',
        ],

        // Neutral schemes that look expensive without organiser colours. `brand` uses
        // the event's primary/secondary with gold foil.
        'palettes' => [
            'brand' => ['label' => 'Event colours'],
            'gold' => ['label' => 'Gold', 'accent' => '#9a7425', 'accent2' => '#1f2a44', 'ink' => '#1c2333', 'foil' => 'gold'],
            'slate' => ['label' => 'Slate', 'accent' => '#475569', 'accent2' => '#0f172a', 'ink' => '#0f172a', 'foil' => 'silver'],
            'ink' => ['label' => 'Ink', 'accent' => '#1e3a5f', 'accent2' => '#0b1220', 'ink' => '#0b1220', 'foil' => 'gold'],
        ],

        'credentials' => [
            'completion' => 'Certificate of Completion',
            'participation' => 'Certificate of Participation',
            'merit' => 'Certificate of Merit',
        ],

        // Signature slots printed when the event names none.
        'default_signatories' => [
            ['name' => null, 'role' => 'Race Director'],
            ['name' => null, 'role' => 'Chief Guest'],
        ],

        // Where text is stamped on an uploaded A4 background, in mm from the top-left
        // corner of the page (x = horizontal centre of the text). Documented in the README.
        'upload_layout' => [
            'landscape' => [
                'name' => ['x' => 148.5, 'y' => 92, 'size' => 30],
                'event' => ['x' => 148.5, 'y' => 118, 'size' => 14],
                'distance' => ['x' => 148.5, 'y' => 128, 'size' => 12],
                'time' => ['x' => 148.5, 'y' => 138, 'size' => 12],
                'date' => ['x' => 74, 'y' => 178, 'size' => 10],
                'bib' => ['x' => 223, 'y' => 178, 'size' => 10],
                'logo' => ['x' => 148.5, 'y' => 30, 'height' => 18],
                'verify' => ['x' => 272, 'y' => 180, 'size' => 14], // QR + EF-CERT number
            ],
            'portrait' => [
                'name' => ['x' => 105, 'y' => 140, 'size' => 28],
                'event' => ['x' => 105, 'y' => 168, 'size' => 14],
                'distance' => ['x' => 105, 'y' => 178, 'size' => 12],
                'time' => ['x' => 105, 'y' => 188, 'size' => 12],
                'date' => ['x' => 55, 'y' => 262, 'size' => 10],
                'bib' => ['x' => 155, 'y' => 262, 'size' => 10],
                'logo' => ['x' => 105, 'y' => 40, 'height' => 20],
                'verify' => ['x' => 185, 'y' => 268, 'size' => 14],
            ],
        ],

        // Custom layouts are not built in-app: organisers are pointed to Indefine.
        'custom_contact' => env('CUSTOM_TEMPLATE_CONTACT'),
    ],

];
