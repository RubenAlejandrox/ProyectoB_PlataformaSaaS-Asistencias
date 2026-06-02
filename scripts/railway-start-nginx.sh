#!/usr/bin/env bash
# Arranque producción: PHP-FPM + Nginx (Nixpacks). No usar "php artisan serve" en Railway Web.
set -euo pipefail

APP_DIR="${APP_DIR:-/app}"
cd "$APP_DIR"

php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan event:cache 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

PHP_FPM_CONF="${PHP_FPM_CONF:-/assets/php-fpm.conf}"
if [[ -n "${FPM_MAX_CHILDREN:-}" && -f "$PHP_FPM_CONF" ]]; then
    sed -i "s/^pm.max_children = .*/pm.max_children = ${FPM_MAX_CHILDREN}/" "$PHP_FPM_CONF"
fi

NGINX_TEMPLATE="${NGINX_TEMPLATE:-/app/nginx.template.conf}"
NGINX_OUT="${NGINX_OUT:-/nginx.conf}"
PRESTART="${PRESTART:-/assets/scripts/prestart.mjs}"

if [[ ! -f "$NGINX_TEMPLATE" ]]; then
    echo "ERROR: No se encontró nginx.template.conf en $NGINX_TEMPLATE" >&2
    exit 1
fi

node "$PRESTART" "$NGINX_TEMPLATE" "$NGINX_OUT"

php-fpm -y "$PHP_FPM_CONF" &
exec nginx -c "$NGINX_OUT"
