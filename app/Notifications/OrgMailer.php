<?php

namespace App\Notifications;

use App\Models\Org;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;

/**
 * Picks how an org's mail is sent.
 *
 * Platform (default): the app mailer from MAIL_* (Brevo or SES SMTP), from the
 * platform address with the organiser's brand as the name and their email as reply-to.
 * Custom SMTP (module email_branding, enabled and verified): the org's own server and
 * from-address, registered at send time as mailer "org_<id>".
 */
final class OrgMailer
{
    /** Mailer name for config('mail.mailers'). */
    public static function mailerName(Org $org, NotificationSettings $settings): string
    {
        if (! $settings->usesCustomSmtp($org)) {
            return config('mail.default');
        }

        return self::registerSmtp($org, $settings);
    }

    /** Registers the org's SMTP server as a mailer (also used to test unverified settings). */
    public static function registerSmtp(Org $org, NotificationSettings $settings): string
    {
        $name = 'org_'.$org->id;
        $encryption = $settings->smtp_encryption ?: 'tls';

        config(["mail.mailers.{$name}" => [
            'transport' => 'smtp',
            // smtps = implicit TLS (465); smtp upgrades with STARTTLS when the server offers it.
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $settings->smtp_host,
            'port' => $settings->smtp_port,
            'username' => $settings->smtp_username,
            'password' => $settings->smtp_password,
            'timeout' => 15,
            'auto_tls' => $encryption !== 'none',
        ]]);
        Mail::purge($name);

        return $name;
    }

    public static function mailer(Org $org, NotificationSettings $settings): Mailer
    {
        return Mail::mailer(self::mailerName($org, $settings));
    }

    public static function from(Org $org, NotificationSettings $settings): Address
    {
        if ($settings->usesCustomSmtp($org)) {
            return new Address($settings->smtp_from_email, $settings->smtp_from_name ?: $org->name);
        }

        return new Address(
            (string) config('mail.from.address'),
            $settings->from_name ?: $org->name ?: (string) config('mail.from.name'),
        );
    }
}
