FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip

RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

RUN php artisan optimize:clear

RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 storage bootstrap/cache

RUN a2enmod rewrite

EXPOSE 10000

CMD ["apache2-foreground"]