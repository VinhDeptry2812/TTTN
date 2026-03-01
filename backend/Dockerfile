FROM php:8.4-fpm

# System packages + nginx
RUN apt-get update && apt-get install -y \
    git unzip curl nginx \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip

# PHP extensions cho Laravel + MySQL
RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd zip pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy source
COPY . .

# Install dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Tạo thư mục public/vendor để chứa swagger assets
RUN mkdir -p public/vendor

# Permission chuẩn production
RUN chmod -R 775 storage bootstrap/cache

# Nginx config + entrypoint
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 10000

CMD ["/entrypoint.sh"]