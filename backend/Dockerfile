FROM php:8.2-cli

# Cài extension cần cho Laravel + MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Thư mục làm việc
WORKDIR /app

# Copy source code
COPY . .

# Cài dependencies Laravel
RUN composer install --no-dev --optimize-autoloader

# Quyền ghi cho storage + cache
RUN chmod -R 775 storage bootstrap/cache || true

# Port Render sử dụng
EXPOSE 10000

# Start Laravel
CMD php artisan serve --host=0.0.0.0 --port=10000