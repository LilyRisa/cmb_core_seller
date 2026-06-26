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

# --- PHP-FPM pool sizing (env-tunable) -----------------------------------------
# Mặc định php:fpm-alpine chỉ cho pm.max_children=5 (5 request PHP đồng thời!) — quá thấp.
# Mỗi child ~50-70MB ⇒ đặt PHP_FPM_MAX_CHILDREN ≈ RAM_dành_cho_PHP(GB) / 0.06. Chỉ container
# php-fpm đọc file này; worker/scheduler bỏ qua (vô hại). zz- nạp sau www.conf nên ghi đè [www].
: "${PHP_FPM_MAX_CHILDREN:=40}"
: "${PHP_FPM_MAX_REQUESTS:=500}"
# Giết request chạy quá lâu (mặc định 115s — DƯỚI trần nginx fastcgi_read_timeout/NPM proxy_read_timeout 120s)
# ⇒ KHÔNG để PHP chạy orphan sau khi proxy đã trả 504 (đơn bị flip status ngầm dù client báo lỗi). Mọi request
# >120s vốn đã bị 504 nên đây chỉ dọn cái đã hỏng. SPEC 2026-06-26.
: "${PHP_FPM_REQUEST_TIMEOUT:=115s}"
_fpm_start=$(( PHP_FPM_MAX_CHILDREN / 4 )); [ "$_fpm_start" -ge 2 ] || _fpm_start=2
_fpm_min=$(( PHP_FPM_MAX_CHILDREN / 8 )); [ "$_fpm_min" -ge 1 ] || _fpm_min=1
_fpm_max=$(( PHP_FPM_MAX_CHILDREN / 2 )); [ "$_fpm_max" -ge 3 ] || _fpm_max=3
cat > /usr/local/etc/php-fpm.d/zz-pool.conf <<EOF
[www]
pm = dynamic
pm.max_children = ${PHP_FPM_MAX_CHILDREN}
pm.start_servers = ${_fpm_start}
pm.min_spare_servers = ${_fpm_min}
pm.max_spare_servers = ${_fpm_max}
pm.max_requests = ${PHP_FPM_MAX_REQUESTS}
pm.process_idle_timeout = 10s
request_terminate_timeout = ${PHP_FPM_REQUEST_TIMEOUT}
EOF

# --- Prod OPcache: code baked, không đổi lúc chạy ⇒ tắt validate (nhanh hơn). Dev giữ =1. ---
if [ "${APP_ENV:-local}" != "local" ]; then
  printf 'opcache.validate_timestamps=0\nopcache.revalidate_freq=0\n' > /usr/local/etc/php/conf.d/zz-prod-opcache.ini
fi

exec "$@"
