web: php artisan migrate --force --no-interaction && php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work --sleep=3 --tries=3 --timeout=90
reverb: php artisan reverb:start --host=0.0.0.0 --port=$PORT
