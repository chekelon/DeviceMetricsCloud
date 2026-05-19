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

# Generar APP_KEY si no está definido en el .env
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force
fi

# Esperar MySQL con reintentos
echo "Esperando a que MySQL esté listo..."
until php -r "
try {
    new PDO('mysql:host=${DB_HOST};port=3306', '${DB_USERNAME}', '${DB_PASSWORD}');
    exit(0);
} catch(Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    echo "MySQL no está listo, esperando 3 segundos..."
    sleep 3
done
echo "MySQL listo!"

php artisan package:discover --ansi

#Correr migraciones automaticamente
php artisan migrate --force

# Crear usuario admin si no existe
php artisan db:seed --class=AdminUserSeeder --force


# Optimizaciones de producción (en dev usabas config:clear, aquí cacheamos)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache


echo "Iniciando Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf