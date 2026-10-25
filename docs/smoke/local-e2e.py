"""Local end-to-end run of the Day 2 checklist against php artisan serve + queue:work + Mailpit.

See docs/SMOKE.md for setup. Needs: pip install requests numpy opencv-python-headless.
Webhook secrets come from the environment (CASHFREE_WEBHOOK_SECRET, RAZORPAY_WEBHOOK_SECRET).
"""
import base64, tempfile, csv, hashlib, hmac, html, io, json, os, re, subprocess, sys, time
import cv2, numpy as np, requests

B = "http://127.0.0.1:8000"
MP = "http://127.0.0.1:8025"
APP = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
S = tempfile.mkdtemp(prefix="eventflow-e2e-")
CF_SECRET = os.environ["CASHFREE_WEBHOOK_SECRET"]
RZP_SECRET = os.environ["RAZORPAY_WEBHOOK_SECRET"]
EVENT = "/demo-runners/bengaluru-city-marathon-2027"
results = []
auth_hits = []


def rec(section, item, status, detail):
    results.append({"section": section, "item": item, "status": status, "detail": detail})
    print(f"[{status}] {section} :: {item} -- {detail}", flush=True)


def token(text):
    m = re.search(r'name="csrf-token" content="([^"]+)"', text) or re.search(r'name="_token"[^>]*value="([^"]+)"', text)
    return m.group(1)


def tinker(code):
    out = subprocess.run(["php", "artisan", "tinker", "--execute", code], capture_output=True, text=True, cwd=APP)
    return out.stdout.strip()


def artisan(*args):
    out = subprocess.run(["php", "artisan", *args], capture_output=True, text=True, cwd=APP)
    return out.returncode, out.stdout + out.stderr


def auth_gate():
    """throttle:auth is 5/min per IP; wait instead of tripping our own limiter."""
    now = time.time()
    recent = [t for t in auth_hits if now - t < 62]
    if len(recent) >= 4:
        time.sleep(62 - (now - recent[0]) + 1)
    auth_hits.append(time.time())


reg_hits = []


def reg_gate():
    now = time.time()
    recent = [t for t in reg_hits if now - t < 62]
    if len(recent) >= 4:
        time.sleep(62 - (now - recent[0]) + 1)
    reg_hits.append(time.time())


def wait_mail(to, contains=None, timeout=40):
    end = time.time() + timeout
    while time.time() < end:
        for m in requests.get(MP + "/api/v1/messages?limit=200").json()["messages"]:
            if any(a["Address"] == to for a in m["To"]) and (contains is None or contains.lower() in m["Subject"].lower()):
                return requests.get(f"{MP}/api/v1/message/{m['ID']}").json()
        time.sleep(1)
    return None


def reg_row(rid):
    return json.loads(tinker(f"echo json_encode(\\App\\Models\\Registration::withoutGlobalScopes()->find({rid})?->only(['id','status','pass_no','paid_at','checked_in_at','gateway_order_id','gateway_payment_id']));") or "null")


def signup(org, email, pw):
    s = requests.Session()
    t = token(s.get(B + "/register").text)
    auth_gate()
    r = s.post(B + "/register", data={"_token": t, "organization_name": org, "name": org + " Owner", "email": email,
                                      "password": pw, "password_confirmation": pw})
    ok_signup = r.status_code == 200 and "/account/plan" in r.url
    gated = s.get(B + "/organiser/events/create", allow_redirects=False)
    unverified_blocked = gated.status_code == 302 and "verify-email" in gated.headers.get("Location", "")
    msg = wait_mail(email, "verify")
    link = None
    if msg:
        m = re.search(r"(http://127\.0\.0\.1:8000/verify-email/[^\s\"<>\])]+)", msg["Text"])
        link = m and html.unescape(m.group(1))
    v = s.get(link) if link else None
    verified = bool(v is not None and v.status_code == 200) and tinker(f"echo \\App\\Models\\User::withoutGlobalScopes()->where('email','{email}')->value('email_verified_at') ? 'yes' : 'no';") == "yes"
    return s, ok_signup, unverified_blocked, bool(msg), verified


def login(email, pw):
    s = requests.Session()
    t = token(s.get(B + "/login").text)
    auth_gate()
    r = s.post(B + "/login", data={"_token": t, "email": email, "password": pw})
    return s, r


