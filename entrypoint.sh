#!/bin/bash

# Exit on any error
set -e

# ─── Environment variable diagnostics ────────────────────────────────────────
# Shows which required vars are SET vs MISSING. Sensitive values are masked.
check_var() {
    local name="$1"
    local sensitive="${2:-false}"
    local value="${!name}"
    if [ -z "$value" ]; then
        echo "  [MISSING] $name"
    elif [ "$sensitive" = "true" ]; then
        echo "  [SET]     $name = ***"
    else
        echo "  [SET]     $name = $value"
    fi
}

echo "========================================"
echo " ENV DIAGNOSTICS"
echo "========================================"
check_var APP_NAME
check_var APP_ENV
check_var APP_DEBUG
check_var APP_KEY        true
check_var APP_URL
check_var FRONTEND_URL
echo "--- Database ---"
check_var DB_CONNECTION
check_var DB_HOST
check_var DB_PORT
check_var DB_DATABASE
check_var DB_USERNAME
check_var DB_PASSWORD    true
echo "--- Cache / Session ---"
check_var CACHE_DRIVER
check_var SESSION_DRIVER
check_var REDIS_HOST
check_var REDIS_PORT
check_var REDIS_PASSWORD true
echo "--- Mail ---"
check_var MAIL_MAILER
check_var MAIL_HOST
check_var MAIL_PORT
check_var MAIL_USERNAME
check_var MAIL_PASSWORD  true
echo "--- Logging ---"
check_var LOG_CHANNEL
check_var LOG_LEVEL
echo "========================================"

# Force logs to stderr so Coolify/Docker always shows them regardless of .env
export LOG_CHANNEL=stderr
export LOG_LEVEL=debug

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

# Regenerate package manifest from the actual installed vendor directory.
# This MUST run before anything else that boots the framework, because the
# committed bootstrap/cache/services.php may list dev-only providers
# (Pail, Sail, Collision…) that are absent when built with --no-dev.
echo "Discovering packages..."
php artisan package:discover --ansi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed only if the database appears empty (avoids unique constraint failures on restart)
echo "Seeding database (if needed)..."
php artisan db:seed --force || echo "Seeding skipped or failed (non-fatal on restart)"

# Link storage (creates public/storage -> storage/app/public symlink)
php artisan storage:link --force 2>/dev/null || true

# Fix permissions on volume-mounted directories.
# Docker bind mounts override the Dockerfile's chown, so we re-apply here.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recreate optimized caches for production
echo "Creating optimized caches..."
php artisan config:cache
php artisan route:cache || { echo "ERROR: route:cache failed — check routes for Closures"; exit 1; }
php artisan view:cache
php artisan optimize

echo "Setup completed successfully!"

# Start Apache
exec apache2-foreground
