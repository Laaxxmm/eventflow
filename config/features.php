<?php

/*
 * Feature flags. Each defaults on; set the env var to false to switch a feature off
 * for the whole installation (organisers still opt in per event where noted).
 */
return [
    // Unpaid payment reminders (Session N2). Per-event opt-in; off for every event by default.
    'payment_reminders' => (bool) env('FEATURE_PAYMENT_REMINDERS', true),

    // Online checkout for organiser purchases (participant packs, add-ons) on the platform
    // gateway (Session P1). Off, or without platform keys, the Buy page sends a request instead.
    'online_billing' => (bool) env('FEATURE_ONLINE_BILLING', true),
];