def register(sess, name, email, distance, dob):
    page = sess.get(B + EVENT).text
    reg_gate()
    return sess.post(B + EVENT + "/register", data={
        "_token": token(page), "participant_name": name, "email": email, "phone": "+91 98450 00000",
        "distance": distance, "dob": dob, "answers[gender]": "Female", "answers[city]": "Bengaluru",
        "answers[emergency_contact_name]": "Kin", "answers[emergency_contact_phone]": "+91 98450 11111",
        "answers[tshirt_size]": "M", "answers[blood_group]": "O+"})


def reg_id_from(url):
    m = re.search(r"/r/(\d+)/review", url)
    return int(m.group(1)) if m else None


def cf_post(body_obj, secret=CF_SECRET, sig=None):
    body = json.dumps(body_obj, separators=(",", ":"))
    ts = str(int(time.time()))
    sig = sig or base64.b64encode(hmac.new(secret.encode(), (ts + body).encode(), hashlib.sha256).digest()).decode()
    return requests.post(B + "/webhooks/cashfree", data=body, headers={
        "Content-Type": "application/json", "x-webhook-timestamp": ts, "x-webhook-signature": sig})


def rzp_post(body_obj, sig=None):
    body = json.dumps(body_obj, separators=(",", ":"))
    sig = sig or hmac.new(RZP_SECRET.encode(), body.encode(), hashlib.sha256).hexdigest()
    return requests.post(B + "/webhooks/razorpay", data=body, headers={"Content-Type": "application/json", "X-Razorpay-Signature": sig})


# ---------------------------------------------------------------- 1. orgs
SEC = "1. Organiser signup, verify, login, isolation"
pw = "E2e-Str0ng-pass!"
a, ok, blocked, mailed, verified = signup("Alpha Runners", "alpha@e2e.test", pw)
rec(SEC, "Org A signs up", "PASS" if ok else "FAIL", "POST /register -> /account/plan (trial started)")
rec(SEC, "Unverified org A is kept out of the event editor", "PASS" if blocked else "FAIL", "/organiser/events/create -> /verify-email")
rec(SEC, "Verification email arrives (Mailpit)", "PASS" if mailed else "FAIL", "subject contains 'Verify'")
rec(SEC, "Verify link verifies org A", "PASS" if verified else "FAIL", "signed /verify-email/{id}/{hash} sets email_verified_at, then opens the page the organiser was blocked from")
a2, r = login("alpha@e2e.test", pw)
rec(SEC, "Org A logs in again", "PASS" if r.url.rstrip("/").endswith("/dashboard") or "/account/plan" in r.url or "/dashboard" in r.url else "FAIL", f"landed on {r.url.replace(B, '')}")

b, ok, blocked, mailed, verified = signup("Bravo Wheels", "bravo@e2e.test", pw)
rec(SEC, "Org B signs up and verifies", "PASS" if ok and mailed and verified else "FAIL", "same flow as org A")

demo_event_id = int(tinker("echo \\App\\Models\\Event::withoutGlobalScopes()->where('slug','bengaluru-city-marathon-2027')->value('id');"))
demo_names = json.loads(tinker("echo json_encode(\\App\\Models\\Registration::withoutGlobalScopes()->where('event_id'," + str(demo_event_id) + ")->pluck('participant_name')->all());"))
bp = b.get(B + "/dashboard/participants").text
leaked = [n for n in demo_names if n and html.escape(n) in bp]
csv_b = b.get(B + "/dashboard/participants/export").text.strip().splitlines()
edit = b.get(f"{B}/organiser/events/{demo_event_id}/edit", allow_redirects=False)
rec(SEC, "Org B cannot see org demo-runners' participants", "PASS" if not leaked and len(csv_b) == 1 else "FAIL",
    f"{len(demo_names)} demo participants, {len(leaked)} visible to B; B's CSV has {len(csv_b) - 1} rows")
rec(SEC, "Org B cannot open demo-runners' event editor", "PASS" if edit.status_code in (403, 404) else "FAIL", f"HTTP {edit.status_code}")
ap = a2.get(B + "/dashboard/participants").text
rec(SEC, "Org A cannot see demo-runners' participants either", "PASS" if not [n for n in demo_names if n and html.escape(n) in ap] else "FAIL", "participants page")

