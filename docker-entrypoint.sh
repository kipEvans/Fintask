#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for database connection..."
for i in {1..30}; do
    if php artisan tinker --execute="DB::connection()->getPdo()" 2>/dev/null; then
        echo "Database is ready!"
        break
    fi
    echo "Database not ready, retrying... ($i/30)"
    sleep 2
done

# Run migrations
echo "Running migrations..."
php artisan migrate --force --quiet || true

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
