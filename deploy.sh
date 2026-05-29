#!/bin/bash
set -e

echo "==> Instalando dependencias..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Optimizando Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Ejecutando migraciones..."
php artisan migrate --force

echo "==> Listo. API en: ${APP_URL:-https://productos.axumtecnologies.com}/api"
