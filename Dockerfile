FROM php:8.2-apache

# Etiquetas
LABEL maintainer="CMS Estático XML"
LABEL description="CMS estático basado en XML con panel de administración PHP"

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    cron \
    unzip \
    libzip-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install -j$(nproc) \
    zip \
    intl \
    opcache \
    && docker-php-ext-enable opcache

# Configuración de PHP para producción
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Personalizar php.ini
RUN { \
    echo "memory_limit = 128M"; \
    echo "upload_max_filesize = 10M"; \
    echo "post_max_size = 10M"; \
    echo "max_execution_time = 30"; \
    echo "max_input_time = 30"; \
    echo "date.timezone = UTC"; \
    echo "session.cookie_httponly = 1"; \
    echo "session.cookie_secure = 0"; \
    echo "session.use_strict_mode = 1"; \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /var/log/apache2/php-error.log"; \
    } >> /usr/local/etc/php/conf.d/cms-custom.ini

# Configuración de OPcache
RUN { \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.max_accelerated_files = 10000"; \
    echo "opcache.revalidate_freq = 60"; \
    echo "opcache.fast_shutdown = 1"; \
    echo "opcache.enable_cli = 1"; \
    echo "opcache.save_comments = 1"; \
    } >> /usr/local/etc/php/conf.d/opcache.ini

# Habilitar módulos de Apache
RUN a2enmod rewrite headers deflate expires

# Configurar Apache - VirtualHost optimizado
RUN { \
    echo "<VirtualHost *:80>"; \
    echo "    ServerAdmin webmaster@localhost"; \
    echo "    DocumentRoot /var/www/html"; \
    echo "    "; \
    echo "    <Directory /var/www/html>"; \
    echo "        Options -Indexes +FollowSymLinks"; \
    echo "        AllowOverride All"; \
    echo "        Require all granted"; \
    echo "    </Directory>"; \
    echo "    "; \
    echo "    # Logging"; \
    echo "    ErrorLog /var/log/apache2/error.log"; \
    echo "    CustomLog /var/log/apache2/access.log combined"; \
    echo "    "; \
    echo "    # Compresión"; \
    echo "    AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript application/json image/svg+xml"; \
    echo "    "; \
    echo "    # Keep-Alive"; \
    echo "    KeepAliveTimeout 5"; \
    echo "    MaxKeepAliveRequests 100"; \
    echo "</VirtualHost>"; \
    } > /etc/apache2/sites-available/000-default.conf

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar código del CMS
COPY . .

# Crear directorios necesarios con permisos correctos
RUN mkdir -p data/articles data/categories data/users data/index cache static \
    && chown -R www-data:www-data data cache static \
    && chmod -R 755 data cache static \
    && chmod -R 775 data cache static

# Configurar cron para publicar artículos programados cada minuto
RUN { \
    echo "* * * * * cd /var/www/html && php cron/publish-scheduled.php >> /var/log/cron.log 2>&1"; \
    } > /etc/cron.d/cms-cron \
    && chmod 0644 /etc/cron.d/cms-cron \
    && crontab /etc/cron.d/cms-cron

# Crear script de entrada
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Archivos .gitkeep para directorios vacíos
RUN touch cache/.gitkeep static/.gitkeep data/.gitkeep

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
