#!/bin/sh
set -e

# Cache config/routes/views for production
php artisan optimize

# Create storage symlink if not exists
php artisan storage:link --force 2>/dev/null || true

# Run database migrations
php artisan migrate --force

# Start Apache
exec apache2-foreground
