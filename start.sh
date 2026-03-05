#!/bin/bash

# Crear directorios necesarios si no existen
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Configurar permisos
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Generar APP_KEY si no existe
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    php artisan key:generate
fi

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Ejecutar migraciones (opcional, comenta si no quieres auto-migración)
# php artisan migrate --force



# Iniciar Nginx en primer plano
echo "Iniciando Supervisor..."

# En lugar de lanzar php-fpm y nginx aquí, 
# lanzamos supervisord para que él gestione todo.
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf