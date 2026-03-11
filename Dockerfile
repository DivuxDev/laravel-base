# Stage 1: Builder
FROM php:8.4-apache AS builder

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js LTS from NodeSource (apt's npm is too outdated)
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Copy all application files (including artisan) before composer install
COPY . .

# Install PHP dependencies (artisan now exists for post-install scripts)
RUN composer install --no-dev --optimize-autoloader --no-ansi --no-interaction

# Install Node dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Stage 2: Runtime
FROM php:8.4-apache

WORKDIR /var/www/html

# Install runtime dependencies only
RUN apt-get update && apt-get install -y \
    curl \
    wget \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache VirtualHost
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Copy entrypoint script before app files
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy application from builder
COPY --from=builder /var/www/html /var/www/html

# Ensure storage directories exist with correct permissions
RUN mkdir -p storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

# Set environment to production
ENV APP_ENV=production
ENV APP_DEBUG=false

# Expose port 80
EXPOSE 80

# Start entrypoint script
ENTRYPOINT ["/entrypoint.sh"]
