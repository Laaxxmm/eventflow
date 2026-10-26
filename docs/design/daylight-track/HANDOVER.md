# Daylight Track redesign: handover

Branch: **`feature/sDT-daylight-track`** (off `main` at `b1248b9`, after PR #32). No PR is open yet.
Brief: `DAYLIGHT_TRACK_BRIEF.md` (copied next to this file). Mockups: the `*.dc.html` files in this
folder, all 31 of them (see `README.md` for how to read them). **Mockup wins for visuals, brief wins for
behaviour.**

## Pick it up locally

```bash
git fetch origin && git checkout feature/sDT-daylight-track
composer install && npm install        # new packages: @fontsource-variable/archivo, @fontsource/cormorant-garamond (Barlow removed)
php artisan migrate                    # no new migrations in this branch; main's P1 migrations if not run yet
npm run build                          # needed after every Blade/Tailwind change before looking at pages
php artisan test                       # 401 passing at the last commit
./vendor/bin/pint --test               # PaymentController/PaymentsTest style warnings are pre-existing, not ours
```

Demo logins (local seed): `owner@demo.test` / `password` (org Demo Runners, Single Event plan).
Screenshots: the Playwright script used is reproduced at the bottom; it logs in and saves 1440 px and
390 px full-page shots, and prints `OVERFLOW` when a page scrolls sideways.

## Status by phase (brief §7)

| Phase | State | What's in it |
|---|---|---|
| 1 Tokens, fonts, components | **Done** | `resources/css/app.css` tokens (Race Ink names kept, values changed, new muted/track-deep/lilac/success/warning/danger), `tailwind.config.js` colours + `fontFamily.display` Archivo + `serif` Cormorant, `.ri-headline` (wdth 118, 800, sentence case), `.ri-kicker` without the rule, `.ri-float`/`.ri-glow`, marigold `:focus-visible` ring, reduced-motion block. `App\Landing\Theme` defaults (#4b3fe0 / #f2a93b / chalk / dusk). Components in `resources/views/components/ri/`: `button` (primary, ink, register, secondary, quiet, danger-text; old names alias), `chip` (live, paid, unpaid, trial, ended, suspended…; old names alias), new `places-bar`, `switch`, `segmented`; `photo` gained `focus`/`scrim`, the light gradient fallback always sits under the image. Tests: contrast pairs + reduced-motion in `LandingTemplatesTest`. |
| 2 App shell + core pages | **In progress** | Done: `layouts/app.blade.php` (chalk page, white sidebar column painted full height, header slot styles h2 as the 40 px headline), `layouts/navigation.blade.php` rebuilt from `Sidebar.dc.html` (org card with plan usage, New event, groups from `NavigationComposer` unchanged; icons/labels updated to the mockup), `dashboard/index` (next-race card, totals, 30-day CSS bars, Worth doing next, Upcoming/Past tables; data added in `DashboardController::index`: `next`, `nextPlaces`, `thisWeek`, `unpaid`, `daily`), `events/index` (photo cards + Alpine filter pills), `saas/plan-banner` + `saas/overage-actions` restyled. **To do:** `events/overview` (Overview.dc.html), `events/wizard` (Wizard.dc.html, all 5 steps), `dashboard/participants` + `participants-create` (Participants.dc.html), `events/edit` + `events/partials/form` (style to match). |
| 3 Remaining app pages | To do | RaceSheet, Certificates (design list left, live A4 preview right), Checkin (3 result states; `resources/js/checkin.js`), Payments, Notifications, Domain (stepper + DNS table), Modules (`<x-ri.switch>`), Billing (`marketing/account-plan` + `billing/show`), Profile, PlatformOrgs. |
| 4 Race pages | To do | 5 templates + Event (phone), Review, PassReady, `payments/return`, `mail/pass`, `emails/layout`. Keep `data-hero-cta`, sticky bar, `#register`, footer and countdown partials (P1), `MobileLandingTest`, `LandingTemplatesTest`. |
| 5 Marketing + auth | To do | Main (3D track hero, bands hero/sports/how-it-works/templates/pricing/cta), Pricing (table from config), Templates, Signup, Login, `layouts/guest`. Update `SaasTest` per brief §6. |
| 6 Images | To do | `public/images/race-ink/<set>/` folders exist but are **empty**; every slot shows the gradient fallback. Needs licensed photos per brief §5 + `CREDITS.md`. No Unsplash/Pexels key in this environment. |

## Conventions and gotchas learned here

- Run tests with a clean env: `env -i HOME=$HOME PATH=$PATH php artisan test` if you have local
  `DB_*` env vars exported (they point tests at your dev DB).
- **Blade:** an inline `@php($x = …)` earlier in a view breaks a later `@php … @endphp` block (the block
  is left uncompiled → "Undefined variable"). Use block form everywhere.
- Rebuild assets (`npm run build`) before screenshots; new Tailwind classes otherwise don't exist.
- MySQL CI differs from local SQLite: JSON columns reorder keys, `SUM()` returns strings.
- Money on the new dashboard shows whole rupees (`precision: 0`); `DashboardTest` updated to `₹1,797`.
- Keep every `__()` wrapper and every `data-*` hook the tests use (`data-trial-banner`, `data-hero-cta`,
  `data-event-thumb`, `data-watermark`, `data-countdown`, `data-event-footer`, `data-overage-actions`, …).
- Payment reminders, billing (`/dashboard/billing/{sku}`), custom domains, manual participants,
  countdown and footer are P1 features already on `main`; restyle, don't change behaviour.

## Screenshot script (scratch, not in the repo)

```js
// node shot.js <outdir> name=/path ...   (needs `php artisan serve` on :8000)
const { chromium } = require('playwright');
const base = 'http://127.0.0.1:8000';
const [out, ...pairs] = process.argv.slice(2);
(async () => {
  const browser = await chromium.launch();
  for (const [vp, w, h, mobile] of [['d', 1440, 900, false], ['m', 390, 844, true]]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: mobile ? 2 : 1, isMobile: mobile, hasTouch: mobile });
    const p = await ctx.newPage();
    await p.goto(base + '/login');
    await p.fill('input[name=email]', 'owner@demo.test');
    await p.fill('input[name=password]', 'password');
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]')]);
    for (const pair of pairs) {
      const [name, path] = pair.split('=');
      await p.goto(base + path, { waitUntil: 'networkidle' });
      const sw = await p.evaluate(() => document.documentElement.scrollWidth);
      if (sw > w + 1) console.log(`OVERFLOW ${vp} ${name}: ${sw}px`);
      await p.screenshot({ path: `${out}/${vp}-${name}.png`, fullPage: true });
    }
    await ctx.close();
  }
  await browser.close();
})();
```

## Prompt to continue in a new session

> Continue the Daylight Track redesign on branch `feature/sDT-daylight-track` in Laaxxmm/eventflow.
> Read `docs/design/daylight-track/HANDOVER.md`, `DAYLIGHT_TRACK_BRIEF.md` and the mockups in that
> folder. Phase 1 is done and phase 2 is part done (shell, sidebar, dashboard, events list). Next:
> Overview, Wizard, Participants, then phases 3–6. Keep all tests passing, run `npm run build` and take
> 1440/390 screenshots after each page, small commits, then open one PR.
