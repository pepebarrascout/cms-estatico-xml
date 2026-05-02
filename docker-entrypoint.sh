#!/bin/bash
set -e

echo "============================================"
echo " CMS Estático XML - Iniciando contenedor"
echo "============================================"

# Iniciar servicio cron en segundo plano
echo "Iniciando cron para publicaciones programadas..."
cron

# Verificar directorios
echo "Verificando directorios..."
mkdir -p /var/www/html/data/articles
mkdir -p /var/www/html/data/categories
mkdir -p /var/www/html/data/users
mkdir -p /var/www/html/data/index
mkdir -p /var/www/html/cache
mkdir -p /var/www/html/static

# Asegurar permisos
chown -R www-data:www-data /var/www/html/data /var/www/html/cache /var/www/html/static
chmod -R 775 /var/www/html/data /var/www/html/cache /var/www/html/static

# Verificar si es la primera ejecución
if [ ! -f /var/www/html/data/settings.xml ]; then
    echo ""
    echo "============================================"
    echo " PRIMERA EJECUCIÓN DETECTADA"
    echo "============================================"
    echo ""
    echo "Accede a http://localhost:8080/admin/registrar"
    echo "para crear tu cuenta de administrador."
    echo ""
    echo "Luego configura el SMTP en:"
    echo "http://localhost:8080/admin/configuracion"
    echo ""
fi

echo "Contenedor listo. Iniciando Apache..."
echo "============================================"

# Ejecutar el comando principal (Apache)
exec "$@"
