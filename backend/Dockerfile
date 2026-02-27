FROM php:8.4-fpm

# Cài thư viện hệ thống
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip

# Cài PHP extension cần cho Laravel + MySQL
RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd zip

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy file composer trước
COPY composer.json composer.lock ./

# Cài dependency nhưng KHÔNG chạy script Laravel
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# Copy source code Laravel vào
COPY . .

# Chạy script Laravel sau khi đã có artisan
RUN php artisan package:discover --ansi || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true

# Quyền thư mục
RUN chmod -R 775 storage bootstrap/cache || true

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000