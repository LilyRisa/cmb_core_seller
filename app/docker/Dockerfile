# syntax=docker/dockerfile:1
#
# Image for the Laravel app — used by the `app` (php-fpm), `worker` (Horizon) and
# `scheduler` services in docker-compose.yml. Multi-stage:
#   1. node     — build the React/Vite assets into public/build
#   2. vendor   — install PHP dependencies (production)
#   3. runtime  — php-fpm + extensions + app code + built assets
#
# See docs/07-infra/environments-and-docker.md.

# ---------- 1. Frontend build ----------
FROM node:20-alpine AS node

WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- 2. Composer dependencies ----------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev --no-scripts --no-autoloader \
        --prefer-dist --no-interaction --no-progress

# ---------- 3. Runtime ----------
FROM php:8.3-fpm-alpine AS runtime

# System packages + PHP extensions Laravel/Horizon/Postgres/Redis need.
RUN apk add --no-cache \
        bash git icu-dev libzip-dev postgresql-dev libpng-dev oniguruma-dev linux-headers \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl zip bcmath pcntl gd exif opcache \
    && apk add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP / opcache tuning.
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# App source, then vendored deps and built assets from the earlier stages.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=node /build/public/build ./public/build

# Regenerate the optimized autoloader now that app/ + vendor/ are both present.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
