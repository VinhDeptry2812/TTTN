FROM php:8.2-fpm

# Cài system packages + PHP extensions Laravel cần
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        gd

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy file composer trước để cache layer
COPY composer.json composer.lock ./

# Cài dependency trước khi copy source
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# Copy toàn bộ source
COPY . .

# Quyền thư mục Laravel
RUN chmod -R 775 storage bootstrap/cache || true

# Generate key nếu chưa có
RUN php artisan key:generate || true

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000