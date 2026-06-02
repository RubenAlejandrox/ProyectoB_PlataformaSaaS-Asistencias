# Producción Railway (Nixpacks + php artisan serve en $PORT)
web: bash scripts/railway-start.sh
worker: php artisan queue:work --sleep=3 --tries=3 --timeout=90
reverb: php artisan reverb:start --host=0.0.0.0 --port=$PORT

# Solo desarrollo local (no usar en Railway Web):
# web-dev: php artisan migrate --force --no-interaction && php artisan serve --host=0.0.0.0 --port=$PORT