# ---------------------------------------------------------------- 2. events:sync + landing
SEC = "2. events:sync and branded landing"
code, out = artisan("events:sync", "events/demo/example-marathon.yaml")
rec(SEC, "events:sync loads example-marathon", "PASS" if code == 0 and "bengaluru-city-marathon-2027" in out else "FAIL", out.strip().splitlines()[-1].strip() if out.strip() else "")
page = requests.get(B + EVENT).text
brand = "--brand-primary:#d9ff45" in page and "--brand-secondary:#ff6e61" in page
rec(SEC, "/demo-runners/bengaluru-city-marathon-2027 renders brand colours", "PASS" if brand else "FAIL", "--brand-primary:#d9ff45, --brand-secondary:#ff6e61 in the page CSS")

# ---------------------------------------------------------------- 3. registration
SEC = "3. Registration rules"
p = requests.Session()
r = register(p, "Asha Runner", "asha@e2e.test", "10K", "1990-05-01")
rid1 = reg_id_from(r.url)
row1 = reg_row(rid1) if rid1 else None
rec(SEC, "Valid registration -> Pending", "PASS" if row1 and row1["status"] == "pending" else "FAIL", f"registration #{rid1} status={row1 and row1['status']}, landed on signed review page")
before = int(tinker("echo \\App\\Models\\Registration::withoutGlobalScopes()->count();"))
r = register(requests.Session(), "Too Young", "young@e2e.test", "10K", "2020-01-01")
after = int(tinker("echo \\App\\Models\\Registration::withoutGlobalScopes()->count();"))
age_err = re.search(r"(No age band[^<]*|[^<>]*age[^<>]*band[^<>]*)", r.text, re.I)
rec(SEC, "Invalid age rejected", "PASS" if after == before and "/r/" not in r.url else "FAIL", f"no row created; message: {age_err.group(1).strip() if age_err else '(validation error shown)'}")

# full distance: shrink FM to capacity 1 via a synced YAML copy, fill it, try again
yaml = open(f"{APP}/events/demo/example-marathon.yaml").read().replace("price_inr: 1999, capacity: 500", "price_inr: 1999, capacity: 1")
tmp_yaml = os.path.join(S, "marathon-fm1.yaml")
open(tmp_yaml, "w").write(yaml)
code, out = artisan("events:sync", tmp_yaml)
r = register(requests.Session(), "Fill FM", "fill@e2e.test", "FM", "1985-03-03")
filled = reg_id_from(r.url)
r = register(requests.Session(), "One Too Many", "late@e2e.test", "FM", "1985-03-03")
full_msg = "is full" in r.text
rec(SEC, "Full distance rejected", "PASS" if filled and full_msg and "/r/" not in r.url else "FAIL", "FM capacity set to 1 via events:sync; 1st FM accepted, 2nd shows 'Full Marathon (42.2K) is full.'")
artisan("events:sync", "events/demo/example-marathon.yaml")

# ---------------------------------------------------------------- 4. payment + webhooks
SEC = "4. Payment and webhooks"
# POST /pay with dummy keys: proves the button reaches the gateway; real sandbox checkout needs keys.
rv = p.get(tinker(f"echo \\Illuminate\\Support\\Facades\\URL::temporarySignedRoute('registrations.review', now()->addHour(), ['registration' => {rid1}]);"))
pay = p.post(f"{B}/pay/{rid1}", data={"_token": token(rv.text)}, allow_redirects=False)
rec(SEC, "Review page POSTs to /pay/{registration} (Cashfree)", "FAIL" if pay.status_code >= 500 else "NOT RUN",
    f"HTTP {pay.status_code} -> {pay.headers.get('Location', '')[:60].replace(B, '')}; a 5xx means the adapter threw (see storage/logs); real sandbox checkout needs CASHFREE_APP_ID/SECRET")

order1 = f"order_e2e_cf_{rid1}"
tinker(f"\\App\\Models\\Registration::withoutGlobalScopes()->whereKey({rid1})->update(['gateway_order_id' => '{order1}']);")
cf_body = {"type": "PAYMENT_SUCCESS_WEBHOOK", "event_time": "2026-10-25T17:30:00+05:30",
           "data": {"order": {"order_id": order1, "order_amount": 899.0, "order_currency": "INR"},
                    "payment": {"cf_payment_id": f"cfp_e2e_{rid1}", "payment_status": "SUCCESS", "payment_amount": 899.0}}}
