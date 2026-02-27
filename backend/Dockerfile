FROM php:8.2-cli

# System packages cần cho composer + extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libonig-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Cài dependency Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

RUN chmod -R 775 storage bootstrap/cache || true

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000