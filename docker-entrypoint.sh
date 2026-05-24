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

echo "Running database seeders to populate dummy data..."
php artisan db:seed --force

# Create storage symlink for file serving
php artisan storage:link || true

echo "Starting Apache web server on port 80..."
exec apache2-foreground
