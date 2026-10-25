# EventFlow

White-label event registration SaaS for Indian skating and running races:
branded landing pages, paid registration (Cashfree or Razorpay), EF- passes
(PNG + PDF with a locally generated QR), and QR check-in.

Stack: Laravel 12, PHP 8.3, MySQL 8, Blade + Tailwind (Breeze), Pest, Laravel Sail.

## Quick start

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d          # app, MySQL 8, Mailpit (:8025), queue worker
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

Locally, set `MAIL_HOST=mailpit MAIL_PORT=1025` in `.env` to catch verification
and reset emails in Mailpit. Demo logins (from `DemoSeeder`): `owner@demo.test`
and `volunteer@demo.test`, password `password`.

Without Docker: point `DB_*` at a local MySQL 8, then `php artisan migrate --seed && composer dev`.

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
| `App\Contracts\Entitlements` | Season Pass + module toggles: `activeSeasonPass(org)`, `enabled(org, Module)`. |
| `App\Contracts\SportPackRepository` | `get(Sport): SportPack` from `events/sports/<sport>.yaml`. |
| `App\Contracts\RaceSheetBuilder` | `build(Event): RaceSheet` (heats from `race_sheet` config). |
| `App\Contracts\CertificateRenderer` | `render(Registration): CertificateFile` (A4 PDF). |
| `App\Enums\{Sport, Module}` | `skating, cycling, running, generic`; `registration, payments, passes, checkin, race_sheets, certificates`. |
| `App\Support\EventSchema` | `errors($data, EventSchema::EVENT\|SPORT_PACK)`: the one shared validator. |
| `config/eventflow.php` | Payment provider + credentials, pass prefix/pattern/signing key, events paths, modules, race sheet presets, certificate gallery and A4 sizes. |

Schema (`database/migrations`): `orgs`, `users` (+`org_id`, `role` owner|staff|volunteer),
`events` (unique `org_id + slug`), `registrations` (`pass_no` EF-XXXXXXXXXXXX,
unique `gateway + gateway_order_id`), `payment_events` (unique `provider + event_id`).

Event configs: `events/schema.json` (JSON Schema draft-07), demo in
`events/demo/example-marathon.yaml`; `tests/Feature/ContractsTest.php` validates it.

Mail: Brevo SMTP or SES only (Mailpit locally). Queue: `database`.

### Accounts, roles and org scoping

- **Signup** (`/register`, ported from the prototype: organisation name, work
  email, optional phone, password) creates an `Org` with a unique slug
  (`sunrise-sports-club`, then `-2`, ...) and makes the organiser its **owner**.
  Email must be verified before the dashboard opens; password reset is Breeze's.
- **Roles** (`users.role`, `App\Enums\Role`): `owner`, `staff`, `volunteer`.
  Gates: `manage-org` (owner), `view-dashboard` and `export-participants`
  (owner, staff), `check-in` (any org member). Volunteers are sent to `/checkin`
  from every other page (`RestrictVolunteers`); stream F provides that route.
- **Org scope.** `Event`, `Registration`, `User` and `SeasonPass` use
  `App\Models\Tenancy\BelongsToOrg`: every query is filtered by the current
  org and new rows get its id. The current org is the logged-in user's, or one
  set explicitly with `app(CurrentOrg::class)->run($org, fn () => ...)` (use this
  on public pages, in jobs and in webhooks). With neither, queries are unfiltered.
  `registrations.org_id` always follows the event. `PaymentEvent` is deliberately
  unscoped (provider webhook log). Bypass on purpose only:
  `Model::withoutGlobalScope(OrgScope::class)`.
- **Rate limits**: 5/min/IP on login, signup, forgot and reset password
  (`throttle:auth`). Public registration routes must use `throttle:registration`
  (same limit, defined here).
- **Dashboard** (`/dashboard`): totals and per-event registrations, paid count,
  paid total (INR) and checked-in count. `/dashboard/participants` filters by
  event and status and exports CSV (custom answers as extra columns, spreadsheet
  formulas neutralised).

### Product rules

- **Season Pass, not per-event invoices.** A `season_passes` row (dates, status,
  included `modules`) grants an org a season. Core modules (registration,
  payments, passes) are on while a pass is active. Optional modules (check-in,
  race sheets, certificates) are toggled in Org Settings (`orgs.modules`) and
  only work when the pass includes them. With no active pass, every module is off.
- **Sport packs.** `events.sport` / YAML `sport:` picks `events/sports/<sport>.yaml`.
  An event inherits each top-level key it omits (`distances`, `age_bands`, `fields`,
  `race_sheet`, `certificate`); keys it sets replace the pack's value entirely.
  Pack distances carry no price, so they must be priced per event.
- **Race sheets are config.** `race_sheet: {heat_size, group_by, seeding}`;
  `heat_size` presets 6/8/10, any N in 2..64. Heats never mix `group_by` groups.
  No `race_sheet` means a mass start.
- **Certificates.** `certificate.template` is a gallery key (`classic`, `podium`,
  `minimal`) or `upload` with a `background` at exactly A4 300 dpi
  (3508x2480 landscape, 2480x3508 portrait). Custom layouts are not built in-app:
  organisers contact Indefine (`CUSTOM_TEMPLATE_CONTACT`).

Demos: `events/demo/example-marathon.yaml` (running, no heats) and
`events/demo/example-skating.yaml` (skating pack, heats of 6, uploaded certificate).

## Stream D: passes and check-in

When `RegistrationPaid` fires, the queued listener `App\Listeners\SendPass` builds the participant pass and queues `App\Mail\PassMail` (PNG + PDF attached).

- **Pass number.** The shipped contract is `EF-` + 12 uppercase hex (`config('eventflow.pass.pattern')`, same as the prototype). It is assigned with `Registration::newPassNo()` if a paid registration does not already have a matching number. `EF-{year}-{6 digits}` does not fit that pattern or the `pass_no` column comment, so it is not used.
- **QR.** Local PNG via `endroid/qr-code` (GD). Payload is `{pass_no}.{hmac-sha256}` using `PASS_SIGNING_KEY`. No external QR API.
- **Files.** `App\Passes\DomPdfPassGenerator` writes PNG (Intervention Image) and A4 PDF (DomPDF) to the `eventflow.pass.disk` disk, using event/org colours and the prototype layout (navy strip, participant, EF- number, scan-at-check-in QR).
- **Mail.** Queue the listener (`QUEUE_CONNECTION=database` in production; Mailpit locally, Brevo SMTP or SES from `.env` only). Run `php artisan queue:work`.
- **Check-in.** Volunteers, staff, and owners open `/checkin` on a phone. The camera uses `html5-qrcode` through Vite. `POST /checkin` verifies the HMAC inside a transaction, stamps `checked_in_at` once, and on a second scan returns the original IST time. A manual pass-number field is the fallback when the camera is unavailable.

```bash
# .env
PASS_SIGNING_KEY=   # php -r "echo bin2hex(random_bytes(32));"
QUEUE_CONNECTION=database
```

Tests: `php artisan test --filter=PassesTest`.
