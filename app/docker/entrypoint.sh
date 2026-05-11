#!/usr/bin/env bash
set -euo pipefail

# Shared entrypoint for the app / worker / scheduler containers.
#
#   - waits briefly for an APP_KEY (compose mounts .env)
#   - on the php-fpm container only, runs migrations + storage:link + caches config
#     (controlled by RUN_MIGRATIONS — disable it in prod where migrations are a
#     separate, controlled deploy step; see docs/07-infra/environments-and-docker.md §3)
#
# Then execs whatever command the service passed (php-fpm / horizon / schedule:work).

cd /var/www/html

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "[entrypoint] APP_KEY not set — generating one (dev only; set it explicitly in prod)."
  php artisan key:generate --force || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[entrypoint] Waiting for the database..."
  until php artisan db:show >/dev/null 2>&1; do sleep 1; done

  echo "[entrypoint] Running migrations..."
  php artisan migrate --force

  php artisan storage:link >/dev/null 2>&1 || true
fi

# In non-local environments, cache config/routes/views for speed.
if [ "${APP_ENV:-local}" != "local" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
else
  php artisan config:clear >/dev/null 2>&1 || true
fi

exec "$@"
