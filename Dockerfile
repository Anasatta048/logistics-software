# syntax=docker/dockerfile:1
#
# Multi-stage build: vendor (composer) and assets (vite) are cached independently,
# so a PHP-only change never reinstalls packages or re-runs the frontend build.
# Final image = php-fpm + nginx + supervisor (web, queue worker, scheduler).

# --- base: OS packages + PHP extensions + composer --------------------------
FROM php:8.2-fpm AS base

RUN DEBIAN_FRONTEND=noninteractive apt-get update -qq \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq --no-install-recommends \
        git curl unzip nginx supervisor default-mysql-client ghostscript \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# phpredis: sessions, cache and rate limits in memory instead of one database round-trip per request.
RUN pecl install redis >/dev/null && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www

# --- vendor: production composer deps (cache key: composer.json/lock) -------
FROM base AS vendor
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-scripts --no-autoloader --no-dev --no-interaction --prefer-dist --quiet

# --- assets: vite + tailwind build (node is not shipped in the final image) -
# Debian-based node (not alpine) so glibc rollup binaries install.
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund --loglevel=warn
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# tailwind.config.js scans Laravel's paginator views for class names.
COPY --from=vendor /var/www/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# --- final image -------------------------------------------------------------
FROM base AS app

COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh && mkdir -p /var/log/supervisor

COPY --from=vendor /var/www/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts \
    && rm -rf /root/.composer
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=5 \
    CMD curl -fs http://127.0.0.1/up || exit 1

CMD ["/entrypoint.sh"]
