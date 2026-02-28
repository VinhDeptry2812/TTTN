#!/bin/sh

echo "⏳ Waiting for database..."
sleep 5

echo "🔧 Running migrations..."
php artisan migrate --force

echo "🌱 Running seeders..."
if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "🚀 Starting server..."
php artisan serve --host=0.0.0.0 --port=10000