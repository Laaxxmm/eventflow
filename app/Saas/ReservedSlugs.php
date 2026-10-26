<?php

namespace App\Saas;

use App\Models\Org;

/**
 * First URL segments the app uses. An org slug must not take one, or /{org} would be shadowed.
 */
final class ReservedSlugs
{
    public const LIST = [
        'admin', 'api', 'app', 'account', 'build', 'certificates', 'checkin', 'contact', 'dashboard',
        'email', 'features', 'forgot-password', 'login', 'logout', 'organiser', 'pass', 'passes',
        'password', 'pay', 'platform', 'pricing', 'product', 'profile', 'r', 'race-sheets', 'register',
        'reset-password', 'signup', 'storage', 'templates', 'up', 'verify-email', 'webhooks',
    ];

    /** Org::uniqueSlug(), steering clear of reserved words. */
    public static function orgSlug(string $name): string
    {
        $slug = Org::uniqueSlug($name);

        return in_array($slug, self::LIST, true) ? Org::uniqueSlug($name.' events') : $slug;
    }
}
