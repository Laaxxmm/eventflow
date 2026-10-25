<?php

namespace App\Services\WhatsApp;

/** Indian mobile numbers to WhatsApp's E.164 digits (no "+"). */
final class PhoneNumber
{
    public static function toWhatsApp(?string $phone, string $defaultCountry = '91'): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return $defaultCountry.$digits;
        }

        return strlen($digits) >= 11 && strlen($digits) <= 15 ? $digits : null;
    }
}
