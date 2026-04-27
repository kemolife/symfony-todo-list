FROM dunglas/frankenphp:1-php8.4-alpine

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    rabbitmq-c-dev \
    autoconf g++ make

RUN install-php-extensions \
    pdo_pgsql \
    redis \
    intl \
    zip \
    amqp \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV SERVER_NAME=":80"

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

RUN mkdir -p var/cache var/log var/share config/jwt && chmod -R 777 var/

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]