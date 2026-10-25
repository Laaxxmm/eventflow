<?php

/*
 * EventFlow SaaS plans (marketing site, pricing page, signup).
 *
 * Organisers pay a Season Pass per year plus an optional percentage on paid
 * participant registrations. All amounts are PLACEHOLDERS until Indefine
 * confirms pricing; change them here, never in the Blade views.
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

    'currency' => 'INR',

    // Every self-serve plan starts as a free trial. No card is taken.
    'trial_days' => 14,

    // Plan new organisers get when they sign up without choosing one.
    'default' => 'starter',

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'tagline' => 'One club, a full season of races.',
            'season_pass_inr' => 19999, // placeholder, per season (12 months)
            'participant_fee_percent' => 3.0, // placeholder, on paid registrations
            'self_serve' => true,
            'max_events' => 5,
            'max_registrations' => 2000,
            'modules' => ['checkin'],
            'features' => [
                'Branded event pages from YAML or the editor',
                'Razorpay or Cashfree checkout',
                'EF- passes (PNG + PDF) by email',
                'QR check-in on volunteers\' phones',
                'Up to 5 events and 2,000 registrations a season',
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'tagline' => 'For organisers running a race calendar.',
            'season_pass_inr' => 49999, // placeholder
            'participant_fee_percent' => 2.0, // placeholder
            'self_serve' => true,
            'highlight' => true,
            'max_events' => 25,
            'max_registrations' => 15000,
            'modules' => ['checkin', 'race_sheets', 'certificates'],
            'features' => [
                'Everything in Starter',
                'Race sheets: heats by distance, age band and gender',
                'Finisher certificates (A4 template gallery)',
                'Up to 25 events and 15,000 registrations a season',
                'Priority email support',
            ],
        ],
        'scale' => [
            'name' => 'Scale',
            'tagline' => 'Federations, city marathons and multi-city series.',
            'season_pass_inr' => null, // custom quote
            'participant_fee_percent' => null,
            'self_serve' => false,
            'max_events' => null,
            'max_registrations' => null,
            'modules' => ['checkin', 'race_sheets', 'certificates', 'email_branding', 'sms_whatsapp'],
            'features' => [
                'Everything in Growth, unlimited events',
                'WhatsApp notifications and custom sender domain included',
                'Custom certificate layouts by Indefine',
                'Onboarding and race-day support',
                'Custom participant fee',
            ],
        ],
    ],

    // Optional add-ons, chosen at signup or later. "module" switches an App\Enums\Module on.
    'addons' => [
        'whatsapp' => [
            'name' => 'WhatsApp notifications',
            'description' => 'Confirmation with the pass link and race-day reminders on WhatsApp (Meta charges extra).',
            'season_inr' => 4999, // placeholder
            'module' => 'sms_whatsapp',
        ],
        'custom_smtp' => [
            'name' => 'Custom email sender',
            'description' => 'Send participant emails from your own domain or mailbox.',
            'season_inr' => 1999, // placeholder
            'module' => 'email_branding',
        ],
        'sport_pack' => [
            'name' => 'Extra sport pack',
            'description' => 'Defaults for another sport: skating, cycling, running or a custom pack.',
            'season_inr' => 2999, // placeholder, per pack
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
