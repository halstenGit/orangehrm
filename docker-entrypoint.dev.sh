#!/bin/bash
set -e

# Install composer dependencies if vendor folder is missing
if [ ! -d "/var/www/html/src/vendor" ]; then
    echo ">>> Installing composer dependencies..."
    composer install -d /var/www/html/src --no-interaction --prefer-dist
fi

# Set permissions
chown -R www-data:www-data /var/www/html/lib/confs \
    /var/www/html/src/cache \
    /var/www/html/src/log \
    /var/www/html/src/config 2>/dev/null || true

chmod -R 775 /var/www/html/lib/confs \
    /var/www/html/src/cache \
    /var/www/html/src/log \
    /var/www/html/src/config 2>/dev/null || true

echo ">>> OrangeHRM dev environment ready"
echo ">>> Access: http://localhost:${APP_PORT:-8080}"
echo ">>> If first run, the installer will launch automatically"

exec "$@"
