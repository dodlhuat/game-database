#!/bin/sh
set -e

# Ensure writable directories exist inside the named volumes
mkdir -p /var/www/backend/storage/framework/views \
         /var/www/backend/storage/framework/sessions \
         /var/www/backend/storage/framework/cache \
         /var/www/backend/storage/logs \
         /var/www/backend/bootstrap/cache

chown -R www-data:www-data \
    /var/www/backend/storage \
    /var/www/backend/bootstrap/cache

exec php-fpm
