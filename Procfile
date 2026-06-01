web: bash scripts/railway-start.sh
worker: php artisan queue:work --sleep=3 --tries=3 --timeout=90
reverb: php artisan reverb:start --host=0.0.0.0 --port=${PORT:-8080}
