#!/bin/sh

echo "⏳ Waiting for database..."
sleep 5

echo "🔧 Running migrations..."
php artisan migrate --force || true

echo "📄 Publishing Swagger assets..."
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force || true

echo "📄 Generating Swagger docs..."
php artisan l5-swagger:generate || true

echo "🧹 Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "⚡ Caching config + views..."
php artisan config:cache || true
php artisan view:cache || true

echo "🚀 Starting services..."
php-fpm -D
exec nginx -g "daemon off;"