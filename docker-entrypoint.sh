#!/bin/bash
set -e

# Fix MPM conflict — PHP needs prefork, disable others
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway provides PORT env var — Apache must listen on it
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf
    echo ">>> Apache listening on port ${PORT}"
fi

# Ensure writable dirs exist with correct permissions
mkdir -p lib/confs lib/confs/cryptokeys src/cache src/log src/config
chown -R www-data:www-data lib/confs src/cache src/log src/config
chmod -R 775 lib/confs src/cache src/log src/config

# If Conf.php doesn't exist, the web installer will launch automatically
if [ ! -f "lib/confs/Conf.php" ]; then
    echo ">>> First run detected — web installer will start"
    echo ">>> Access the app URL to begin setup"
fi

exec "$@"
