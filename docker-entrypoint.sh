#!/bin/bash
set -e

echo "====== FINTASK ENTRYPOINT STARTING ======"

# Print environment variables for debugging
echo "Database Environment Variables:"
echo "  DB_CONNECTION=${DB_CONNECTION:-not set}"
echo "  DB_HOST=${DB_HOST:-not set}"
echo "  DB_PORT=${DB_PORT:-not set}"
echo "  DB_DATABASE=${DB_DATABASE:-not set}"
echo "  DB_USERNAME=${DB_USERNAME:-not set}"

# Force generate .env from environment variables (always overwrite)
echo ""
echo "Generating .env from environment variables..."
cat > /var/www/html/.env <<EOF
APP_NAME=${APP_NAME:-FinTask}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-https://fintask.onrender.com}

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-stderr}
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

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
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
EOF

chmod 644 /var/www/html/.env
echo ".env file created successfully"

# Show what was written to .env (first 15 lines)
echo ""
echo "Generated .env contents (first 15 lines):"
head -15 /var/www/html/.env

# Fix permissions for storage and bootstrap/cache
echo ""
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Clear any cached config
echo "Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Wait for database with timeout
echo ""
echo "Waiting for database (${DB_HOST}:${DB_PORT})..."
for i in {1..60}; do
    if php artisan tinker --execute="DB::connection()->getPdo()" > /dev/null 2>&1; then
        echo "✓ Database is ready!"
        break
    fi
    echo "  Attempt $i/60: Database not ready yet..."
    sleep 1
done

# Run migrations (non-blocking)
echo ""
echo "Running database migrations..."
php artisan migrate --force --quiet 2>/dev/null || echo "Migrations completed or skipped"

# Start Apache
echo ""
echo "====== STARTING APACHE ======"
exec apache2-foreground
