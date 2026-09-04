FROM composer:2 AS deps

WORKDIR /app


COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    --no-interaction --ignore-platform-reqs

COPY cubo/ cubo/
COPY src/ src/
COPY public/ public/
RUN composer dump-autoload --optimize --no-dev

# ------------------------------------------------------------------------ app

FROM php:8.2-fpm-alpine AS app

RUN apk add --no-cache libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql opcache \
    && apk del .build-deps

COPY deploy/php/php.ini /usr/local/etc/php/conf.d/controla.ini

WORKDIR /var/www/html

COPY --from=deps /app/vendor/ vendor/
COPY cubo/ cubo/
COPY src/ src/
COPY templates/ templates/
COPY routing/ routing/
COPY public/ public/
COPY database/ database/


RUN mkdir -p storage/logs && chown -R www-data:www-data storage/logs

# o config.ini NAO entra na imagem: vem por bind mount, com as senhas de verdade

# ------------------------------------------------------------------------ web

FROM nginx:1.27-alpine AS web

COPY deploy/nginx/app.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
