#!/bin/sh
set -e

if [ ! -f /app/config/jwt/private.pem ]; then
    mkdir -p /app/config/jwt
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
fi

exec docker-php-entrypoint "$@"
