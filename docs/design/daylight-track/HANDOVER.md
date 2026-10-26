# Daylight Track redesign: handover

Branch: **`feature/sDT-daylight-track`** (off `main` at `b1248b9`, after PR #32). No PR is open yet.
Brief: `DAYLIGHT_TRACK_BRIEF.md` (copied next to this file). Mockups: the `*.dc.html` files in this
folder, all 31 of them (see `README.md` for how to read them). **Mockup wins for visuals, brief wins for
behaviour.**

## Pick it up locally

```bash
git fetch origin && git checkout feature/sDT-daylight-track
composer install && npm install        # new packages: @fontsource-variable/archivo, @fontsource/cormorant-garamond (Barlow removed)
php artisan migrate:fresh --seed        # demo YAML brand colours changed to indigo/marigold
php artisan migrate                    # no new migrations in this branch; main's P1 migrations if not run yet
npm run build                          # needed after every Blade/Tailwind change before looking at pages
php artisan test                       # all passing at the last commit
./vendor/bin/pint --test               # PaymentController/PaymentsTest style warnings are pre-existing, not ours
```

Demo logins (local seed): `owner@demo.test` / `password` (org Demo Runners, Single Event plan).
Screenshots: the Playwright script used is reproduced at the bottom; it logs in and saves 1440 px and
390 px full-page shots, and prints `OVERFLOW` when a page scrolls sideways.

## Status by phase (brief §7)

| Phase | State | What's in it |
|---|---|---|
| 1 Tokens, fonts, components | **Done** | Tokens in `resources/css/app.css` (Race Ink names, Daylight values, new muted/track-deep/lilac/success/warning/danger), Archivo (wdth 118) + Inter + Cormorant, `.ri-headline`, `.ri-input`/`.ri-label`, focus ring, motion keyframes (`ef-spin`, `ef-float`, `ef-runner`, `ef-medal`, `ef-sway`, `ef-lane`, `ef-seal`, `ef-scan`) all stopped under reduced motion. Components: `button`, `chip`, `places-bar`, `switch`, `segmented`, `photo`, `accordion` (lines or cards), `medal`, `pass-card`, `track-stage`. Breeze form components restyled. |
| 2 App shell + core pages | **Done** | Sidebar, shell, Dashboard, Events, Overview (places by distance, template swatches), Wizard (5 steps, phone preview), Edit event, Participants (tab counts, search, reminder bar), Add participant. |
| 3 Remaining app pages | **Done** | Race sheet (+ PDF), Certificates (design list + live preview), Check-in (three result states, scan line), Payments, Email and WhatsApp, Domain, Modules, Plan and billing, Buy page, checkout/return, Profile, Platform orgs, Communications, Published. |
| 4 Race pages | **Done** | Gun Lap (phone: medal hero card), Stadium Split, Equipment Close (turning seal), Night Circuit (light lanes), Heritage Crest (Cormorant). Equipment Close and Heritage Crest use house colours (green / burgundy) only while the organiser keeps the default brand colour. Shared: light header, sticky navy register bar, registration form card with pay bar, venue map card, partner cards, light footer, countdown. Review and pay, pass-ready page with QR `pass-card`, light branded emails. Demo YAML brand is now indigo/marigold. |
| 5 Marketing + auth | **Done** | Homepage bands hero (3D track, tilts on desktop), sports, how-it-works, templates (3D fan), pricing (race bibs from `config/plans.php`, Single Event highlighted), cta. Light nav/footer. Pricing, Templates, Login, Sign-up and the other auth pages. `SaasTest` updated per brief §6. |
| 6 Images | **Tooling done, photos not added** | `php artisan imagery:fetch` downloads licensed stock per brief §5 slots (needs `UNSPLASH_ACCESS_KEY` or `PEXELS_API_KEY`), resizes to 2400 px, keeps files under 400 KB and appends credits. `public/images/race-ink/CREDITS.md` documents the slot convention. No key was available here, so every slot still shows the graphic fallback. |

Known gaps: check-in header has no event/counter (controller passes none); certificate preview shows the
design thumbnail, the exact page opens as PDF; Razorpay mode is read from the key prefix so it shows a
chip, not a Test/Live toggle; platform orgs has no "Open requests" total or Activate/Grant actions (no routes).

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
> Read `docs/design/daylight-track/HANDOVER.md`. Phases 1–5 are done; phase 6 needs real photos: add
> an Unsplash or Pexels key and run `php artisan imagery:fetch`, or add organiser photography, then
> review each template at 1440/390 against the mockups and open one PR.
