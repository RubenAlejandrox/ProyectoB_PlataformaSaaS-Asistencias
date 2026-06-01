#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

# Optimización en runtime (con variables de Railway ya inyectadas)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

php artisan storage:link 2>/dev/null || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
