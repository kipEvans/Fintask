# Use official PHP image with FPM
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk --update --no-cache add \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    nodejs \
    npm \
    npm \
    shadow \
    openssh

# PHP extensions
RUN docker-php-ext-configure zip
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl

# Composer install
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# App directory
WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install node dependencies and build assets
RUN npm ci && npm run prod

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 9000

# Entrypoint
env PATH="/var/www/html/vendor/bin:$PATH"

CMD ["php-fpm"]
