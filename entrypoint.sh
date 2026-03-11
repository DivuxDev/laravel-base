#!/bin/bash

# Exit on any error
set -e

# Auto-generate APP_KEY if not provided (Coolify may not set it on first boot)
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY not set — generating a new one..."
    # key:generate writes to .env; create the file first if it doesn't exist
    [ -f /var/www/html/.env ] || touch /var/www/html/.env
    php artisan key:generate --force
    # Export so config:cache and apache child processes both see the new key
    APP_KEY=$(grep "^APP_KEY=" /var/www/html/.env | cut -d= -f2-)
    export APP_KEY
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Link storage (creates public/storage -> storage/app/public symlink)
php artisan storage:link --force 2>/dev/null || true

# Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recreate optimized caches for production
echo "Creating optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "Setup completed successfully!"

# Start Apache
exec apache2-foreground
