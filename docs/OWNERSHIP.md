# Path ownership

Each path has exactly one owning session. Only the owner edits it; anyone else
who needs a change opens an issue / asks the owner. Shared contracts change
only through a Session 1 PR.

| Session | Stream | Branch prefix | Owns |
|---|---|---|---|
| 1 | Foundation & contracts | `feat/a-` | `app/Contracts/**`, `app/Payments/Dto/**`, `app/Payments/PaymentStatus.php`, `app/Passes/PassFiles.php`, `app/Landing/EventConfig.php` + its value objects (`Branding`, `Hero`, `Distance`, `AgeBand`, `FormField`, `Faq`, `Certificate`), `app/Events/RegistrationPaid.php`, `app/Enums/**`, `app/Sports/SportPack.php`, `app/RaceSheets/{RaceSheetConfig,RaceSheet,Heat}.php`, `app/Certificates/CertificateFile.php`, `app/Support/EventSchema.php`, `config/eventflow.php`, `events/schema.json`, `events/sports/**`, `events/demo/**`, `database/migrations/**` (schema), `app/Models/**` (columns, casts, relations), `.env.example`, `compose.yaml`, `docs/**`, `tests/Feature/ContractsTest.php`, `tests/Feature/ProductRulesContractsTest.php` |
| 2 | Payments | `feat/b-` | `app/Payments/**` (except the Session 1 files above), `app/Http/Controllers/Payments/**`, `app/Http/Controllers/Checkout/**`, `routes/payments.php`, `resources/views/checkout/**`, `tests/Feature/Payments/**`, `tests/Unit/Payments/**`. Dispatches `RegistrationPaid`. |
| 3 | Landing pages & registration form | `feat/c-` | `app/Landing/**` (except Session 1 files), `app/Http/Controllers/Landing/**`, `app/Http/Controllers/Registration/**`, `app/Sports/**` (except `SportPack.php`; implements `SportPackRepository`), `routes/landing.php`, `resources/views/landing/**`, `resources/views/registration/**`, `tests/Feature/Landing/**` |
| 4 | Passes & email | `feat/d-` | `app/Passes/**` (except `PassFiles.php`), `app/Listeners/**`, `app/Mail/**`, `app/Certificates/**` (except `CertificateFile.php`; implements `CertificateRenderer`), `resources/views/passes/**`, `resources/views/certificates/**`, `resources/views/mail/**`, `tests/Feature/Passes/**`, `tests/Feature/Certificates/**`. Listens to `RegistrationPaid`. Local QR only. |
| 5 | Organiser auth & dashboard | `feat/e-` | `app/Http/Controllers/Auth/**`, `app/Http/Controllers/Dashboard/**` (incl. Org Settings module toggles), `app/Entitlements/**` (implements `Entitlements`), `app/Policies/**`, `routes/auth.php`, `routes/dashboard.php`, `resources/views/auth/**`, `resources/views/dashboard/**`, `resources/views/layouts/**`, `resources/views/components/**`, `tests/Feature/Auth/**`, `tests/Feature/Dashboard/**`, `tests/Feature/Entitlements/**` |
| 6 | Check-in, race sheets & ops | `feat/f-` | `app/Http/Controllers/CheckIn/**`, `app/RaceSheets/**` (except the DTOs above; implements `RaceSheetBuilder`), `app/Http/Controllers/RaceSheets/**`, `resources/views/race-sheets/**`, `tests/Feature/RaceSheets/**`, `routes/checkin.php`, `resources/views/checkin/**`, `resources/js/checkin/**`, `.github/workflows/**`, `docker/**`, `tests/Feature/CheckIn/**` |

## Shared files (append-only, small PRs)

`routes/web.php` (only `require` lines for your stream's route file),
`app/Providers/AppServiceProvider.php` (only your stream's bindings),
`composer.json` / `package.json` (add your own deps; rebase and re-run install on conflict),
`README.md` (only your stream's section).

## Contracts (Session 1 only)

- `app/Contracts/*` (`PaymentGateway`, `PassGenerator`, `LandingSource`, `Entitlements`, `SportPackRepository`, `RaceSheetBuilder`, `CertificateRenderer`)
- `config/eventflow.php`
- `events/schema.json`, `events/sports/**` (sport packs + `schema.json`)
- `app/Events/RegistrationPaid.php`
