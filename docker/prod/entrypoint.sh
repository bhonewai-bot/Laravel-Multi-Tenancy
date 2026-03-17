#!/bin/sh
set -eu

APP_RUNTIME_USER="${APP_RUNTIME_USER:-www-data}"
APP_RUNTIME_GROUP="${APP_RUNTIME_GROUP:-www-data}"

ensure_writable_paths() {
    mkdir -p \
        /var/www/storage/framework/cache \
        /var/www/storage/framework/sessions \
        /var/www/storage/framework/testing \
        /var/www/storage/framework/views \
        /var/www/storage/logs \
        /var/www/bootstrap/cache

    if [ "$(id -u)" -eq 0 ]; then
        chown -R "${APP_RUNTIME_USER}:${APP_RUNTIME_GROUP}" /var/www/storage /var/www/bootstrap/cache
    fi

    chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache
}

ensure_writable_paths

if [ "$#" -eq 0 ]; then
    set -- php-fpm
fi

if [ "$1" = "php-fpm" ]; then
    exec docker-php-entrypoint "$@"
fi

if [ "$(id -u)" -eq 0 ]; then
    exec gosu "${APP_RUNTIME_USER}:${APP_RUNTIME_GROUP}" "$@"
fi

exec "$@"
