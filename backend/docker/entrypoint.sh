#!/bin/sh

echo "⏳ Waiting for database..."
sleep 5

echo "🔧 Running migrations..."
php artisan migrate --force || true

if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force || true
fi

php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan view:cache || true
RUN php artisan l5-swagger:generate || true

echo "🚀 Starting Nginx + PHP-FPM..."
php-fpm -D
exec nginx -g "daemon off;"