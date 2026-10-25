#!/bin/bash
# Runs backup.sh every day at $BACKUP_AT (HH:MM, UTC; default 21:00 UTC = 02:30 IST).
# Logs go to the container's stdout: docker compose logs backup
set -uo pipefail

at="${BACKUP_AT:-21:00}"
echo "[schedule] nightly backup at ${at} UTC to ${BACKUP_DEST:-<unset>}"

while true; do
    now="$(date -u +%s)"
    next="$(date -u -d "today ${at}" +%s)"
    [ "$next" -le "$now" ] && next="$(date -u -d "tomorrow ${at}" +%s)"
    sleep $((next - now))
    backup.sh || echo "[schedule] backup FAILED at $(date -u)" >&2
done
