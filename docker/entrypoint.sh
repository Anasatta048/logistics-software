#!/bin/bash
set -e
cd /var/www

# Configuration comes from real environment variables (Coolify / compose), so no
# .env file is written. Fail fast on the essentials.
for v in APP_KEY APP_URL DB_HOST DB_DATABASE DB_USERNAME; do
    if [ -z "${!v}" ]; then
        echo "!! Missing required env var: $v" >&2
        exit 1
    fi
done

# Runtime dirs must exist and be writable on EVERY start: a volume mounted
# over storage/ comes back owned by root.
mkdir -p \
    storage/app/public storage/app/private \
    storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan config:clear >/dev/null 2>&1 || true

echo "==> Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
for i in $(seq 1 30); do
    if php artisan db:show >/dev/null 2>&1; then
        echo "==> MySQL is ready"
        break
    fi
    if [ "$i" = "30" ]; then
        echo "!! MySQL not reachable" >&2
        exit 1
    fi
    sleep 2
done

echo "==> Migrating..."
php artisan migrate --force

echo "==> Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

php artisan storage:link --force >/dev/null
php artisan queue:restart >/dev/null 2>&1 || true

echo "==> Starting nginx + php-fpm + queue + scheduler"
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
