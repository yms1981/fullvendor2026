#!/bin/sh
set -e
cd /var/www/html
if [ -f .env ]; then
  php artisan package:discover --ansi 2>/dev/null || true
  php artisan config:cache 2>/dev/null || true
  php artisan route:cache 2>/dev/null || true
  php artisan view:cache 2>/dev/null || true
fi
exec apache2-foreground
