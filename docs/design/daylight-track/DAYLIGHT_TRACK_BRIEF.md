# EventFlow redesign: "Daylight Track" — implementation brief

You are implementing a full visual redesign of EventFlow (Laravel 12, Blade, Tailwind, Alpine) in this repository. The current look ("Race Ink", see `docs/design/race-ink.md`) is too dark and harsh. The new look is light, calm and premium, with one memorable 3D moment per page.

The mockups are in `docs/design/daylight-track/` (copy the folder `daylight-track-mockups/` there first). Each `.dc.html` file is one screen with inline styles, so every colour, size and line of copy can be read straight from it. Read `docs/design/daylight-track/README.md` for how to read them. **When this brief and a mockup disagree, the mockup wins for visuals and this brief wins for behaviour.**

Work in the phases at the end. Keep every existing feature, route, permission and test intent. This is a reskin and re-layout, not a rewrite of business logic.

---

## 1. Design rules (apply everywhere)

- **Light first.** Page background is Chalk `#F3F5FA`; cards are white with no border and no shadow. Dark colour is never a full page. The only dark areas: Night Circuit's hero, the Midnight Elite certificate, and the check-in camera view.
- **One accent at a time.** Indigo for brand and primary actions. Marigold only for small highlights (Live badge, places-left warnings, race-page Register button), always with navy text on it.
- **Type:** Archivo (width axis 118, weight 800, letter-spacing −0.02em) for headings and big numbers, in **sentence case** — no more all-caps headlines. Inter for everything else. No all-caps eyebrow labels above headings, no "→" on links, no "A · B · C" proof lines.
- **Shape:** pill buttons, tabs and badges (`border-radius: 999px`); 22–28px radius cards; 12–14px inputs.
- **Motion:** one orchestrated moment per page (homepage 3D track, Equipment Close seal, Night Circuit lanes, medal on the phone race page, check-in scan line). Easing `cubic-bezier(.2,.7,.2,1)`. Everything stops under `prefers-reduced-motion: reduce`. No fade-up on every section; remove the `data-reveal` defaults from pages that use the new layouts.
- **Accessibility:** contrast pairs in §2 all pass WCAG AA. Real `<button>`/`<a>`/`<label>` elements, visible focus ring (`outline: 3px solid #F2A93B; outline-offset: 2px`), touch targets ≥ 44px, `aria-pressed`/`aria-selected`/`aria-current` on toggles, tabs and nav.
- **Copy:** use the copy in the mockups. Plain verbs, sentence case. Keep every `__()` translation wrapper.

## 2. Tokens

Replace the values in `resources/css/app.css` (keep the variable names so existing Tailwind classes keep working) and add the new ones:

```css
:root {
  --ri-paper: 243 245 250;      /* #F3F5FA chalk — page background (was cream) */
  --ri-ink: 18 26 51;           /* #121A33 navy — text, ink buttons (was near-black) */
  --ri-track: 75 63 224;        /* #4B3FE0 indigo — brand, primary buttons, links */
  --ri-on-track: 255 255 255;
  --ri-track-text: 75 63 224;   /* indigo as text on white: 6.8:1 */
  --ri-heat: 242 169 59;        /* #F2A93B marigold — small highlights, race Register */
  --ri-on-heat: 18 26 51;       /* navy on marigold: 8.6:1 */
  --ri-mist: 221 226 238;       /* #DDE2EE — borders, empty bars */
  --ri-midnight: 27 34 68;      /* #1B2244 dusk — Night Circuit hero only */
  --ri-volt: 242 169 59;        /* retired: alias to marigold */
  /* new */
  --ri-muted: 74 83 114;        /* #4A5372 secondary text: 6.9:1 on chalk */
  --ri-track-deep: 43 35 144;   /* #2B2390 text on lilac, pressed */
  --ri-lilac: 236 234 255;      /* #ECEAFF selected, active nav, soft panels */
  --ri-success: 30 122 92;      /* #1E7A5C, text #0B4A38 on #E3F4EE */
  --ri-warning: 138 83 0;       /* #8A5300, text on #FDF0DA */
  --ri-danger: 161 42 42;       /* #A12A2A, text #6E1414 on #FBE4E4 */
}
```

Add `muted`, `track-deep`, `lilac`, `success`, `warning`, `danger` to `tailwind.config.js` with the same `token()` helper. Update `App\Landing\Theme::raceInk()` so its fallback volt is `#f2a93b` and its default colours match these; keep all its contrast-fixing logic and keep `tests/Feature/Events/LandingTemplatesTest.php` passing (update expected default hex values only).

**Fonts.** Replace Barlow Condensed with Archivo variable from `@fontsource-variable/archivo`, using the stylesheet that includes the `wdth` axis (check the package README for the exact file name). Keep Inter. Add `@fontsource/cormorant-garamond` (600, 700) for Heritage Crest and two certificate designs. Set `fontFamily.display` to Archivo and update `.ri-headline` to:

