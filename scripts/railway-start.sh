#!/usr/bin/env bash
# Desarrollo local rápido. En Railway usa scripts/railway-start-nginx.sh (Nginx + PHP-FPM).
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan event:cache 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
