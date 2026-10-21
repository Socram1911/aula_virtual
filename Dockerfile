# Imagen de producción para Render (y cualquier host Docker).
# Servidor Apache + mod_rewrite + PHP 8.2 con DocumentRoot en public/.

FROM php:8.2-apache

# Dependencias del sistema para compilar extensiones.
# Nota: NO se usa --auto-remove al purgar, porque eliminaría las librerías
# de runtime (libpq5, libzip4, libxml2, libcurl4, libonig5) de las que dependen
# las extensiones compiladas (pdo_pgsql, zip, simplexml, curl, mbstring).
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install pdo_pgsql opcache simplexml curl mbstring zip \
    && apt-get purge -y libpq-dev libxml2-dev libcurl4-openssl-dev libonig-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Enrutado mediante .htaccess (RewriteEngine).
RUN a2enmod rewrite

# Composer (etapa de build).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html
WORKDIR /var/www/html

# Instala dependencias (aws-sdk-php) y genera el autoload optimizado.
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress

# Permite .htaccess y apunta el DocumentRoot a public/.
RUN sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Apache debe escuchar en el puerto que Render inyecta como $PORT.
CMD ["sh", "-c", "sed -i \"s/80/${PORT}/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
