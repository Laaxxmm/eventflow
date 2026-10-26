<?php

namespace App\Domains;

/** DnsResolver over PHP's resolver (dns_get_record). */
final class SystemDnsResolver implements DnsResolver
{
    public function cname(string $host): array
    {
        $records = @dns_get_record($host, DNS_CNAME) ?: [];

        return array_values(array_map(fn (array $r) => rtrim(strtolower((string) ($r['target'] ?? '')), '.'), $records));
    }

    public function a(string $host): array
    {
        $records = @dns_get_record($host, DNS_A) ?: [];

        return array_values(array_map(fn (array $r) => (string) ($r['ip'] ?? ''), $records));
    }
}
