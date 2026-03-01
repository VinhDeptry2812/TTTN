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
php artisan config:clear || true
php artisan cache:clear || true   # ← thêm || true vì bảng cache chưa có
php artisan route:clear || true
php artisan view:clear || true

echo "⚡ Caching config..."
php artisan config:cache || true

echo "🚀 Starting server on port ${PORT:-10000}..."
exec php artisan serve \
  --host=0.0.0.0 \
  --port=${PORT:-10000}
```

Lưu ý thêm **`exec`** trước `php artisan serve` — đây là quan trọng để process chạy đúng trong Docker.

---

### Đồng thời fix lỗi bảng cache trong `.env` trên Render

Thêm biến môi trường này trên Render Dashboard → Environment:
```
CACHE_DRIVER=file
SESSION_DRIVER=file