#!/bin/bash
set -e

# Generate .env from environment variables if not already present
if [ ! -f /var/www/html/.env ]; then
    echo "Generating .env from environment variables..."
    cat > /var/www/html/.env <<EOF
APP_NAME=${APP_NAME:-FinTask}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-https://fintask.onrender.com}

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-stderr}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

SESSION_DRIVER=${SESSION_DRIVER:-cookie}
CACHE_STORE=${CACHE_STORE:-array}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

MAIL_MAILER=log
EOF
    chmod 644 /var/www/html/.env
fi

# Fix permissions for storage and bootstrap/cache
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Wait for database with timeout
echo "Waiting for database..."
for i in {1..60}; do
    if php artisan tinker --execute="DB::connection()->getPdo()" > /dev/null 2>&1; then
        echo "Database is ready!"
        break
    fi
    echo "Database not ready, retrying... ($i/60)"
    sleep 1
done

# Run migrations (non-blocking)
echo "Running database migrations..."
php artisan migrate --force --quiet 2>/dev/null || echo "Migrations completed or skipped"

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
