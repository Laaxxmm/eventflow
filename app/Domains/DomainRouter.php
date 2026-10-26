<?php

namespace App\Domains;

use App\Models\CustomDomain;
use Illuminate\Support\Facades\Cache;

/** host -> active custom domain, cached briefly for the routing middleware. */
final class DomainRouter
{
    public static function cacheKey(string $host): string
    {
        return 'custom-domain:'.strtolower($host);
    }

    /** ['path' => '/org/slug'] for an active host, else null. */
    public static function resolve(string $host): ?array
    {
        $host = strtolower($host);

        return Cache::remember(self::cacheKey($host), (int) config('domains.cache_seconds', 60), function () use ($host) {
            $domain = CustomDomain::withoutGlobalScopes()->where('host', $host)->where('status', CustomDomain::ACTIVE)->first();

            return $domain ? ['path' => $domain->targetPath()] : false;
        }) ?: null;
    }
}
