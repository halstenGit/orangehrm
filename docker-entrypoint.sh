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

# Auto-apply pending migrations (idempotent — no-op if at latest version)
if [ -f "lib/confs/Conf.php" ]; then
    echo ">>> Running auto-upgrade (apply pending migrations)..."
    php bin/auto-upgrade.php || {
        echo ">>> auto-upgrade failed — refusing to start app to avoid serving with stale schema" >&2
        exit 1
    }
fi

# Seed pt_BR translations once (after install is complete)
if [ -f "lib/confs/Conf.php" ] && [ ! -f "lib/confs/.pt_br_seeded" ]; then
    echo ">>> Seeding pt_BR translations..."
    php bin/seed-pt-br.php && touch lib/confs/.pt_br_seeded
fi

# Pipe Apache + PHP error logs to container stdout/stderr so Railway captures them.
ln -sf /dev/stderr /var/log/apache2/error.log
ln -sf /dev/stdout /var/log/apache2/access.log
ln -sf /dev/stderr /var/log/apache2/other_vhosts_access.log 2>/dev/null || true

# Pipe OHRM application log too (PHP fatals from within the framework).
mkdir -p src/log
touch src/log/orangehrm.log
chown www-data:www-data src/log/orangehrm.log
tail -F src/log/orangehrm.log >> /dev/stderr 2>/dev/null &

exec "$@"
