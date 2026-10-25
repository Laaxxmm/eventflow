#!/usr/bin/env bash
# Deploy or update EventFlow on this VPS.
#
#   deploy/deploy.sh            pull $EVENTFLOW_IMAGE and restart
#   deploy/deploy.sh --build    build the image here from this checkout instead
#
# Idempotent: run it again after every merge to main.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root/deploy"
compose=(docker compose -f compose.prod.yml)

[ -f .env ] || { echo "deploy/.env missing: cp deploy/env.example deploy/.env and fill it in" >&2; exit 1; }
if grep -q 'CHANGE_ME' .env; then
    echo "deploy/.env still has CHANGE_ME values:" >&2
    grep -n 'CHANGE_ME' .env >&2
    exit 1
fi

set -a; . ./.env; set +a

if [ "${1:-}" = "--build" ]; then
    "${compose[@]}" build app backup
else
    "${compose[@]}" pull app queue scheduler
    "${compose[@]}" build backup
fi

if [ -z "${APP_KEY:-}" ]; then
    key="$("${compose[@]}" run --rm --no-deps -T -e APP_KEY=base64:placeholder app php artisan key:generate --show | tr -d '[:space:]')"
    [[ "$key" == base64:* ]] || { echo "could not generate APP_KEY" >&2; exit 1; }
    sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" .env
    echo "Generated APP_KEY into deploy/.env (back it up: it encrypts sessions and signed data)."
fi

"${compose[@]}" up -d --remove-orphans

echo "Waiting for the app to become healthy..."
for _ in $(seq 1 60); do
    status="$("${compose[@]}" ps --format '{{.Health}}' app 2>/dev/null || true)"
    [ "$status" = "healthy" ] && break
    sleep 5
done
[ "$status" = "healthy" ] || { "${compose[@]}" logs --tail 50 app >&2; echo "app did not become healthy" >&2; exit 1; }

# Event YAML in the image -> events table (never deletes events with registrations).
"${compose[@]}" exec -T app php artisan events:sync

# APP_URL (https://$APP_DOMAIN) must answer before we call it done.
"$root/tests/Smoke/staging.sh" "https://${APP_DOMAIN}"
