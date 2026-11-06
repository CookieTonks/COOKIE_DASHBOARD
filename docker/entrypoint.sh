#!/bin/sh
set -e

mkdir -p storage/framework/sessions \
         storage/framework/cache \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

as_www() {
    su -s /bin/sh www-data -c "$1"
}

if [ "$1" = "scheduler" ]; then
    echo "Iniciando programador de tareas..."
    exec su -s /bin/sh www-data -c "php artisan schedule:work"
fi

as_www "php artisan config:clear --quiet"
as_www "php artisan migrate --force"

echo "Iniciando PHP-FPM..."
exec php-fpm
