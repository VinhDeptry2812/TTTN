FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip

RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

COPY . .

# ❌ KHÔNG tạo .env trong container
# RUN cp .env.example .env

# Clear cache để Laravel đọc ENV runtime
RUN php artisan config:clear || true
RUN php artisan cache:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true

RUN chmod -R 775 storage bootstrap/cache || true

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000