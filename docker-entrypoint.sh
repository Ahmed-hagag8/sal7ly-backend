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

# Auto-detect APP_URL on Railway (uses RAILWAY_PUBLIC_DOMAIN env var)
if [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
    echo "Railway detected — APP_URL set to ${APP_URL}"
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

# Ensure persistent storage directories exist (Railway Volume)
echo "Preparing storage directories..."
mkdir -p /var/www/html/storage/app/public/profile_images
mkdir -p /var/www/html/storage/app/technician_documents
chown -R www-data:www-data /var/www/html/storage/app

# Create storage symlink for file serving
# Remove any existing symlink (especially broken Windows symlinks committed to git)
rm -rf /var/www/html/public/storage
php artisan storage:link || true

echo "Starting Apache web server on port 80..."
exec apache2-foreground
