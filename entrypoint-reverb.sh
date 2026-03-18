#!/bin/bash

set -e

export LOG_CHANNEL=stderr

echo "========================================"
echo " Reverb WebSocket Server"
echo "========================================"
echo "  APP_KEY   : ${APP_KEY:+set (masked)}"
echo "  REVERB_APP_KEY: ${REVERB_APP_KEY:-MISSING}"
echo "  REVERB_APP_ID : ${REVERB_APP_ID:-MISSING}"
echo "  HOST:PORT : 0.0.0.0:${REVERB_SERVER_PORT:-8081}"
echo "========================================"

# Clear config cache so env vars are always picked up fresh
php artisan config:clear

echo "Starting Reverb..."
exec php artisan reverb:start --host=0.0.0.0 --port="${REVERB_SERVER_PORT:-8081}"
