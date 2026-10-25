# syntax=docker/dockerfile:1
#
# Production image: nginx + php-fpm under supervisord (role "web"), or a queue
# worker / scheduler from the same image (roles "queue", "scheduler").
# Build:  docker build -t eventflow .
# Run:    see deploy/compose.prod.yml

ARG PHP_IMAGE=php:8.3-fpm-alpine
ARG NODE_IMAGE=node:22-alpine

# --- PHP runtime with the extensions the app needs ------------------------
FROM ${PHP_IMAGE} AS base

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype libwebp \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev \
        libjpeg-turbo-dev freetype-dev libwebp-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pcntl pdo_mysql zip \
    && apk del .build-deps

WORKDIR /var/www/html

# --- Composer dependencies (no dev) --------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && composer check-platform-reqs --no-dev

# --- Front-end assets ----------------------------------------------------
FROM ${NODE_IMAGE} AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

# --- Final image ---------------------------------------------------------
FROM base AS app

RUN apk add --no-cache nginx supervisor tini su-exec \
    && mkdir -p /run/nginx /var/log/supervisor \
    && chown -R www-data:www-data /var/lib/nginx

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-eventflow.ini
COPY docker/php/fpm.conf /usr/local/etc/php-fpm.d/zz-eventflow.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisor/web.conf /etc/supervisor/web.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

COPY --chown=www-data:www-data --from=vendor /var/www/html /var/www/html
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build

RUN rm -rf node_modules tests .git .env \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CONTAINER_ROLE=web

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD if [ "$CONTAINER_ROLE" = "web" ]; then wget -qO- http://127.0.0.1:8080/up >/dev/null; fi

ENTRYPOINT ["/sbin/tini", "--", "entrypoint"]
CMD []
