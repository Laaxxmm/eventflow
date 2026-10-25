# Branches

| Branch | Session | Purpose |
|---|---|---|
| `main` | all | Always green. Only PRs merge here (Session 1 bootstrap commit excepted). |
| `feat/a-<short>` | 1 | Foundation & contracts |
| `feat/b-<short>` | 2 | Payments (gateways, webhooks, checkout) |
| `feat/c-<short>` | 3 | Landing pages & registration form |
| `feat/d-<short>` | 4 | Passes (PNG/PDF, QR) & email |
| `feat/e-<short>` | 5 | Organiser auth & dashboard |
| `feat/f-<short>` | 6 | Check-in, race sheets & ops (CI, Docker) |

## Workflow

1. `git fetch origin && git switch -c feat/<stream>-<short> origin/main`
2. Small commits, only inside paths you own (see `OWNERSHIP.md`).
3. Before opening the PR: `git fetch origin && git rebase origin/main`.
4. Done means: `php artisan test` green, `./vendor/bin/pint --test` clean,
   your README section updated.
5. Open a PR to `main`. Contract changes go through a `feat/a-` PR first.
