#!/bin/bash

# Exit on any error
set -e

# Run migrations
echo "Running migrations..."
php artisan migrate --force

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
