#!/bin/sh
set -e

# When backend_public volume is mounted at /var/www/public, it may be empty. Populate from image copy.
if [ -d /var/www/public.from-image ] && [ -z "$(ls -A /var/www/public 2>/dev/null)" ]; then
  cp -a /var/www/public.from-image/. /var/www/public/
  chown -R www-data:www-data /var/www/public
fi

# Ensure permissions for storage and cache which might be mounted as root in production volumes
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Only clear caches if volume-persisted config cache exists (stale from previous build)
if [ -f /var/www/bootstrap/cache/config.php ]; then
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  # Re-cache with current environment
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
