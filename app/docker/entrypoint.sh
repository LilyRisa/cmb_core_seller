#!/usr/bin/env bash
set -euo pipefail

# Shared entrypoint for the app / worker / scheduler containers.
#
#   - generates an APP_KEY only if one isn't provided (dev convenience; prod sets APP_KEY explicitly)
#   - on the php-fpm container only (RUN_MIGRATIONS=true): waits for the DB, migrates, storage:link.
#     Prod keeps RUN_MIGRATIONS=false — migrations are a separate, controlled deploy step.
#   - in non-local envs: caches config / routes / views for speed (resilient: a failed cache
#     falls back to the cleared state so a bad config/route can't crash-loop the container).
#
# Then execs whatever command the service passed (php-fpm / horizon / schedule:work).

cd /var/www/html

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "[entrypoint] APP_KEY not set — generating one (dev only; set APP_KEY explicitly in prod)."
  php artisan key:generate --force || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[entrypoint] Waiting for the database..."
  until php artisan db:show >/dev/null 2>&1; do sleep 1; done
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force
  php artisan storage:link >/dev/null 2>&1 || true
fi

if [ "${APP_ENV:-local}" != "local" ]; then
  php artisan config:cache  || { echo "[entrypoint] config:cache failed — running uncached"; php artisan config:clear || true; }
  php artisan route:cache   || { echo "[entrypoint] route:cache failed — running uncached"; php artisan route:clear  || true; }
  php artisan view:cache    || { echo "[entrypoint] view:cache failed — continuing";        php artisan view:clear   || true; }
else
  php artisan config:clear >/dev/null 2>&1 || true
fi

exec "$@"
