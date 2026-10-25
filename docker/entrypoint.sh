#!/bin/sh
# EventFlow container entrypoint. CONTAINER_ROLE picks what runs:
#   web        nginx + php-fpm on :8080 (runs migrations when RUN_MIGRATIONS=1)
#   queue      php artisan queue:work (passes, mail)
#   scheduler  php artisan schedule:work (payments:reconcile, ...)
# Any other arguments are run as a command, e.g. `php artisan tinker`.
set -e

cd /var/www/html
export PHP_FPM_MAX_CHILDREN="${PHP_FPM_MAX_CHILDREN:-10}"

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: docker compose run --rm app php artisan key:generate --show" >&2
    exit 1
fi

# Writable dirs live on the storage volume; make sure the tree exists.
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

if [ "$#" -gt 0 ]; then
    exec su-exec www-data "$@"
fi

case "${CONTAINER_ROLE:-web}" in
    web)
        su-exec www-data php artisan storage:link --force >/dev/null 2>&1 || true
        if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
            su-exec www-data php artisan migrate --force --no-interaction
        fi
        su-exec www-data php artisan optimize
        exec supervisord -c /etc/supervisor/web.conf
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
