FROM php:8.4-fpm

# System libs
RUN apt-get update && apt-get install -y \
    git unzip curl nginx \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www

# Copy source
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD ["/entrypoint.sh"]