FROM php:8.4-fpm-alpine

RUN apk add --no-cache bash git libzip-dev postgresql-dev zip \
    && docker-php-ext-install opcache pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

COPY backend/composer.json backend/composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-scripts || true; fi

COPY backend ./
