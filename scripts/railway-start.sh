#!/usr/bin/env bash
# Arranque Web en Railway y local: php artisan serve (Nixpacks no incluye nginx por defecto).
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

# No usar config:cache aquí: congela SESSION_DOMAIN/APP_URL del build y rompe cookies en Railway.
php artisan config:clear 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan event:cache 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
