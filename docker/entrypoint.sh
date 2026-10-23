#!/bin/sh
set -eu

cd /var/www/html
php scripts/check-php-runtime.php
mkdir -p storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ "${1:-}" = apache2-foreground ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo 'APP_KEY is required. Supply the existing production key at runtime.' >&2
        exit 64
    fi
    case "${APP_DEBUG:-false}" in
        false|FALSE|False|'(false)'|0) ;;
        *)
            echo 'APP_DEBUG must be false for the production web container.' >&2
            exit 64
            ;;
    esac

    # Cache runtime environment values, never build-time credentials.
    php artisan config:cache --no-ansi
    php artisan route:cache --no-ansi
    php artisan view:cache --no-ansi
fi

# Migrations and key generation are explicit operator commands, not startup jobs.
exec docker-php-entrypoint "$@"
