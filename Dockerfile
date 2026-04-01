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

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

# Build assets if package.json exists
RUN if [ -f package.json ]; then \
      if [ -f package-lock.json ]; then npm ci; else npm install; fi && \
      if npm run prod 2>/dev/null; then :; else npm run build; fi; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Set Apache document root to public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's!<Directory /var/www/>!<Directory /var/www/html/public>!g' /etc/apache2/apache2.conf || true

# Expose HTTP
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
