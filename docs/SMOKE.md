# Day 2 end-to-end smoke: v0.1-pilot

| | |
|---|---|
| Run date | 2026-10-25 |
| Commit | `main` @ `bece4d2` (all stream PRs merged: A #4, B #3, C #8, D #7, F #11 and follow-ups) |
| Where | **Local only**: `php artisan serve` + `queue:work` (database queue) + Mailpit (SMTP 127.0.0.1:1025), SQLite, PHP 8.3.6 |
| Not available | Staging host (`STAGING_HOST` unset, deploy job skipped), sandbox keys for Cashfree and Razorpay, Docker/MySQL, a phone. The container's network policy also blocks `sandbox.cashfree.com`. |
| Verdict | **Not ready to tag.** Two payment bugs block real checkout (below). The staging and sandbox rows still have to be run. |

Status key: **PASS** = ran and behaved as the checklist says. **FAIL** = ran and did not. **NOT RUN** = needs staging, sandbox keys or a phone.

## Baseline after the merges

| Check | Result |
|---|---|
| `composer install` | PASS |
| `php artisan migrate:fresh --seed` | PASS (needs `PLATFORM_ADMIN_PASSWORD` and `DEMO_ORGANISER_PASSWORD` set) |
| `php artisan test` | PASS: 280 tests, 1172 assertions |
| `php artisan test tests/Smoke` | PASS: 1 test, 26 assertions |

## Wiring (integrator step 2)

| Link | Where | Result |
|---|---|---|
| Registration → `/pay/{registration}` | `RegistrationController::store` redirects to a signed review page (`/r/{id}/review`); its button POSTs to `/pay/{registration}` | PASS |
| Webhook dispatches `RegistrationPaid` | `WebhookController::handle`, after commit, once per Pending→Paid; `PaymentController::return` and `payments:reconcile` dispatch it too | PASS |
| `SendPass` listens | `php artisan event:list`: `App\Listeners\SendPass@handle (ShouldQueue)` and `QueueWhatsAppConfirmation` | PASS |
| Check-in reads the pass | `CheckinController::store` verifies `SignedPassQr` and looks up `registrations.pass_no` under a row lock | PASS |

No glue changes were needed.

## Payment provider (integrator step 3)

`PAYMENT_PROVIDER` stays `cashfree`, the `.env.example` default. No sandbox keys for either gateway exist yet, so it can't yet be set to "the gateway whose sandbox keys exist". Both adapters ran in this smoke with local webhook secrets. Once keys are issued, set `PAYMENT_PROVIDER` on staging to that gateway, and keep the other adapter covered by `tests/Feature/Payments`.

## E2E checklist

| # | Checklist item | Status | Evidence |
|---|---|---|---|
| 1 | Org signs up | PASS | `POST /register` → `/account/plan` (trial started) |
| 1 | …verifies | PASS | Verification email arrived in Mailpit. Before verifying, `/organiser/events/create` → `/verify-email`. The signed link sets `email_verified_at` and returns the organiser to the page they were blocked from. |
| 1 | …logs in | PASS | Fresh session login → `/dashboard` |
| 1 | Second org cannot see first org data | PASS | Org B: 0 of the 20 demo-runners participants visible on `/dashboard/participants`. CSV export has a header and 0 rows. `/organiser/events/{demo id}/edit` → 404. Org A checked the same way. |
| 2 | `events:sync` loads example-marathon | PASS | `demo-runners/bengaluru-city-marathon-2027 … updated` |
| 2 | `/{org}/{slug}` renders brand colours | PASS | Page CSS has `--brand-primary:#d9ff45` and `--brand-secondary:#ff6e61` |
| 3 | Valid registration → Pending | PASS | Row created with `status=pending`; browser lands on the signed review page |
| 3 | Invalid age rejected | PASS | DOB 2020-01-01 (7 on race day, no band): validation error, no row created |
| 3 | Full distance rejected | PASS | FM capacity set to 1 via `events:sync` of a YAML copy. First FM accepted; second shows "Full Marathon (42.2K) is full." |
| 4 | Pay in sandbox | **FAIL** + NOT RUN | `POST /pay/{id}` → **HTTP 500**. See bug B-1 (phone) and B-2 (no error handling). With a bare 10-digit phone the adapter does reach `sandbox.cashfree.com`; this container's proxy blocks that host. Real sandbox checkout not run. |
| 4 | Webhook → Paid once | PASS | HMAC-signed Cashfree `PAYMENT_SUCCESS_WEBHOOK` → `{"status":"ok"}`, `status=paid`, `paid_at` set, 1 `RegistrationPaid` |
| 4 | Replay webhook → no change | PASS | Same body re-signed → `{"status":"duplicate"}`, `paid_at` unchanged, one `payment_events` row |
| 4 | Bad signature → 401 | **FAIL** | Returns **400** `{"error":"Invalid signature"}`. The request is still rejected and nothing changes, but the status code differs. See B-3. |
| 4 | Razorpay adapter, same flow | PASS | Signed `payment.captured` → Paid; replay → duplicate; bad signature → 400 |
| 5 | Kill webhook, run reconcile → Paid | NOT RUN | `payments:reconcile` picked up the 20-minute-old Pending row and asked Cashfree. With dummy keys the lookup fails, so it correctly stays Pending. The Paid path is covered by `RegistrationPaidDispatchTest` (fake gateway) only. |
| 6 | Pass email with EF- number | PASS (Mailpit) | Subject "You're in: Bengaluru City Marathon 2027 — pass EF-397E88E7FF9B" |
| 6 | PNG + PDF | PASS | `Pass-EF-….png` (~52 KB) and `Pass-EF-….pdf` (~880 KB) attached |
| 6 | Local QR | PASS | QR decoded from the emailed PNG with OpenCV to `EF-397E88E7FF9B.<hmac>`. No `qrserver` in the email or anywhere under `app/`, `resources/`, `config/`, `routes/`. |
| 6 | Brevo delivery | NOT RUN | Needs Brevo SMTP credentials and a verified sender domain on staging |
| 7 | Volunteer scans → checked in | PASS (HTTP) | `volunteer@demo.test` POSTs the decoded payload to `/checkin` → "Checked in." and `checked_in_at` stamped |
| 7 | Second scan warns | PASS | "Already checked in at 25 Oct 2026, 10:59 PM IST." `checked_in_at` unchanged |
| 7 | Tampered QR rejected | PASS | Flipped signature character → 422; swapped pass number → 422: "This QR does not match a signed EventFlow pass." |
| 7 | On a phone camera | NOT RUN | Page has the viewport meta and `checkin.js` scanner. A real phone needs HTTPS staging. |
| 8 | Dashboard counts and CSV export match | PASS | Dashboard tiles {Registrations 24, Paid 19, Checked in 6} = CSV {24, 19, 6} |
| 9 | No secret in git | PASS | gitleaks v8.30.1 `detect --log-opts=origin/main`: 57 commits, ~1.86 MB, **no leaks found** |

Totals from `docs/smoke/local-e2e.py`: **27 PASS, 2 FAIL** (`/pay` 500, 400-vs-401), **1 NOT RUN** (reconcile → Paid). Also not run, outside the script: real sandbox checkout, Brevo delivery, phone camera, and the whole run on staging.

## Bugs found (owners pinged, not fixed here: outside integrator glue)

- **B-1 (Session 2, `app/Payments/CashfreeGateway.php`): Indian phone numbers break Cashfree checkout.**
  - `createOrder` passes the raw phone to `CustomerDetails::setCustomerPhone`.
  - The SDK rejects anything longer than 10 characters (`InvalidArgumentException: invalid length for $customer_phone … must be smaller than or equal to 10`), before any network call.
  - Every participant who types `+91 98450 00000` (the form allows it) gets a 500.
  - Fix: normalise to the last 10 digits, and add a test with a `+91` number.
- **B-2 (Session 2, `app/Http/Controllers/PaymentController.php`): gateway errors are unhandled.**
  - Any exception from `createOrder` (bad keys, network, SDK validation) is a 500 page.
  - It should log the error and send the participant back to the review page with "Payment could not start, please try again."
- **B-3 (Sessions 2 + 6): bad webhook signature returns 400, the checklist says 401.**
  - Three places would change together: `WebhookController`, the assertion in `tests/Feature/Payments/PaymentsTest.php`, and `webhook-rejects-unsigned 400` in `tests/Smoke/staging.sh`.
  - Either change all three to 401 or amend the checklist. Security is the same either way.

## Still to run on staging before `v0.1-pilot`

1. Fix B-1 and B-2, then decide B-3.
2. Get sandbox keys for one gateway and set `PAYMENT_PROVIDER` to it on staging.
3. Set the `STAGING_HOST` variable and the `STAGING_SSH_KEY` and `STAGING_USER` secrets so **Deploy staging** runs. Then confirm `tests/Smoke/staging.sh https://<staging>` is green.
4. On staging:
   - real sandbox checkout → webhook → Paid
   - point the webhook URL elsewhere, pay, run `payments:reconcile` → Paid
   - pass email through Brevo
   - scan the pass with a phone on `/checkin`
5. Update this file with those rows, then tag `v0.1-pilot` on `main`.

## How to re-run locally

```bash
# terminal env (dummy values; never real keys in this file)
export DB_CONNECTION=sqlite DB_DATABASE=$PWD/database/e2e.sqlite APP_URL=http://127.0.0.1:8000 APP_TIMEZONE=Asia/Kolkata
export MAIL_MAILER=smtp MAIL_HOST=127.0.0.1 MAIL_PORT=1025 QUEUE_CONNECTION=database
export PAYMENT_PROVIDER=cashfree CASHFREE_APP_ID=dummy CASHFREE_SECRET_KEY=dummy CASHFREE_WEBHOOK_SECRET=local-cf
export RAZORPAY_KEY_ID=rzp_test_dummy RAZORPAY_KEY_SECRET=dummy RAZORPAY_WEBHOOK_SECRET=local-rzp
export PASS_SIGNING_KEY=local-e2e PLATFORM_ADMIN_PASSWORD=local-admin DEMO_ORGANISER_PASSWORD=local-org
touch database/e2e.sqlite && php artisan migrate:fresh --seed --force
mailpit --smtp 127.0.0.1:1025 --listen 127.0.0.1:8025 &   # go install github.com/axllent/mailpit@latest
php artisan serve --port=8000 & php artisan queue:work &
pip install requests numpy opencv-python-headless
python docs/smoke/local-e2e.py
```

The script paces itself to stay under the 5-per-minute auth and registration rate limits, so a run takes about 3 minutes.
