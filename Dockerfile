FROM php:8.3-fpm-alpine

# system + ext
RUN set -eux; \
    apk add --no-cache nginx supervisor icu-dev libzip-dev oniguruma bash curl tzdata; \
    docker-php-ext-install intl pdo pdo_mysql opcache

# php.ini prod
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=0'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.jit_buffer_size=32M'; \
  echo 'memory_limit=256M'; \
  echo 'expose_php=0'; \
} > /usr/local/etc/php/conf.d/prod.ini

WORKDIR /var/www/html

# >>> CI ma wgrać gotowe pliki tutaj (vendor, public, var/cache/prod, itd.)
COPY artifact/ /var/www/html/

# konfiguracja
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# uprawnienia
RUN adduser -D -u 1000 app && \
    mkdir -p /run/nginx && \
    chown -R app:app /var/www/html /run/nginx

USER app

EXPOSE 8083

# healthcheck (nginx -> php -> symfony)
HEALTHCHECK --interval=30s --timeout=3s --start-period=15s \
  CMD wget -qO- http://127.0.0.1:8083/health || exit 1

CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