```css
.ri-headline { font-family: theme('fontFamily.display'); font-variation-settings: 'wdth' 118;
  font-weight: 800; letter-spacing: -0.02em; line-height: 1.02; text-transform: none; }
```

Remove the `::before` rule from `.ri-kicker` and stop using kickers as eyebrows on new layouts.

**Shadows:** only on floating things (pass card, primary buttons, phone frames): `0 30px 60px -30px rgb(18 26 51 / .5)`; primary button glow `0 16px 32px -16px rgb(75 63 224 / .7)`.

## 3. Shared components (`resources/views/components/ri/`)

- `button`: variants `primary` (indigo, white), `ink` (navy, white), `register` (marigold, navy), `secondary` (white, 1px `#C5CBDB` border), `quiet` (chalk), `danger-text`. All pills, 13–18px vertical padding by size, weight 600–700.
- `chip` (status badge): `live` marigold/navy, `paid` `#E3F4EE`/`#0B4A38`, `unpaid` `#FDF0DA`/`#6B4A12`, `trial` lilac/deep indigo, `ended` mist/`#3B4463`, `suspended` `#FBE4E4`/`#6E1414`.
- New `places-bar`: 8px lilac track; indigo fill; marigold fill and warning text when ≤ 10 left; grey `#9AA2BA` when full.
- New `switch`: native checkbox with `role="switch"` styled as in `Modules.dc.html`.
- New `segmented` (pill tab group) as used for Desktop/Mobile, Test/Live, Email/WhatsApp.
- `photo`: keep the existing fallback logic (organiser upload → `SportImagery` set → graphic). Change the graphic fallback to the tinted gradients in the mockups. Add props `focus` (CSS `object-position`) and `scrim` (bottom gradient `rgb(18 26 51 / .55)` for text on photos).
- New `track-stage` (homepage hero 3D track), `pass-card` (QR pass), `medal` (phone race page): build from `Main.dc.html`, `PassReady.dc.html`, `Event.dc.html`. Pure CSS 3D transforms and keyframes, no WebGL. The hero tilts towards the pointer on desktop (small Alpine handler) and is static on touch devices.
- App sidebar: rebuild `layouts/navigation.blade.php` from `Sidebar.dc.html` (white, lilac active item, org card with plan usage, New event button, user block at the bottom). Keep `NavigationComposer` groups exactly as they are, including the Indefine group shown only to platform admins.

## 4. Page map

| Mockup | Blade file(s) | Notes |
| --- | --- | --- |
| `Main` | `marketing/home.blade.php`, `marketing/partials/*` | New bands in order: `hero`, `sports`, `how-it-works`, `templates`, `pricing`, `cta`. Single Event is the highlighted bib (`plans.single_event.highlight`). All prices from `config/plans.php`. |
| `Pricing` | `marketing/pricing.blade.php` | Comparison table rows driven from `config/plans.php`, not hard-coded. |
| `Templates` | `marketing/templates.blade.php`, `partials/template-card` | |
| `Signup`, `Login` | `auth/register`, `auth/login`, `layouts/guest` + other auth views in the same style | |
| `GunLap`, `StadiumSplit`, `EquipmentClose`, `NightCircuit`, `HeritageCrest` | `landings/templates/*.blade.php`, `landings/partials/*` | Keep template keys and aliases. Keep organiser colour tinting through `Theme`. Every template keeps logo slot, hero, distances with prices and places left, schedule, venue, Register (sticky header desktop, bottom bar mobile, form at `#register`), FAQ, sponsors or "Organised by". |
| `Event`, `Review`, `PassReady` | mobile landing, `landing/review`, `payments/return`, `mail/pass`, `emails/layout` | Registration form is one card, the distance summary sits above it, the pay bar is fixed to the bottom. |
| `Dashboard` | `dashboard/index` | Next-race card, four totals, 30-day bars (simple CSS bars, no chart library), "Worth doing next", upcoming table. |
| `Events` | `events/index` | Photo cards, not a table. Thumbnail logic already exists in this view. |
| `Overview` | `events/overview` | Desktop/Mobile toggle and "try a template" preview keep their current behaviour, restyled. |
| `Wizard` | `events/wizard` | Five-step progress bar. Mockup shows step 3. Apply the same card style to the other steps. |
| `Participants` | `dashboard/participants`, `participants-create` | Bulk reminder bar keeps the quiet-hours message. |
| `RaceSheet` | `racesheets/show`, `racesheets/pdf` | |
| `Certificates` | `certificates/index` | Design list on the left, live A4 preview on the right. |
| `Payments`, `Notifications`, `Domain`, `Modules`, `Billing` | `settings/payments`, `notifications/settings`, `settings/domain`, `settings/edit`, `billing/show` + `marketing/account-plan` | |
| `Profile` | `profile/edit` + partials | |
| `PlatformOrgs` | `platform/orgs/index` | |
| `Checkin` | `checkin/index`, `resources/js/checkin.js` | Three result states: checked in (green), already checked in (amber, shows first time), not recognised (red). |

