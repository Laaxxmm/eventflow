# Path ownership

This table reflects the session prompts and what each stream has merged.
Each path has exactly one owning session. Only the owner edits it; anyone else
who needs a change opens an issue / asks the owner. Shared contracts change
only through a Session 1 PR.

| Session | Stream | Branch prefix | Owns |
|---|---|---|---|
| 1 | Foundation & contracts | `feat/a-` | `app/Contracts/**`, `app/Payments/Dto/**`, `app/Payments/PaymentStatus.php`, `app/Passes/PassFiles.php`, `app/Landing/EventConfig.php` + its value objects (`Branding`, `Hero`, `Distance`, `AgeBand`, `FormField`, `Faq`, `Certificate`), `app/Events/RegistrationPaid.php`, `app/Enums/**`, `app/Sports/SportPack.php`, `app/RaceSheets/{RaceSheetConfig,RaceSheet,Heat}.php`, `app/Certificates/CertificateFile.php`, `app/Support/EventSchema.php`, `config/eventflow.php`, `events/schema.json`, `events/sports/**`, `events/demo/**`, `app/Models/**` (incl. `Tenancy/` org scope), `database/migrations/**`, `database/seeders/**`, `database/factories/**`, `app/Http/Controllers/Auth/**`, `app/Http/Controllers/DashboardController.php`, `app/Http/Middleware/**`, `routes/web.php` (top section), `resources/views/auth/**`, `resources/views/dashboard/**`, `.env.example`, `docker-compose.yml`, `docs/**`, `tests/Feature/Auth/**`, `tests/Feature/ContractsTest.php`, `tests/Feature/ProductRulesContractsTest.php` |
| 2 | Payments | `feat/b-` | `app/Payments/**` (except the Session 1 files above), `app/Http/Controllers/PaymentController.php`, `app/Http/Controllers/WebhookController.php`, `app/Console/Commands/ReconcilePayments.php`, `app/Providers/PaymentServiceProvider.php`, `routes/payments.php`, `resources/views/payments/**`, `tests/Feature/Payments/**`. Dispatches `RegistrationPaid`. |
| 3 | Events, YAML landings & registration | `feat/c-` | `app/Landing/**` (except Session 1 files; implements `LandingSource` and `SportPackRepository`), `app/Http/Controllers/EventController.php`, `app/Http/Controllers/PublicEventController.php`, `app/Http/Controllers/RegistrationController.php`, `app/Console/Commands/EventsSync.php`, `app/Console/Commands/EventsValidate.php`, `routes/events.php` (required last: catch-all routes), `resources/views/events/**`, `resources/views/landing/**`, `tests/Feature/Events/**` |
| 4 | Passes, email & check-in | `feat/d-` | `app/Passes/**` (except `PassFiles.php`), `app/Listeners/**`, `app/Mail/**`, `app/Http/Controllers/CheckinController.php`, `routes/checkin.php`, `resources/views/passes/**`, `resources/views/mail/**`, `resources/views/checkin/**`, `resources/js/checkin.js`, `tests/Feature/Passes/**`. Listens to `RegistrationPaid`. Local QR only. |
| 5 | Certificates & race sheets | `feat/e-` | `app/Certificates/**` (except `CertificateFile.php`; implements `CertificateRenderer`), `app/RaceSheets/**` (except the Session 1 DTOs; implements `RaceSheetBuilder`), `app/Http/Controllers/CertificateController.php`, `app/Http/Controllers/RaceSheetController.php`, `routes/reports.php`, `resources/views/certificates/**`, `resources/views/racesheets/**`, `tests/Feature/Reports/**` |
| 6 | Ops | `feat/f-` | `.github/workflows/**`, `docker/**` |

**Not assigned yet:** the `Entitlements` implementation and Org Settings module
toggles (`app/Entitlements/**`, `resources/views/settings/**`), and Breeze's
`resources/views/layouts/**` and `resources/views/components/**`.

## Shared files (append-only, small PRs)

`routes/web.php` (top section is stream A's; others add only `require` lines below the marker),
`bootstrap/app.php` (stream A registers `RestrictVolunteers` in the web group),
`app/Providers/AppServiceProvider.php` (only your stream's bindings),
`composer.json` / `package.json` (add your own deps; rebase and re-run install on conflict),
`README.md` (only your stream's section).

## Contracts (Session 1 only)

- `app/Contracts/*` (`PaymentGateway`, `PassGenerator`, `LandingSource`, `Entitlements`, `SportPackRepository`, `RaceSheetBuilder`, `CertificateRenderer`)
- `config/eventflow.php`
- `events/schema.json`, `events/sports/**` (sport packs + `schema.json`)
- `app/Events/RegistrationPaid.php`
