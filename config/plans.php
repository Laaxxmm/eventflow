<?php

/*
 * EventFlow SaaS plans (marketing site, pricing page, signup, plan caps).
 *
 * Prices are LOCKED (Indefine, Session M2). Change them here only, never in Blade.
 * Amounts are INR, exclusive of GST. null price = quoted by Indefine ("Contact Indefine").
 *
 * term: how long a paid plan runs from activation. Single Event is a 90-day window that
 * never renews; the season plans run 12 months and renew yearly. Race slots are counted
 * per term and unused slots do not roll over into the next one.
 *
 * max_events: races an org can create in one term (null = quote).
 * max_participants_per_event: registrations per race before the soft-block (null = quote).
 * Participant packs raise one race's cap; Indefine grants them (php artisan plans:pack).
 *
 * Modules use App\Enums\Module values. Core modules (registration, payments, passes)
 * are in every plan. Email with the organiser's own SMTP is free on every plan;
 * Platform mail (module email_branding) and WhatsApp (sms_whatsapp) are paid add-ons.
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
     * The trial's Season Pass carries the chosen plan's caps (App\Saas\PlanLimits, always
     * enforced). On top, App\Saas\Trial adds trial-only limits:
     *
     *   trial_events         events a trial org can publish
     *   trial_registrations  test registrations across the trial org
     *   trial_watermark      subtle "Powered by EventFlow" on landings until paid
     *
     * TRIAL_STUB=true (default until SaaS checkout is live) shows the trial-only limits but
     * never enforces them: there is nothing to check out yet. Set TRIAL_STUB=false once it ships.
     */
    'trial_days' => 14,
    'trial_events' => 1,
    'trial_registrations' => 25,
    'trial_watermark' => true,
    'trial_stub' => (bool) env('TRIAL_STUB', true),

    // Plan new organisers get when they sign up without choosing one.
    'default' => 'single_event',

    // Display order: Single Event leads on / and /pricing.
    'plans' => [
        'single_event' => [
            'name' => 'Single Event',
            'tagline' => 'One race, from entries to finish line.',
            'price_inr' => 3999,
            'term' => ['days' => 90],
            'term_label' => '90-day window',
            'renews' => false, // one-off: does not auto-renew next year
            'max_events' => 1,
            'max_participants_per_event' => 500,
            'self_serve' => true,
            'highlight' => true, // Single-first: where the pricing strip starts the eye
            'upgrade_to' => 'club_season',
            'support' => 'Email',
            'modules' => ['checkin'],
            'features' => [
                'Branded event page, Razorpay or Cashfree checkout',
                'EF- passes (PNG + PDF) by email',
                'QR check-in on volunteers\' phones',
                'Email included: connect your own SMTP free',
            ],
        ],
        'club_season' => [
            'name' => 'Club Season',
            'tagline' => 'A club calendar, all season.',
            'price_inr' => 12999,
            'term' => ['months' => 12],
            'term_label' => '12 months',
            'renews' => true, // yearly; unused race slots do not roll over
            'max_events' => 5,
            'max_participants_per_event' => 1500,
            'self_serve' => true,
            'upgrade_to' => 'circuit_season',
            'support' => 'Email',
            'modules' => ['checkin', 'race_sheets', 'certificates'],
            'features' => [
                'Everything in Single Event',
                'Race sheets: heats by distance, age band and gender',
                'Finisher certificates (A4 template gallery)',
            ],
        ],
        'circuit_season' => [
            'name' => 'Circuit Season',
            'tagline' => 'Series, circuits and multi-city calendars.',
            'price_inr' => 29999,
            'term' => ['months' => 12],
            'term_label' => '12 months',
            'renews' => true,
            'max_events' => 25,
            'max_participants_per_event' => 5000,
            'self_serve' => true,
            'upgrade_to' => 'custom',
            'support' => 'Priority email',
            'modules' => ['checkin', 'race_sheets', 'certificates'],
            'features' => [
                'Everything in Club Season',
                'Built for big fields and busy calendars',
            ],
        ],
        'custom' => [
            'name' => 'Custom',
            'tagline' => 'Federations, city marathons, special requirements.',
            'price_inr' => null, // quote
            'term' => null,
            'term_label' => null,
            'renews' => null,
            'max_events' => null,
            'max_participants_per_event' => null,
            'self_serve' => false,
            'upgrade_to' => null,
            'support' => 'Onboarding + race-day support',
            'modules' => ['checkin', 'race_sheets', 'certificates'],
            'features' => [
                'Races, field sizes and term to suit you',
                'Custom certificate layouts by Indefine',
                'Onboarding and race-day support',
            ],
        ],
    ],

    // Paid add-ons. "module" switches an App\Enums\Module on. Prices are quoted by Indefine.
    'addons' => [
        'platform_mail' => [
            'name' => 'Platform mail',
            'description' => 'No SMTP of your own? Indefine sends participant emails for you (Brevo / Amazon SES), with your name and reply-to.',
            'price_inr' => null,
            'module' => 'email_branding',
        ],
        'whatsapp' => [
            'name' => 'WhatsApp notifications',
            'description' => 'Confirmation with the pass link and race-day reminders on WhatsApp (Meta charges per message).',
            'price_inr' => null,
            'module' => 'sms_whatsapp',
        ],
    ],

    // When a race reaches its participant cap: soft-block, then these ways out.
    'overage' => [
        'participant_pack' => [
            'name' => 'Participant pack',
            'description' => 'More places for one race on your current plan.',
            'price_inr' => null, // quoted by Indefine
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
