#!/bin/bash
set -e

# Fix permissions for storage and bootstrap/cache
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Attempt to run migrations (non-blocking if database unavailable)
echo "Attempting migrations (if database available)..."
php artisan migrate --force --quiet 2>/dev/null || echo "Database not available - skipping migrations"

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
