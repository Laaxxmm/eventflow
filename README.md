# EventFlow

White-label event registration SaaS for Indian skating and running races:
branded landing pages, paid registration (Cashfree or Razorpay), EF- passes
(PNG + PDF with a locally generated QR), and QR check-in.

Stack: Laravel 12, PHP 8.3, MySQL 8, Blade + Tailwind (Breeze), Pest, Laravel Sail.

## Quick start

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d          # app, MySQL 8, Mailpit
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

Without Docker: point `DB_*` at a local MySQL 8, then `php artisan migrate && composer dev`.

Tests: `php artisan test` (in-memory SQLite). Style: `./vendor/bin/pint --test`.

## Parallel sessions

See [docs/OWNERSHIP.md](docs/OWNERSHIP.md) for who owns which paths and
[docs/BRANCHES.md](docs/BRANCHES.md) for the branch workflow.

## Stream A: foundation & contracts (Session 1)

Contracts every other stream codes against. Change them only via a `feat/a-` PR.

| Contract | Purpose |
|---|---|
| `App\Contracts\PaymentGateway` | `createOrder`, `verifyWebhook`, `parseEvent`, `fetchStatus`, `refund`. Amounts are integer paise. |
| `App\Payments\Dto\{PaymentOrder, PaymentEvent, RefundResult}` | Readonly DTOs returned by gateways. |
| `App\Payments\PaymentStatus` | Enum `Pending, Paid, Failed, Refunded` (also `registrations.status`). |
| `App\Contracts\PassGenerator` | `generate(Registration): PassFiles` (png + pdf paths). No external QR APIs. |
| `App\Contracts\LandingSource` | `load(org, slug): EventConfig`. |
| `App\Landing\EventConfig` | Typed view of `events/schema.json`; `EventConfig::fromArray()`. |
| `App\Events\RegistrationPaid` | `registrationId`. Dispatched by payments (B), handled by passes (D). Fires after DB commit. |
| `config/eventflow.php` | Payment provider + credentials, pass prefix/pattern/signing key, events paths. |

Schema (`database/migrations`): `orgs`, `users` (+`org_id`, `role` owner|staff|volunteer),
`events` (unique `org_id + slug`), `registrations` (`pass_no` EF-XXXXXXXXXXXX,
unique `gateway + gateway_order_id`), `payment_events` (unique `provider + event_id`).

Event configs: `events/schema.json` (JSON Schema draft-07), demo in
`events/demo/example-marathon.yaml`; `tests/Feature/ContractsTest.php` validates it.

Mail: Brevo SMTP or SES only (Mailpit locally). Queue: `database`.
