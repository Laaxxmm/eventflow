<?php

namespace App\Domains;

/** DNS lookups for custom domain verification (swapped for a fake in tests). */
interface DnsResolver
{
    /** @return list<string> CNAME targets of $host, lower-case, without the trailing dot */
    public function cname(string $host): array;

    /** @return list<string> IPv4 addresses $host resolves to */
    public function a(string $host): array;
}
