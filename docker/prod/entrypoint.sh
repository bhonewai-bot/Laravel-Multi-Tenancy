#!/bin/sh
set -eu

APP_RUNTIME_USER="${APP_RUNTIME_USER:-www-data}"
APP_RUNTIME_GROUP="${APP_RUNTIME_GROUP:-www-data}"
DB_CONNECTION="${DB_CONNECTION:-}"
DB_DATABASE="${DB_DATABASE:-}"

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

ensure_sqlite_writable() {
    if [ "${DB_CONNECTION}" != "sqlite" ] || [ -z "${DB_DATABASE}" ]; then
        return
    fi

    case "${DB_DATABASE}" in
        /var/www/*)
            db_dir="$(dirname "${DB_DATABASE}")"
            mkdir -p "${db_dir}"
            touch "${DB_DATABASE}"

            if [ "$(id -u)" -eq 0 ]; then
                chown -R "${APP_RUNTIME_USER}:${APP_RUNTIME_GROUP}" "${db_dir}"
            fi

            chmod -R ug+rwX "${db_dir}"
            ;;
    esac
}

ensure_writable_paths
ensure_sqlite_writable

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
