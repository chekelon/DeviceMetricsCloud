#!/bin/bash
set -e

mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# En prod el .env ya debe existir — no lo generamos automáticamente
if [ ! -f /var/www/html/.env ]; then
    echo "ERROR: .env no encontrado. El deploy debe proveerlo."
    exit 1
fi

php artisan package:discover --ansi

# Optimizaciones de producción (en dev usabas config:clear, aquí cacheamos)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Iniciando Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf