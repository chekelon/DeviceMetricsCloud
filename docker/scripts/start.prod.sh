#!/bin/bash
set -e

mkdir -p /var/www/DeviceMetricsCloud/storage/framework/{sessions,views,cache}
mkdir -p /var/www/DeviceMetricsCloud/storage/logs
mkdir -p /var/www/DeviceMetricsCloud/bootstrap/cache

chown -R www-data:www-data /var/www/DeviceMetricsCloud/storage
chown -R www-data:www-data /var/www/DeviceMetricsCloud/bootstrap/cache
chmod -R 775 /var/www/DeviceMetricsCloud/storage
chmod -R 775 /var/www/DeviceMetricsCloud/bootstrap/cache

# En prod el .env ya debe existir — no lo generamos automáticamente
if [ ! -f /var/www/DeviceMetricsCloud/.env ]; then
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