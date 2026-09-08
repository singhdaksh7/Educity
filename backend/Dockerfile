FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM php:8.2-cli
WORKDIR /var/www/html
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libpq-dev \
    && docker-php-ext-install mbstring pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 10000
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