w1 = cf_post(cf_body)
time.sleep(1)
row = reg_row(rid1)
rec(SEC, "Signed Cashfree webhook -> Paid", "PASS" if w1.status_code == 200 and row["status"] == "paid" else "FAIL", f"HTTP {w1.status_code} {w1.text}; status={row['status']} paid_at={row['paid_at']}")
paid_at = row["paid_at"]
w2 = cf_post(cf_body)
row = reg_row(rid1)
events_n = tinker(f"echo \\App\\Models\\PaymentEvent::where('provider','cashfree')->where('event_id','cfp_e2e_{rid1}')->count();")
rec(SEC, "Replay same webhook -> no change", "PASS" if w2.status_code == 200 and "duplicate" in w2.text and row["paid_at"] == paid_at and events_n == "1" else "FAIL", f"HTTP {w2.status_code} {w2.text}; paid_at unchanged; payment_events rows={events_n}")
w3 = cf_post(cf_body, sig="bm90LWEtcmVhbC1zaWduYXR1cmU=")
rec(SEC, "Bad signature -> 401", "PASS" if w3.status_code == 401 else "FAIL", f"HTTP {w3.status_code} {w3.text} (code returns 400 by design today)")

# Razorpay adapter, same flow (tests-only adapter per step 3; exercised here with a local secret)
r = register(requests.Session(), "Ravi Razor", "ravi@e2e.test", "5K", "1992-08-08")
rid2 = reg_id_from(r.url)
order2 = f"order_e2e_rzp_{rid2}"
tinker(f"\\App\\Models\\Registration::withoutGlobalScopes()->whereKey({rid2})->update(['gateway_order_id' => '{order2}']);")
rz = {"id": f"evt_e2e_{rid2}", "event": "payment.captured", "payload": {"payment": {"entity": {"id": f"pay_e2e_{rid2}", "order_id": order2, "status": "captured", "amount": 59900}}}}
x1, x2, x3 = rzp_post(rz), rzp_post(rz), rzp_post(rz, sig="deadbeef")
row2 = reg_row(rid2)
rec(SEC, "Razorpay: signed -> Paid, replay -> duplicate, bad sig rejected", "PASS" if row2["status"] == "paid" and "duplicate" in x2.text and x3.status_code in (400, 401) else "FAIL",
    f"{x1.status_code}/{x2.status_code} {x2.text}/{x3.status_code}")

# ---------------------------------------------------------------- 5. reconcile
SEC = "5. Reconcile"
r = register(requests.Session(), "Webhook Lost", "lost@e2e.test", "HM", "1980-02-02")
rid3 = reg_id_from(r.url)
tinker(f"\\App\\Models\\Registration::withoutGlobalScopes()->whereKey({rid3})->update(['gateway_order_id' => 'order_e2e_lost_{rid3}', 'created_at' => now()->subMinutes(20)]);")
code, out = artisan("payments:reconcile")
row3 = reg_row(rid3)
rec(SEC, "Kill webhook, run reconcile -> Paid", "NOT RUN", f"payments:reconcile ran (exit {code}) and asked Cashfree; with dummy keys the order lookup fails so #{rid3} stays {row3['status']}. Needs sandbox keys + a real sandbox order")

# ---------------------------------------------------------------- 6. pass email
SEC = "6. Pass email"
msg = wait_mail("asha@e2e.test", None, timeout=60)
row = reg_row(rid1)
pass_no = row["pass_no"]
atts = msg["Attachments"] if msg else []
types = sorted(a["ContentType"] for a in atts)
has_ef = bool(pass_no and re.fullmatch(r"EF-[0-9A-F]{12}", pass_no) and pass_no in (msg["Text"] + msg["HTML"] + msg["Subject"]))
rec(SEC, "Pass email arrives (Mailpit) with EF- number", "PASS" if msg and has_ef else "FAIL", f"subject '{msg and msg['Subject']}', pass {pass_no}")
rec(SEC, "PNG + PDF attached", "PASS" if "image/png" in types and "application/pdf" in types else "FAIL", ", ".join(f"{a['FileName']} ({a['ContentType']}, {a['Size']} B)" for a in atts))
png = next((a for a in atts if a["ContentType"] == "image/png"), None)
payload = None
if png:
    raw = requests.get(f"{MP}/api/v1/message/{msg['ID']}/part/{png['PartID']}").content
    open(os.path.join(S, "e2e-pass.png"), "wb").write(raw)
    img = cv2.imdecode(np.frombuffer(raw, np.uint8), cv2.IMREAD_COLOR)
    payload, _, _ = cv2.QRCodeDetector().detectAndDecode(img)
