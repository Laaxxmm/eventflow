#!/bin/bash
# Restore test: fetch the newest dump from $BACKUP_DEST, load it into a scratch
# database on the same server, check it, and drop the scratch database.
# Exit 0 means the latest backup restores. Needs MYSQL_ROOT_PASSWORD (to create
# the scratch database). Pass a dump file name to test an older one.
set -euo pipefail

: "${DB_HOST:=mysql}" "${DB_PORT:=3306}" "${DB_DATABASE:?}" "${MYSQL_ROOT_PASSWORD:?}"
: "${BACKUP_DEST:?}"
scratch="${RESTORE_TEST_DATABASE:-eventflow_restore_test}"
export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"
mysql_root=(mysql --host="$DB_HOST" --port="$DB_PORT" --user=root --batch --skip-column-names)

dump="${1:-$(rclone lsf "${BACKUP_DEST}/db" --include 'eventflow-*.sql.gz' | sort | tail -n 1)}"
[ -n "$dump" ] || { echo "[restore-test] no dumps in ${BACKUP_DEST}/db" >&2; exit 1; }

work="$(mktemp -d)"
trap 'rm -rf "$work"; "${mysql_root[@]}" -e "DROP DATABASE IF EXISTS \`${scratch}\`" || true' EXIT

echo "[restore-test] restoring ${dump} into ${scratch}"
rclone copyto "${BACKUP_DEST}/db/${dump}" "$work/db.sql.gz"
"${mysql_root[@]}" -e "DROP DATABASE IF EXISTS \`${scratch}\`; CREATE DATABASE \`${scratch}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
zcat "$work/db.sql.gz" | "${mysql_root[@]}" "$scratch"

fail=0
for table in migrations orgs users events registrations payment_events; do
    if ! count="$("${mysql_root[@]}" -e "SELECT COUNT(*) FROM \`${scratch}\`.\`${table}\`" 2>/dev/null)"; then
        echo "[restore-test] FAIL: table ${table} missing"
        fail=1
        continue
    fi
    live="$("${mysql_root[@]}" -e "SELECT COUNT(*) FROM \`${DB_DATABASE}\`.\`${table}\`" 2>/dev/null || echo '?')"
    printf '[restore-test] %-16s restored=%-8s live=%s\n' "$table" "$count" "$live"
done

migrations="$("${mysql_root[@]}" -e "SELECT COUNT(*) FROM \`${scratch}\`.migrations" 2>/dev/null || echo 0)"
if [ "${migrations:-0}" -lt 1 ]; then
    echo "[restore-test] FAIL: no migrations recorded"
    fail=1
fi

[ "$fail" -eq 0 ] && echo "[restore-test] OK: ${dump} restores (${migrations} migrations)"
exit "$fail"
