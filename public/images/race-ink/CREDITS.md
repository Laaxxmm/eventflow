# Daylight Track photography

Per-sport photos used on event pages until the organiser uploads their own, and on the marketing
site. `App\View\SportImagery` reads `<set>/*.jpg|jpeg|webp` in file-name order. Every slot also
renders with no file present (a tinted graphic), and organiser uploads always win.

**Slot convention (same in every sport set):** `01` hero, wide · `02` single athlete in action ·
`03` equipment close-up · `04` venue or course, wide · `05` finish or celebration · `06` pack or crowd.

| Template | Slots |
| --- | --- |
| Gun Lap | hero 01, gallery 05 / 06 / 04 / 03 |
| Stadium Split | action panel 02, schedule photo 04 |
| Equipment Close | arch hero 03, gallery 06 / 05 |
| Night Circuit | hero 01 (dusk shot preferred), course photo 04 |
| Heritage Crest | framed photo 04 |
| Homepage | sports band `marketing/01–03`; `marketing/04` optional for login / sign-up |

Rules: JPEG, 2400 px on the long side, quality ~80, under 400 KB. Heroes 16:9; portrait or arch
crops still get a landscape file with the subject centred. Action or equipment, no posed stock
smiles, no third-party logos or readable bib sponsors as the subject, no watermarks. Images of
children (for example under-8 skating categories) only from the organiser's own photography with
consent; never stock or AI images of identifiable children. AI-generated images are marked
"AI-generated" below and replaced with real photos when available.

To fill the slots from free-licence stock, put `UNSPLASH_ACCESS_KEY` or `PEXELS_API_KEY` in `.env`
and run `php artisan imagery:fetch` (`--only=running/01` for one file, `--force` to replace).
It downloads, resizes, compresses and appends a row here. Search terms and AI prompts per file are
in `docs/design/daylight-track/DAYLIGHT_TRACK_BRIEF.md` §5.

| File | Source | Photographer | Licence |
| --- | --- | --- | --- |
