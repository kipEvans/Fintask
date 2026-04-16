#!/bin/bash
set -e

echo "=== FinTask Container Starting ==="

# Generate .env from environment variables
echo "Generating .env file..."
cat > /var/www/html/.env <<EOF
APP_NAME=${APP_NAME:-FinTask}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-}

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-stderr}
LOG_LEVEL=${LOG_LEVEL:-info}

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-fintask}
DB_USERNAME=${DB_USERNAME:-postgres}
DB_PASSWORD=${DB_PASSWORD:-}

SESSION_DRIVER=${SESSION_DRIVER:-cookie}
CACHE_STORE=${CACHE_STORE:-array}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

MAIL_MAILER=log
FILESYSTEM_DISK=local
EOF

chmod 644 /var/www/html/.env

# Print configuration for debugging
echo ""
echo "=== Configuration ==="
echo "Database (PostgreSQL):"
echo "  Host: ${DB_HOST:-127.0.0.1}"
echo "  Port: ${DB_PORT:-5432}"
echo "  Database: ${DB_DATABASE:-fintask}"
echo "  User: ${DB_USERNAME:-postgres}"
echo "Logging: ${LOG_STACK:-stderr}"
echo "Cache: ${CACHE_STORE:-array}"
echo "====================="
echo ""

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Clear Laravel caches
php artisan config:clear --quiet 2>/dev/null || true
php artisan cache:clear --quiet 2>/dev/null || true

# Run migrations (non-blocking)
echo "Running migrations..."
php artisan migrate --force --quiet 2>/dev/null && echo "✓ Migrations completed" || echo "⚠ Migrations skipped (database may be unavailable)"

echo ""
echo "=== Starting Apache ==="
exec apache2-foreground