Sample names, numbers and organisations in the mockups are placeholders; render real data. Anything in square brackets in a mockup (for example `[COUNT]`) is data to wire up.

## 5. Images

Use the existing pipeline: `App\View\SportImagery` reads `public/images/race-ink/<set>/NN.jpg` in file-name order. Every slot must render fine with no file present (graphic fallback). Organiser uploads always win.

**Slot convention (same in every sport set):** `01` hero, wide · `02` single athlete in action · `03` equipment close-up · `04` venue or course, wide · `05` finish or celebration · `06` pack or crowd.

**Which template uses which slot:** Gun Lap hero 01, gallery 05/06/04/03 · Stadium Split action panel 02, schedule photo 04 · Equipment Close arch hero 03, gallery 06/05 · Night Circuit hero 01 (dusk shot preferred), feature 04 · Heritage Crest framed photo 04. Homepage sports band uses `marketing/01–03`. The login and sign-up mockups use a plain indigo panel; if you keep a photo there instead, point `layouts/guest.blade.php` at `marketing/04` (change `SportImagery::pick('marketing', 1)` to index `3`).

**File rules** (from `CREDITS.md`): JPEG, 2400 px on the long side, quality ~80, under 400 KB. Heroes 16:9. Slots shown in portrait or arch crops (Stadium Split 02, Equipment Close 03) still get a landscape file with the subject centred, plus a `focus` value. Action or equipment, no posed stock smiles, no third-party logos or readable bib sponsors as the subject, no watermarks. Record every file in `public/images/race-ink/CREDITS.md` (file, source URL, photographer, licence).

**How to get them.** Pick one route per image:
1. Organiser or Indefine's own race photography (best).
2. Free-licence stock (Unsplash or Pexels licence). If you have network access and an API key in `.env` (`UNSPLASH_ACCESS_KEY` or `PEXELS_API_KEY`), write `php artisan imagery:fetch` that searches with the terms below, downloads, resizes to 2400 px, compresses, saves under the right name and appends the credit line. Never hot-link.
3. AI-generated with the prompts below (Adobe Firefly, Imagen, Midjourney or similar). Mark them "AI-generated" in `CREDITS.md`, check hands, faces and text, and replace with real photos when available.

For every AI prompt below, append this style line:
> Photorealistic editorial sports photograph, natural light, full-frame camera, 35mm lens, shallow depth of field, Indian setting and Indian athletes, candid not posed, no text, no logos, no watermarks, 16:9.

