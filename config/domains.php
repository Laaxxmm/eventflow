<?php

/*
 * Custom domains (Session P1). Organisers point a host (register.myrace.in) at EventFlow with a
 * CNAME to `cname_target`, press Verify, and the host serves their event page (or their
 * workspace page) once DNS checks out. TLS is issued on demand by the edge proxy, which asks
 * GET /domains/tls-check?domain=<host> before getting a certificate (docs/custom-domains.md).
 *
 * Entitlement: Club Season, Circuit Season and Custom include it; Single Event needs the
 * Custom domain add-on (config('plans.custom_domain_inr'), per event).
 */

$appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

return [
    // What organisers CNAME their host to. Defaults to the app's own host.
    'cname_target' => env('CUSTOM_DOMAIN_CNAME_TARGET') ?: $appHost,

    // For apex domains (myrace.in) that cannot CNAME: A records that also verify (comma list).
    'a_records' => array_values(array_filter(array_map('trim', explode(',', (string) env('CUSTOM_DOMAIN_A_RECORDS', ''))))),

    // Hosts that are EventFlow itself, never looked up as custom domains (comma list + APP_URL host).
    'app_hosts' => array_values(array_unique(array_filter(array_merge(
        [$appHost, 'localhost', '127.0.0.1'],
        array_map('trim', explode(',', (string) env('CUSTOM_DOMAIN_APP_HOSTS', ''))),
    )))),

    // TLS for connected hosts: "on_demand" (Caddy / Traefik ask /domains/tls-check) or "manual".
    'tls' => env('CUSTOM_DOMAIN_TLS', 'on_demand'),

    // Seconds a host -> domain lookup is cached by the routing middleware.
    'cache_seconds' => 60,
];
