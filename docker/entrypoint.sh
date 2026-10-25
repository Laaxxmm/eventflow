#!/bin/sh
# EventFlow container entrypoint. CONTAINER_ROLE picks what runs:
#   web        nginx + php-fpm on :8080 (runs migrations when RUN_MIGRATIONS=1)
#   queue      php artisan queue:work (passes, mail)
#   scheduler  php artisan schedule:work (payments:reconcile, ...)
#   all        web + queue + scheduler in one container (Railway and other
#              single-service hosts; see README "Railway")
# Optional on web/all: RUN_MIGRATIONS=1, SYNC_EVENTS=1 (events:sync),
# SEED_DEMO=1 (demo data, only into an empty database).
# Any other arguments are run as a command, e.g. `php artisan tinker`.
set -e

cd /var/www/html
export PHP_FPM_MAX_CHILDREN="${PHP_FPM_MAX_CHILDREN:-10}"

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: docker compose run --rm app php artisan key:generate --show" >&2
    exit 1
fi

# Writable dirs live on the storage volume; make sure the tree exists.
mkdir -p storage/app/public storage/fonts storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

if [ "$#" -gt 0 ]; then
    exec su-exec www-data "$@"
fi

prepare_web() {
    su-exec www-data php artisan storage:link --force >/dev/null 2>&1 || true
    if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
        su-exec www-data php artisan migrate --force --no-interaction
    fi
    if [ "${SEED_DEMO:-0}" = "1" ]; then
        su-exec www-data php artisan demo:seed-once
    fi
    if [ "${SYNC_EVENTS:-0}" = "1" ]; then
        su-exec www-data php artisan events:sync
    fi
    su-exec www-data php artisan optimize
}

case "${CONTAINER_ROLE:-web}" in
    web)
        prepare_web
        exec supervisord -c /etc/supervisor/web.conf
        ;;
    all)
        prepare_web
        exec supervisord -c /etc/supervisor/all.conf
        ;;
    queue)
        su-exec www-data php artisan optimize
        exec su-exec www-data php artisan queue:work --tries=3 --backoff=10 --sleep=1 --max-time=3600
        ;;
    scheduler)
        su-exec www-data php artisan optimize
        exec su-exec www-data php artisan schedule:work
        ;;
    *)
        echo "Unknown CONTAINER_ROLE: ${CONTAINER_ROLE}" >&2
        exit 1
        ;;
esac
