FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip

RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd zip pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# ← Bỏ hết các lệnh artisan ở đây vì chưa có .env lúc build
# Chỉ fix permission thôi
RUN chmod -R 775 storage bootstrap/cache || true

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 10000

CMD ["/entrypoint.sh"]