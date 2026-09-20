FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    --no-interaction --ignore-platform-reqs

COPY cubo/ cubo/
COPY src/ src/
RUN composer dump-autoload --optimize --no-dev

# ------------------------------------------------------------------------ app

FROM php:8.2-fpm-alpine AS app

RUN docker-php-ext-install -j"$(nproc)" pdo_mysql opcache

COPY deploy/php.ini /usr/local/etc/php/conf.d/controla.ini

WORKDIR /var/www/html

COPY --from=deps /app/vendor/ vendor/
COPY bin/ bin/
COPY cubo/ cubo/
COPY src/ src/
COPY templates/ templates/
COPY routing/ routing/
COPY public/ public/
COPY database/ database/

RUN mkdir -p storage/logs && chown -R www-data:www-data storage/logs

COPY deploy/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ------------------------------------------------------------------------ web

FROM caddy:2-alpine AS web

COPY deploy/Caddyfile /etc/caddy/Caddyfile

COPY --from=app /var/www/html/public /var/www/html/public
