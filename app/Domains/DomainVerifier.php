<?php

namespace App\Domains;

use App\Models\CustomDomain;
use Illuminate\Support\Facades\Cache;

/**
 * Checks a custom domain's DNS: a CNAME to config('domains.cname_target'), or, for an apex
 * domain, an A record listed in config('domains.a_records'). Active once it points at us.
 */
final class DomainVerifier
{
    public function __construct(private DnsResolver $dns) {}

    public function verify(CustomDomain $domain): CustomDomain
    {
        $target = strtolower(rtrim((string) config('domains.cname_target'), '.'));
        $cnames = $this->dns->cname($domain->host);
        $ips = config('domains.a_records') ? $this->dns->a($domain->host) : [];

        $ok = in_array($target, $cnames, true) || array_intersect($ips, (array) config('domains.a_records')) !== [];

        $domain->forceFill([
            'status' => $ok ? CustomDomain::ACTIVE : CustomDomain::PENDING,
            'verified_at' => $ok ? ($domain->verified_at ?? now()) : null,
            'last_checked_at' => now(),
            'last_error' => $ok ? null : mb_substr($cnames
                ? __('The CNAME points to :found, not :target.', ['found' => implode(', ', $cnames), 'target' => $target])
                : __('No CNAME record found yet. DNS changes can take up to an hour.'), 0, 255),
        ])->save();

        Cache::forget(DomainRouter::cacheKey($domain->host));

        return $domain;
    }
}
