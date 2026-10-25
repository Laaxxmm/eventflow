<?php

namespace App\Enums;

/**
 * Product modules an org's Season Pass can include.
 *
 * Core modules (config('eventflow.modules.core')) are on whenever the org has an
 * active Season Pass. The rest are toggled per org in Org Settings (orgs.modules),
 * and only take effect when the active pass includes them.
 */
enum Module: string
{
    case Registration = 'registration';
    case Payments = 'payments';
    case Passes = 'passes';
    case CheckIn = 'checkin';
    case RaceSheets = 'race_sheets';
    case Certificates = 'certificates';
    case EmailBranding = 'email_branding'; // custom SMTP sender for org email
    case SmsWhatsapp = 'sms_whatsapp'; // WhatsApp notifications (paid add-on)
}
