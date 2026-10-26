<?php

namespace App\Support;

/**
 * "Contact Indefine" links (config/brand.php). Never returns "#" or an empty mailto:.
 */
final class Contact
{
    public static function email(): string
    {
        $email = (string) config('brand.contact_email');

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'hello@indefine.in';
    }

    /** The contact page when one is configured, else a mailto: with the subject filled in. */
    public static function url(?string $subject = null): string
    {
        $url = config('brand.contact_url');
        if (is_string($url) && preg_match('#^(https?://|/)#', $url)) {
            return $url;
        }

        return self::mailto($subject);
    }

    /**
     * Custom templates / certificate layouts: CUSTOM_TEMPLATE_CONTACT when it is an email or a
     * URL, else the general contact link.
     */
    public static function customDesign(?string $subject = null): string
    {
        $custom = trim((string) config('eventflow.certificates.custom_contact'));

        return match (true) {
            filter_var($custom, FILTER_VALIDATE_EMAIL) !== false => 'mailto:'.$custom.($subject ? '?subject='.rawurlencode($subject) : ''),
            (bool) preg_match('#^https?://#', $custom) => $custom,
            default => self::url($subject),
        };
    }

    /** Always a mailto: (for links that must open an email, e.g. with a prefilled request). */
    public static function mailto(?string $subject = null, ?string $body = null): string
    {
        $query = array_filter(['subject' => $subject, 'body' => $body], 'filled');

        return 'mailto:'.self::email().($query ? '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }
}
