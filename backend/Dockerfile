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

 # Generate JWT keys if not present in build context (e.g. gitignored on fresh clone)
RUN mkdir -p config/jwt \
    && if [ ! -f config/jwt/private.pem ]; then \
        openssl genrsa -out config/jwt/private.pem 4096 \
        && openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem; \
    fi

RUN mkdir -p var/cache var/log var/share && chmod -R 777 var/

EXPOSE 80