#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

echo "Running production optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting Apache web server..."
exec apache2-foreground
