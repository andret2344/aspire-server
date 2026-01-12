# Deps
FROM composer:2 AS deps
WORKDIR /app

COPY composer.json composer.lock symfony.lock* ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install \
      --no-interaction \
      --no-dev \
      --prefer-dist \
      --optimize-autoloader \
      --no-scripts \
      --no-progress

# Build
FROM php:8.5-cli-alpine AS build
WORKDIR /app

RUN apk add --no-cache bash

COPY . /app
COPY --from=deps /app/vendor /app/vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

ARG APP_ENV=prod
ENV APP_ENV=${APP_ENV}

# Runtime
FROM php:8.5-fpm-alpine AS runtime

COPY --from=mlocati/php-extension-installer:2.7.2 /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    chmod +x /usr/local/bin/install-php-extensions; \
    apk add --no-cache \
        nginx supervisor bash curl tzdata \
        icu-libs libzip oniguruma; \
    install-php-extensions \
        intl \
        pdo_mysql \
        opcache; \
    php -m | sort

RUN set -eux; \
    { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.jit_buffer_size=32M'; \
      echo 'memory_limit=256M'; \
      echo 'expose_php=0'; \
    } > /usr/local/etc/php/conf.d/prod.ini

WORKDIR /var/www/html

COPY --from=build /app /var/www/html

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN set -eux; \
    mkdir -p /run/nginx \
             /var/lib/nginx/tmp/client_body \
             /var/lib/nginx/tmp/proxy \
             /var/lib/nginx/tmp/fastcgi \
             /var/log/nginx; \
    chown -R nginx:nginx /run/nginx /var/lib/nginx /var/log/nginx; \
    mkdir -p /var/www/html/var; \
    chown -R www-data:www-data /var/www/html/var

EXPOSE 8083

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
