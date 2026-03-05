# --- ETAPA 1: Obtener Node ---
FROM node:24-alpine AS node_engine

# --- ETAPA 2: Tu imagen de PHP ---
FROM php:8.2-fpm-alpine

# Copiamos Node y NPM desde la etapa anterior
COPY --from=node_engine /usr/local/bin/node /usr/local/bin/node
COPY --from=node_engine /usr/local/lib/node_modules /usr/local/lib/node_modules
# Creamos el enlace simbólico para que el comando 'npm' funcione
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
# Instalar dependencias
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    bash



# Instalar extensiones de PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip



# Instalar Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Nginx
RUN rm -rf /etc/nginx/http.d/*
COPY nginx.conf /etc/nginx/http.d/default.conf

# Crear directorio para la aplicación
WORKDIR /var/www/html

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80


# Instalar Supervisor
RUN apk add --no-cache supervisor

# Crear directorios para logs de supervisor
RUN mkdir -p /var/log/supervisor

RUN mkdir -p /var/log/supervisor && chown -R www-data:www-data /var/log/supervisor
RUN mkdir -p /var/run && chmod 777 /var/run

# Copiar la configuración de supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar script de inicio
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]