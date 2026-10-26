<?php

/*
 * Feature flags. Each defaults on; set the env var to false to switch a feature off
 * for the whole installation (organisers still opt in per event where noted).
 */
return [
    // Unpaid payment reminders (Session N2). Per-event opt-in; off for every event by default.
    'payment_reminders' => (bool) env('FEATURE_PAYMENT_REMINDERS', true),
];
