<?php

/*
 * Unpaid payment reminders (Session N2): defaults an organiser starts from, and the safety
 * rails no organiser can change. Per-event settings live in events.payment_reminders.
 */
return [
    'defaults' => [
        'enabled' => false, // opt-in per event
        // Hours to wait before each reminder: #1 after the registration was created, then after
        // the previous reminder. Extra reminders (when max > 3) reuse the last delay.
        'delays_hours' => [1, 24, 72],
        'max' => 3,
        // Reminder #3 and later are due no later than this many hours before the start; if that
        // moment has already passed when the reminder would be scheduled, it is skipped.
        'before_event_hours' => 48,
        'subject' => 'Complete your payment — {event}',
        'message' => 'Hi {name}, thanks for starting your entry for {event}. Your registration is not confirmed until payment is complete, and places are limited. It only takes a minute to finish.',
    ],

    'max_reminders' => 5, // organisers can raise the cap up to this

    // Asia/Kolkata quiet hours: nothing is sent from 21:00 to 08:00; due reminders wait for 08:00.
    'timezone' => 'Asia/Kolkata',
    'quiet_from' => 21,
    'quiet_until' => 8,

    // Rate limits: per org per minute (queue middleware), and per scheduler run overall.
    'per_org_per_minute' => (int) env('PAYMENT_REMINDERS_PER_MINUTE', 60),
    'per_run' => (int) env('PAYMENT_REMINDERS_PER_RUN', 500),

    // A manual "Send reminder now" is refused if the participant got any reminder this recently.
    'manual_cooldown_hours' => 12,

    // Signed links in the reminder stay valid this long.
    'link_days' => 14,
];
