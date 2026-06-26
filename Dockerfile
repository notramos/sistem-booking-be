FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring opcache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev \
    && php artisan optimize \
    && php artisan view:cache \
    && php artisan route:cache \
    && php artisan config:cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/storage

RUN ln -s /var/www/storage/app/public /var/www/public/storage

EXPOSE 9000

CMD ["php-fpm"]

FROM base AS dev

RUN composer install --no-interaction --optimize-autoloader \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/storage

RUN ln -s /var/www/storage/app/public /var/www/public/storage

EXPOSE 9000

CMD ["php-fpm"]
