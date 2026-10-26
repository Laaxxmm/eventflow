<?php

/*
 * EventFlow SaaS plans (marketing site, pricing page, signup).
 *
 * Organisers buy a flat pass (Single Event, Club Season, Circuit Season) with no
 * percentage on tickets, or talk to Indefine for Custom. Change prices here,
 * never in the Blade views.
 *
 * Modules use App\Enums\Module values. Core modules (registration, payments,
 * passes) are in every plan.
 */

return [

    'brand' => [
        'name' => 'EventFlow',
        // "EventFlow by Indefine"; set BRAND_SHOW_INDEFINE=false to show "EventFlow" only.
        'by' => env('BRAND_SHOW_INDEFINE', true) ? 'Indefine' : null,
        'contact_email' => env('SALES_CONTACT_EMAIL', 'hello@indefine.in'),
    ],

    // Footer links, per deployment. An unset link is hidden (legal falls back to the contact email).
    'links' => [
        'docs' => env('MARKETING_DOCS_URL'),
        'status' => env('MARKETING_STATUS_URL', '/up'),
        'indefine' => env('MARKETING_INDEFINE_URL', 'https://indefine.in'),
        'terms' => env('MARKETING_TERMS_URL'),
        'privacy' => env('MARKETING_PRIVACY_URL'),
    ],

    'currency' => 'INR',

    /*
     * Free trial (product-led: no card, no human gate before the first publish).
     *
     *   trial_days           length of the trial
     *   trial_events         events a trial org can create and publish
     *   trial_registrations  test registrations across the trial org's events
     *   trial_watermark      subtle "Powered by EventFlow" on landings until paid
     *
     * TRIAL_STUB=true (the default until SaaS billing checkout is live) keeps the
     * limits visible in the UI but never hard-blocks: there is no way to pay yet,
     * so blocking would strand a trial. Set TRIAL_STUB=false once checkout ships.
     */
    'trial_days' => 14,
    'trial_events' => 1,
    'trial_registrations' => 25,
    'trial_watermark' => true,
    'trial_stub' => (bool) env('TRIAL_STUB', true),

    // Plan new organisers get when they sign up without choosing one.
    'default' => 'single',

    // Plan keys from before Single/Club/Circuit, resolved to their successor.
    'legacy' => [
        'starter' => 'club',
        'growth' => 'circuit',
        'scale' => 'custom',
    ],

    /*
     * No ticket percentage on any plan: organisers pay one flat price per pass.
     * max_events per pass window; pax_per_event is a soft cap per event
     * (over it: Upgrade, Buy a participant pack, or Contact Indefine).
     */
    'plans' => [
        'single' => [
            'name' => 'Single Event',
            'tagline' => 'One race, done properly.',
            'season_pass_inr' => 3999,
            'term' => '90-day window',
            'duration_days' => 90,
            'participant_fee_percent' => null,
            'self_serve' => true,
            'highlight' => true, // where the pricing strip starts the eye
            'max_events' => 1,
            'pax_per_event' => 500,
            'support' => 'Email',
            'modules' => ['checkin', 'race_sheets', 'certificates', 'email_branding'],
            'features' => [
                '1 race over a 90-day window',
                'Up to 500 participants',
                'QR passes, gate check-in and certificates',
                'Email confirmations · own SMTP free',
            ],
        ],
        'club' => [
            'name' => 'Club Season',
            'tagline' => 'A club calendar for the year.',
            'season_pass_inr' => 12999,
            'term' => '12 months',
            'duration_days' => 365,
            'participant_fee_percent' => null,
            'self_serve' => true,
            'max_events' => 5,
            'pax_per_event' => 1500,
            'support' => 'Priority email',
            'modules' => ['checkin', 'race_sheets', 'certificates', 'email_branding'],
            'features' => [
                '5 races over 12 months',
                'Up to 1,500 participants per race',
                'Everything in Single Event',
                'Priority email support',
            ],
        ],
        'circuit' => [
            'name' => 'Circuit Season',
            'tagline' => 'Series, circuits and busy organisers.',
            'season_pass_inr' => 29999,
            'term' => '12 months',
            'duration_days' => 365,
            'participant_fee_percent' => null,
            'self_serve' => true,
            'max_events' => 25,
            'pax_per_event' => 5000,
            'support' => 'Priority email + race-week chat',
            'modules' => ['checkin', 'race_sheets', 'certificates', 'email_branding'],
            'features' => [
                '25 races over 12 months',
                'Up to 5,000 participants per race',
                'Everything in Club Season',
                'Race-week chat support',
            ],
        ],
        'custom' => [
            'name' => 'Custom',
            'tagline' => 'Federations, city marathons, managed race ops.',
            'season_pass_inr' => null, // quoted by Indefine
            'term' => 'Custom term',
            'duration_days' => null,
            'participant_fee_percent' => null,
            'self_serve' => false,
            'max_events' => null,
            'pax_per_event' => null,
            'support' => 'Named contact + race-day support',
            'modules' => ['checkin', 'race_sheets', 'certificates', 'email_branding', 'sms_whatsapp'],
            'features' => [
                'Unlimited races and participants',
                'Platform mail and WhatsApp included',
                'Custom certificate layouts',
                'Managed race ops by Indefine',
            ],
        ],
    ],

    // What every self-serve plan includes for participant messaging.
    'messaging' => [
        'email' => true,          // confirmations and passes by email
        'own_smtp' => true,       // free: plug in your own SMTP in Settings
    ],

    // Optional add-ons, bought in Settings. "module" switches an App\Enums\Module on.
    'addons' => [
        'platform_mail' => [
            'name' => 'Platform mail',
            'description' => 'We send participant email for you from a verified EventFlow sender. Skip it if you use your own SMTP.',
            'season_inr' => null, // TODO: price not confirmed by Indefine yet
            'module' => null,
        ],
        'whatsapp' => [
            'name' => 'WhatsApp notifications',
            'description' => 'Confirmation with the pass link and race-day reminders on WhatsApp (Meta charges extra).',
            'season_inr' => 4999, // placeholder
            'module' => 'sms_whatsapp',
        ],
        'participant_pack' => [
            'name' => 'Participant pack',
            'description' => 'Extra participants on one event when it outgrows its plan cap.',
            'season_inr' => null, // TODO: price not confirmed by Indefine yet
            'module' => null,
        ],
    ],

    // Local/demo accounts created by Database\Seeders\PlatformSeeder. Passwords only from env.
    'demo' => [
        'admin_email' => 'admin@eventflow.local',
        'admin_password' => env('PLATFORM_ADMIN_PASSWORD'),
        'organiser_email' => 'organiser@eventflow.local',
        'organiser_password' => env('DEMO_ORGANISER_PASSWORD'),
        // owner@demo.test / volunteer@demo.test (Database\Seeders\DemoSeeder). "password" locally;
        // production (e.g. a public Railway preview) must set DEMO_PASSWORD.
        'demo_password' => env('DEMO_PASSWORD', 'password'),
    ],

];
