<?php

namespace App\Passes;

/**
 * QR payload is "{pass_no}.{hmac-sha256}" signed with PASS_SIGNING_KEY.
 * Generated locally; never sent to an external QR API.
 */
final class SignedPassQr
{
    public static function payload(string $passNo): string
    {
        return $passNo.'.'.self::signature($passNo);
    }

    public static function signature(string $passNo): string
    {
        return hash_hmac('sha256', $passNo, self::key());
    }

    /**
     * @return string|null The pass number when the signature matches.
     */
    public static function verify(string $payload): ?string
    {
        $payload = trim($payload);
        $dot = strrpos($payload, '.');
        if ($dot === false) {
            return null;
        }

        $passNo = substr($payload, 0, $dot);
        $signature = substr($payload, $dot + 1);
        $pattern = (string) config('eventflow.pass.pattern');

        if ($signature === '' || ! preg_match($pattern, $passNo)) {
            return null;
        }

        $expected = self::signature($passNo);

        return hash_equals($expected, $signature) ? $passNo : null;
    }

    private static function key(): string
    {
        return (string) config('eventflow.pass.signing_key');
    }
}
