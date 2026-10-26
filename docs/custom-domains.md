# Custom domains (Session P1)

Organisers can put an event page, or on Club Season and up their workspace page, on their own
host, for example `register.myrace.in`. The default `/{org}/{slug}` URL always keeps working.

| Plan | Custom domain |
|---|---|
| Single Event (₹3,999) | Not included. The **Custom domain** add-on costs **₹599 per event** (for the 90-day window), set by `plans.custom_domain_inr`. |
| Club Season | Included |
| Circuit Season | Included |
| Custom | Included (set up by Indefine) |

## Flow

1. **Settings → Domain → Connect custom domain.** The organiser enters a host and picks the page it
   shows. On Single Event without the add-on, the button is **Add Custom domain — ₹599**, which
   opens the Buy page.
2. **DNS.** The organiser adds a `CNAME` from the host to `CUSTOM_DOMAIN_CNAME_TARGET`, which
   defaults to the `APP_URL` host. An apex domain can use an A record instead, to an IP listed in
   `CUSTOM_DOMAIN_A_RECORDS`.
3. **Verify.** `App\Domains\DomainVerifier` checks DNS with `dns_get_record` and marks the domain
   `active`. Re-check is available at any time.
4. **Routing.** `App\Http\Middleware\RouteCustomDomain` is global middleware. On an active custom
   host:
   - `/` is rewritten to the event or workspace page;
   - every other public path (the registration POST, `/pay`, `/r/…`, `/build`) works unchanged;
   - organiser and account paths redirect to `APP_URL`.

   Lookups are cached for 60 seconds, and the cache is cleared on verify and remove.
5. **TLS.** Certificates are issued on demand by the edge proxy. Before issuing one it calls
   `GET /domains/tls-check?domain=<host>`, which returns 200 only for verified hosts, so nobody can
   make you request certificates for arbitrary names.

## Caddy (recommended)

```caddyfile
{
    on_demand_tls {
        ask http://app:8000/domains/tls-check
    }
}

# The app itself
eventflow.example.com {
    reverse_proxy app:8000
}

# Every organiser domain
https:// {
    tls {
        on_demand
    }
    reverse_proxy app:8000
}
```

## Other proxies

- **Traefik** can do the same with a certificate resolver plus an HTTP router per host. Without
  on-demand TLS, set `CUSTOM_DOMAIN_TLS=manual` and add each verified host to the proxy by hand.
- **Railway / Render**: add the organiser's host as a custom domain in the platform dashboard. The
  platform issues the certificate, and EventFlow still does the routing.

## Notes

- Keep `SESSION_DOMAIN` empty (null). The session cookie is then host-only, and registration forms
  on custom hosts pass CSRF.
- Uploads and passes keep using `APP_URL` (public disk URLs), so nothing else needs a second host.
- Hosts on the app's own domain (for example `foo.eventflow.example.com`) are rejected.
- Removing a domain stops routing straight away, and the default URL keeps working.
