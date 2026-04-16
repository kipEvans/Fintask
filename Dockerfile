# Use Apache PHP image for Render-friendly deployment
FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip libpng-dev libjpeg-dev libzip-dev libicu-dev libonig-dev libpq-dev \
    nodejs npm \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules for Laravel
RUN a2enmod rewrite headers

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl

# Install Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Set workdir
WORKDIR /var/www/html

# Copy source
COPY . /var/www/html

# Ensure storage and cache directories exist
RUN mkdir -p storage bootstrap/cache

# Create SQLite database file if it doesn't exist
RUN touch /var/www/html/database.sqlite && chmod 666 /var/www/html/database.sqlite

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Ensure public asset directories exist
RUN mkdir -p /var/www/html/public/css /var/www/html/public/js

# Copy source assets directly to public (bypass npm build complexity)
COPY resources/css/fintask.css /var/www/html/public/css/fintask.css
COPY resources/js/fintask.js /var/www/html/public/js/fintask.js

# Create necessary directories with proper structure
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/css /var/www/html/public/js
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/css /var/www/html/public/js

# Set Apache document root to public with explicit rewrite and override
RUN cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted

        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L,QSA]
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose HTTP
EXPOSE 80

# Start with entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
