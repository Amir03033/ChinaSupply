#!/usr/bin/env bash
# Deployment script for the Plesk VPS (run by Plesk Git "additional deploy actions"
# or manually from the app directory). Never run against production without approval.
set -euo pipefail

php artisan down --retry=30 || true

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci && npm run build

php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force   # idempotent permission sync
php artisan storage:link || true
php artisan optimize
php artisan filament:optimize
php artisan queue:restart

php artisan up