| File | Slot / where | AI prompt | Stock search terms | Focus |
| --- | --- | --- | --- | --- |
| `marketing/01.jpg` | Homepage sports band, Running | A crowded city marathon start line at first light, runners shoulder to shoulder, warm side light across faces, slight motion | marathon start line morning; city run crowd | center |
| `marketing/02.jpg` | Homepage sports band, Cycling | Road cyclists climbing a winding hill road at golden hour, low sun behind them, long shadows | cyclists hill climb sunset; road cycling group | center |
| `marketing/03.jpg` | Homepage sports band, Skating | Inline speed skate wheels on an indoor rink, low angle, rink lights blurred behind | inline skates close up rink; speed skating wheels | center bottom |
| `marketing/04.jpg` | Login and sign-up background (optional) | Wide aerial of a stadium running track in morning light, a few runners on the lanes | running track aerial; stadium track morning | center |
| `running/01.jpg` | Running hero | Wide side-angle shot of a crowded marathon start in an Indian city at dawn, pale sky in the upper left for headline text | marathon start dawn; runners start line wide | 70% 50% |
| `running/02.jpg` | Single runner | One runner mid-stride, tight crop from knees up, motion blur behind, determined expression | runner mid stride; runner motion blur | center 30% |
| `running/03.jpg` | Equipment | Running shoes and a race bib pinned to a vest on a wooden bench, morning light | running shoes race bib; marathon gear flat lay | center |
| `running/04.jpg` | Venue | Runners on a stadium track seen from the stands, wide, early morning haze | stadium running track runners; athletics track wide | center |
| `running/05.jpg` | Finish | Runner crossing a finish-line arch with arms raised, crowd and volunteers cheering | marathon finish line arms up; finish arch runner | center 35% |
| `running/06.jpg` | Pack | A pack of runners on a tree-lined park road, dappled light | runners park road group; marathon pack trees | center |
| `cycling/01.jpg` | Cycling hero (Night Circuit) | Riders with front lights on a hill road at dusk, long exposure light trails, deep blue sky | night cycling lights; cyclists dusk long exposure | center 60% |
| `cycling/02.jpg` | Single rider | One road cyclist out of the saddle on a climb, side view, sweat and effort | cyclist climbing out of saddle; road cyclist effort | center |
| `cycling/03.jpg` | Equipment | Close-up of a road bike drivetrain and helmet on the grass before a race | road bike chainring close up; cycling helmet bike | center |
| `cycling/04.jpg` | Course | Hairpin bends of a hill road seen from above, lit by a line of bike lights at dusk | hairpin road aerial; winding mountain road | center |
| `cycling/05.jpg` | Finish | Cyclist crossing the line with one arm up, spectators behind barriers | cycling race finish; cyclist victory arm | center 35% |
| `cycling/06.jpg` | Pack | A tight peloton on a flat road, motion blur on the wheels | peloton road; group cycling race | center |
| `skating/01.jpg` | Skating hero | Speed skaters leaning into the bend of an indoor rink, low wide angle | speed skating race rink; inline skating race | center |
| `skating/02.jpg` | Single skater | One skater in a low crouch on the straight, arms swinging, motion blur | inline speed skater; skater crouch speed | center |
| `skating/03.jpg` | Equipment (Equipment Close hero) | Inline speed skate wheels and boot on the rink floor, very low angle, rink lights as bokeh | inline skate wheels close up; skate boot rink | center 65% |
| `skating/04.jpg` | Venue | An empty indoor skating rink with lane markings, wide | indoor skating rink empty; roller rink track | center |
| `skating/05.jpg` | Podium | Medals and flowers on a small podium after a race, faces not the focus | medals podium close up; sports medals | center |
| `skating/06.jpg` | Pack | Skaters bunched together on the bend, helmets and suits, blur | group inline skaters race; skaters pack bend | center |
| `generic/01.jpg` | Fallback hero | Wide finish-line straight at a stadium in morning light, a few athletes, no logos | stadium finish line; athletics stadium morning | center |
| `generic/02.jpg` | Fallback action | One athlete sprinting on a track, side view | sprinter track side view; athlete sprint | center |
| `generic/03.jpg` | Fallback equipment | Starting blocks and a stopwatch on a running track | starting blocks track; stopwatch track | center |
| `generic/04.jpg` | Heritage Crest / venue | A heritage palace road in an Indian city at sunrise, runners passing in the foreground | heritage city road sunrise runners; palace road morning | center 60% |
| `generic/05.jpg` | Fallback celebration | Finishers with medals around their necks hugging after a race | finishers medals celebration; race medal hug | center |
| `generic/06.jpg` | Fallback crowd | Spectators cheering along a race course barrier | race spectators cheering; marathon crowd cheering | center |

Images of children (for example under-8 skating categories) only from the organiser's own photography with consent. Do not use stock or AI images of identifiable children.

## 6. Tests

- Update `tests/Feature/Marketing/SaasTest.php` for the new homepage: band order `hero, sports, how-it-works, templates, pricing, cta`; proof line is now "14 days free. No card, no call, and we never take a cut of your entry fees."; nav order Templates, Pricing, Log in, Start free trial; CTA band still has exactly one button and no `mailto:`; the "no template grid on /" rule becomes "templates band shows a 3D fan, the full gallery stays on /templates".
- Keep the contrast assertions in `LandingTemplatesTest` and add cases for the new default colours.
- Add a reduced-motion check: pages render with no `animation` on `.ef-spin`/hero elements when `prefers-reduced-motion` is set (or assert the CSS media query exists).
- Run `php artisan test`, `./vendor/bin/pint --test` and `npm run build` after each phase.

## 7. Phases

1. Tokens, fonts, `.ri-headline`, button/chip/switch/segmented components. Ship this alone: existing pages should already look calmer.
2. App shell: sidebar and page header, then Dashboard, Events, Overview, Wizard, Participants.
3. Remaining app pages: race sheet, certificates, check-in, all settings, billing, profile, platform admin.
4. Race page templates, mobile race page, review, pass return page and pass email.
5. Marketing site and auth pages, including the 3D track hero.
6. Images: populate `public/images/race-ink/` per §5 and fill in `CREDITS.md`.

After each phase, take desktop (1440 px) and phone (390 px) screenshots of the changed pages and compare them with the mockups.

## 8. Done when

- Every page in §4 matches its mockup at 1440 px and works at 390 px with no sideways scroll.
- No page has a dark full-page background except as listed in §1.
- All text pairs meet the contrast in §2; keyboard focus is visible everywhere.
- Every image slot shows a licensed photo or the graphic fallback, never a broken image, and every photo is in `CREDITS.md`.
- All existing tests pass, with the updates in §6.
