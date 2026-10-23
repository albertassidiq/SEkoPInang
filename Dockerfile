# syntax=docker/dockerfile:1

FROM composer:2.10.3@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc AS composer-bin

# This is the patched web runtime as well as the PHP dependency build runtime.
FROM php:8.5.12-apache-bookworm@sha256:5e4418695f9b13b75edefaff547125bf6be267e865931731bdd5946461505736 AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu72 libzip4 libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl pdo_mysql zip \
    && apt-get purge -y --auto-remove libicu-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
WORKDIR /var/www/html

FROM php-base AS vendor
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=production APP_DEBUG=false
COPY artisan composer.json composer.lock ./
COPY scripts/check-php-runtime.php ./scripts/check-php-runtime.php
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY lang ./lang
COPY public ./public
COPY resources ./resources
COPY routes ./routes
RUN mkdir -p bootstrap/cache storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs \
    && php scripts/check-php-runtime.php \
    && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && composer check-platform-reqs --no-dev

FROM node:24-bookworm-slim@sha256:55fb5f5a527ea2c5c4a95f963e48ef90aa38c7b346054fdc8ee9957a2b55d205 AS assets
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci --no-fund --no-audit
COPY --from=vendor /var/www/html/vendor ./vendor
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php-base AS production
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning
COPY --from=vendor --chown=root:root /var/www/html /var/www/html
COPY --from=assets --chown=root:root /var/www/html/public/build ./public/build
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-sekopinang.ini
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/sekopinang-entrypoint
RUN printf 'Listen 8080\n' > /etc/apache2/ports.conf \
    && a2enmod rewrite \
    && chown root:root /var/www/html \
    && chmod 755 /var/www/html \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && php scripts/check-php-runtime.php
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD ["curl", "--fail", "--silent", "--show-error", "--cookie", "/tmp/sekopinang-health.cookies", "--cookie-jar", "/tmp/sekopinang-health.cookies", "--output", "/dev/null", "http://127.0.0.1:8080/form"]
ENTRYPOINT ["sekopinang-entrypoint"]
CMD ["apache2-foreground"]
