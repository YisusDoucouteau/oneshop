#!/bin/sh
set -eu

if [ ! -d /var/www/storage/framework ]; then
    cp -a /var/www/storage-template/. /var/www/storage/
fi

mkdir -p \
    /var/www/storage/app/backups \
    /var/www/storage/app/public \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

exec "$@"
