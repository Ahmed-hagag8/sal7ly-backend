#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

# Fix Apache MPM at runtime (safety net)
echo "Fixing Apache MPM configuration..."
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

echo "Running production optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Configuring Apache to listen on Railway's dynamic port..."
# Default to port 80 if Railway doesn't provide $PORT
LISTEN_PORT=${PORT:-80}
sed -i "s/Listen 80/Listen ${LISTEN_PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${LISTEN_PORT}/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache web server on port ${LISTEN_PORT}..."
exec apache2-foreground