mail_src = (msg["HTML"] + msg["Text"]) if msg else ""
local_qr = bool(payload) and payload.startswith(pass_no + ".") and "qrserver" not in mail_src
rec(SEC, "QR is local and decodes to the signed pass", "PASS" if local_qr else "FAIL", f"decoded from the PNG with OpenCV: {payload[:28] + '...' if payload else None}; no api.qrserver.com in the email")
code, out = subprocess.run(["grep", "-rIl", "qrserver", "app", "resources", "config", "routes"], capture_output=True, text=True, cwd=APP).returncode, ""
rec(SEC, "No external QR API anywhere in app code", "PASS" if code == 1 else "FAIL", "grep -r qrserver app resources config routes -> no matches")
rz_msg = wait_mail("ravi@e2e.test", None, timeout=30)
rec(SEC, "Razorpay-paid registration also gets its pass", "PASS" if rz_msg and len(rz_msg["Attachments"]) >= 2 else "FAIL", rz_msg and rz_msg["Subject"])

# ---------------------------------------------------------------- 7. check-in
SEC = "7. Volunteer check-in"
v, r = login("volunteer@demo.test", "password")
ck = v.get(B + "/checkin")
t = token(ck.text)
hdr = {"Accept": "application/json", "X-CSRF-TOKEN": t, "User-Agent": "Mozilla/5.0 (Linux; Android 14; Pixel 8) Mobile"}
c1 = v.post(B + "/checkin", data={"payload": payload}, headers=hdr)
row = reg_row(rid1)
rec(SEC, "Scan -> checked in", "PASS" if c1.status_code == 200 and row["checked_in_at"] else "FAIL", f"HTTP {c1.status_code} {c1.text[:120]}")
c2 = v.post(B + "/checkin", data={"payload": payload}, headers=hdr)
rec(SEC, "Second scan warns", "PASS" if "already" in c2.text.lower() and reg_row(rid1)["checked_in_at"] == row["checked_in_at"] else "FAIL", f"HTTP {c2.status_code} {c2.text[:140]}")
bad = payload[:-1] + ("0" if payload[-1] != "0" else "1")
c3 = v.post(B + "/checkin", data={"payload": bad}, headers=hdr)
rid2_row = reg_row(rid2)
bad2 = pass_no.replace(pass_no[-1], "0" if pass_no[-1] != "0" else "1") + payload[len(pass_no):]
c4 = v.post(B + "/checkin", data={"payload": bad2}, headers=hdr)
rec(SEC, "Tampered QR rejected", "PASS" if c3.status_code >= 400 and c4.status_code >= 400 else "FAIL", f"flipped signature char: HTTP {c3.status_code}; swapped pass no: HTTP {c4.status_code} -- {c3.text[:90]}")
mobile = 'name="viewport"' in ck.text and "checkin" in ck.text
rec(SEC, "Check-in page is phone-ready", "PASS" if mobile else "FAIL", "viewport meta + camera scanner script (checkin.js); real phone camera not exercised here")

# ---------------------------------------------------------------- 8. dashboard vs CSV
SEC = "8. Dashboard counts vs CSV"
o, r = login("owner@demo.test", "password")
dash = o.get(B + "/dashboard").text
tiles = dict(re.findall(r'<dt class="text-sm text-slate-500">\s*([^<]+?)\s*</dt>\s*<dd[^>]*>\s*([^<]+?)\s*</dd>', dash))
rows = list(csv.reader(io.StringIO(o.get(B + "/dashboard/participants/export").text)))
head, body = rows[0], rows[1:]
st, ck_i = head.index("Status"), head.index("Checked in at")
csv_counts = {"Registrations": len(body), "Paid": sum(1 for r_ in body if r_[st] == "paid"), "Checked in": sum(1 for r_ in body if r_[ck_i])}
dash_counts = {k: int(tiles.get(k, "-1").replace(",", "")) for k in csv_counts}
rec(SEC, "Dashboard tiles match the CSV export", "PASS" if dash_counts == csv_counts else "FAIL", f"dashboard {dash_counts} vs CSV {csv_counts}")

json.dump(results, open(os.path.join(S, "e2e-results.json"), "w"), indent=1)
print("\nSUMMARY", {s: sum(1 for r_ in results if r_["status"] == s) for s in ("PASS", "FAIL", "NOT RUN")})
