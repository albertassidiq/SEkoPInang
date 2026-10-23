#!/bin/sh
set -eu

compose() {
    docker compose --env-file .env.docker --env-file .env.mysql \
        -f compose.yaml -f compose.mysql.yaml "$@"
}

base_url=http://127.0.0.1:8080
container=$(compose ps -q app)
image=$(docker inspect --format '{{.Image}}' "$container")

compose exec -T app php scripts/check-php-runtime.php
compose exec -T app sh -ec '
    test "$(id -u)" = 33
    test -w storage/framework/views
    test -w bootstrap/cache
    test ! -w artisan
    test -s public/build/manifest.json
    test ! -e .env
    test ! -e database/docker-canary.sql
    test ! -e public/hot
    test ! -d node_modules
    test ! -d vendor/phpunit
    ! command -v node
    ! command -v composer
'

for path in /form /mitra /monitor /login; do
    curl --fail --silent --show-error --output /dev/null "$base_url$path"
done

for path in /.env /composer.json /sikopi_db.sql; do
    status=$(curl --silent --output /dev/null --write-out '%{http_code}' "$base_url$path")
    case "$status" in
        403|404) ;;
        *) echo "Private path exposed: $path ($status)" >&2; exit 1 ;;
    esac
done

status=$(curl --silent --output /dev/null --write-out '%{http_code}' "$base_url/dashboard")
test "$status" = 302

asset=$(compose exec -T app php -r 'echo json_decode(file_get_contents("public/build/manifest.json"), true)["resources/css/app.css"]["file"];')
curl --fail --silent --show-error --output /dev/null "$base_url/build/$asset"

# Check startup failures on the actual production image, without database access.
if timeout 30s docker run --rm -e APP_KEY= "$image" > /tmp/sekopinang-no-key.log 2>&1; then
    echo 'Web startup accepted a missing APP_KEY' >&2
    exit 1
fi
grep -q 'APP_KEY is required' /tmp/sekopinang-no-key.log

if timeout 30s docker run --rm -e APP_KEY=test-only -e APP_DEBUG=true "$image" > /tmp/sekopinang-debug.log 2>&1; then
    echo 'Web startup accepted APP_DEBUG=true' >&2
    exit 1
fi
grep -q 'APP_DEBUG must be false' /tmp/sekopinang-debug.log

# Uploaded files must not become executable PHP through the public storage link.
compose exec -T app sh -ec 'printf "<?php echo 123;" > storage/app/public/docker-blocked.php'
status=$(curl --silent --output /dev/null --write-out '%{http_code}' "$base_url/storage/docker-blocked.php")
compose exec -T app rm storage/app/public/docker-blocked.php
test "$status" = 403

# Simulate a legacy volume with another UID and private directory permissions.
compose run --rm --no-deps --user root --cap-add CHOWN --cap-add DAC_OVERRIDE app sh -ec '
    mkdir storage/app/docker-legacy
    printf legacy > storage/app/docker-legacy/probe
    chmod 700 storage/app/docker-legacy
    chown -R 65534:65534 storage/app/docker-legacy
'
compose run --rm --no-deps --user root --cap-add CHOWN --cap-add DAC_OVERRIDE app \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
compose exec -T app sh -ec '
    test -r storage/app/docker-legacy/probe
    rm storage/app/docker-legacy/probe
    rmdir storage/app/docker-legacy
'

test "$(docker inspect --format '{{.State.Health.Status}}' "$container")" = healthy
echo 'Docker runtime, permissions, legacy volume repair, startup guards, assets, HTTP routes and health checks passed.'
