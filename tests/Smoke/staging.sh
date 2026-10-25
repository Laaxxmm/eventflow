#!/usr/bin/env bash
# Post-deploy smoke check over HTTP(S). Read-only: safe against production.
#
#   tests/Smoke/staging.sh https://staging.example.com
#   INSECURE=1 tests/Smoke/staging.sh https://localhost   # self-signed (CI)
#
# Checks: health endpoint, HTTPS redirect, demo org page, demo landing with its
# registration form, login page, built assets, and that the webhook endpoint
# rejects unsigned calls.
set -uo pipefail

base="${1:?usage: staging.sh <base-url>}"
base="${base%/}"
curl_opts=(-sS --max-time 20 --retry 3 --retry-delay 2 --retry-all-errors)
[ "${INSECURE:-0}" = "1" ] && curl_opts+=(-k)

fail=0
check() { # name, expected status, url, [grep pattern]
    local name="$1" want="$2" url="$3" pattern="${4:-}" body status
    body="$(mktemp)"
    status="$(curl "${curl_opts[@]}" -o "$body" -w '%{http_code}' "${@:5}" "$url" || echo 000)"
    if [ "$status" != "$want" ]; then
        echo "FAIL $name: $url -> HTTP $status (want $want)"
        fail=1
    elif [ -n "$pattern" ] && ! grep -q -- "$pattern" "$body"; then
        echo "FAIL $name: $url missing \"$pattern\""
        fail=1
    else
        echo "ok   $name"
    fi
    rm -f "$body"
}

check health 200 "$base/up"
check org-page 200 "$base/demo-runners" "Bengaluru City Marathon 2027"
check landing 200 "$base/demo-runners/bengaluru-city-marathon-2027" 'name="participant_name"'
check login 200 "$base/login" 'name="password"'
check webhook-rejects-unsigned 400 "$base/webhooks/razorpay" "" -X POST -H 'Content-Type: application/json' -d '{}'

asset="$(curl "${curl_opts[@]}" "$base/login" | grep -oE '/build/assets/app-[A-Za-z0-9_-]+\.css' | head -n 1)"
if [ -n "$asset" ]; then
    check assets 200 "$base$asset"
else
    echo "FAIL assets: no built CSS linked from /login"
    fail=1
fi

if [[ "$base" == https://* ]] && [ "${INSECURE:-0}" != "1" ]; then
    check http-redirect 308 "http://${base#https://}/up"
fi

[ "$fail" -eq 0 ] && echo "Smoke OK: $base" || echo "Smoke FAILED: $base"
exit "$fail"
