#!/bin/bash
# One backup run: gzip'd mysqldump of $DB_DATABASE and a tar of /storage/app
# (passes, uploaded certificate backgrounds), copied to $BACKUP_DEST, then
# objects older than $BACKUP_RETENTION_DAYS are pruned.
#
# BACKUP_DEST is an rclone path: "backup:my-bucket/eventflow" with the remote
# defined by RCLONE_CONFIG_BACKUP_* env vars, or a local dir for testing.
set -euo pipefail

: "${DB_HOST:=mysql}" "${DB_PORT:=3306}" "${DB_DATABASE:?}" "${DB_USERNAME:?}" "${DB_PASSWORD:?}"
: "${BACKUP_DEST:?set BACKUP_DEST, e.g. backup:my-bucket/eventflow}"
: "${BACKUP_RETENTION_DAYS:=30}"

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

echo "[backup] ${stamp}: dumping ${DB_DATABASE}@${DB_HOST}"
MYSQL_PWD="$DB_PASSWORD" mysqldump \
    --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" \
    --single-transaction --quick --routines --triggers --no-tablespaces \
    --set-gtid-purged=OFF --default-character-set=utf8mb4 \
    "$DB_DATABASE" | gzip -9 > "$work/db.sql.gz"

# Refuse to upload an empty or truncated dump.
gzip -t "$work/db.sql.gz"
zcat "$work/db.sql.gz" | tail -n 1 | grep -q 'Dump completed' \
    || { echo "[backup] dump did not complete" >&2; exit 1; }

rclone copyto "$work/db.sql.gz" "${BACKUP_DEST}/db/eventflow-${stamp}.sql.gz"
echo "[backup] uploaded db/eventflow-${stamp}.sql.gz ($(du -h "$work/db.sql.gz" | cut -f1))"

if [ -d /storage/app ]; then
    tar -czf "$work/storage.tar.gz" -C /storage app
    rclone copyto "$work/storage.tar.gz" "${BACKUP_DEST}/storage/storage-${stamp}.tar.gz"
    echo "[backup] uploaded storage/storage-${stamp}.tar.gz ($(du -h "$work/storage.tar.gz" | cut -f1))"
fi

rclone delete --min-age "${BACKUP_RETENTION_DAYS}d" "${BACKUP_DEST}/db" || true
rclone delete --min-age "${BACKUP_RETENTION_DAYS}d" "${BACKUP_DEST}/storage" || true
echo "[backup] done"
