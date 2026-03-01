#!/bin/sh

echo "⏳ Waiting for database..."
sleep 5

echo "🔧 Running migrations..."
php artisan migrate --force || true

echo "🌱 Running seeders..."
if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force || true
fi

echo "🧹 Clearing old cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "⚡ Caching config mới..."
php artisan config:cache   # ← quan trọng, load cors.php mới vào đây

echo "🚀 Starting server on port ${PORT:-10000}..."
php artisan serve \
  --host=0.0.0.0 \
  --port=${PORT:-10000}