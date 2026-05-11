# Môi trường & Docker

**Status:** Stable · **Cập nhật:** 2026-05-12

## 1. Môi trường
| Env | Mục đích | Ghi chú |
|---|---|---|
| `local` | Dev máy cá nhân | Docker Compose (base + override); cấu hình ở `app/.env` (đã commit — xem dưới); test seller/token sandbox của các sàn |
| `staging` | Kiểm thử trước release | Cấu hình giống prod thu nhỏ; dùng app sandbox của sàn nếu có |
| `production` | Thật | Dữ liệu thật người dùng; cấu hình ở `./.env` cạnh compose (**không** commit); backup + monitoring đầy đủ |

- **Cấu hình `.env`:**
  - `app/.env` — cấu hình **dev/chung**, được **commit vào repo** (repo private, không bao giờ public). Clone về là chạy được luôn (không cần `cp .env.example` / `key:generate`). Chứa `APP_KEY` dev, `DB_CONNECTION=sqlite` (dev nhanh), `INTEGRATIONS_CHANNELS=manual,tiktok`, `TIKTOK_APP_KEY/SECRET` sandbox, … Sửa cấu hình dev = sửa file này rồi commit. `app/.env.example` giữ làm **bản mẫu có chú thích** + làm khung cho `./.env` của prod.
  - `./.env` (gốc repo, cạnh `docker-compose.yml`) — **chỉ dùng cho prod** (`docker-compose.prod.yml` đọc qua `env_file:`). Chứa bí mật prod: `APP_KEY` **riêng** (đừng tái dùng key dev), `APP_URL`, `MAIL_PASSWORD`, `SENTRY_LARAVEL_DSN`, `TIKTOK_*` prod, … `chmod 600`, **KHÔNG commit** (root `.gitignore` chặn `/.env`). Tốt hơn nữa: dùng secret manager.
- Biến chính: `APP_KEY` (mã hoá token), `APP_URL`, DB, Redis, MinIO, Gotenberg, `SENTRY_LARAVEL_DSN`, `TRUSTED_PROXIES`, mail, `INTEGRATIONS_CHANNELS`, và per-sàn: `TIKTOK_APP_KEY/SECRET/SERVICE_ID/SANDBOX`, (sau) `SHOPEE_PARTNER_ID/KEY`, `LAZADA_APP_KEY/SECRET`, per-ĐVVC tokens (hoặc lưu trong `carrier_accounts` theo tenant).

## 2. Docker Compose — base + override + prod
> Hiện thực: 3 file ở gốc repo — `docker-compose.yml` (base, dùng chung), `docker-compose.override.yml` (dev, **tự load** khi `docker compose up`), `docker-compose.prod.yml` (prod, phải nêu tường minh `-f`). Dockerfile/nginx/entrypoint của app ở `app/docker/`; image nginx prod ở `app/docker/nginx/Dockerfile`.
>
> - **Dev:** `cp app/.env.example app/.env` → `docker compose up -d --build`. Override bật: bind-mount `./app` (sửa là thấy ngay), publish port host (5432/6379/9000/9001/8000), service `vite` (HMR, ghi `public/hot`), service `mailpit` (`:8025`), `RUN_MIGRATIONS=true`. App đọc cấu hình từ `app/.env` (bind-mounted) — gồm `INTEGRATIONS_CHANNELS`, `TIKTOK_*`, …
> - **Prod:** `docker network create proxy` (NPM/Caddy đứng ngoài) → tạo `./.env` (cạnh file compose, `chmod 600`, **không commit** — chứa `APP_KEY`, `APP_URL`, `INTEGRATIONS_CHANNELS`, `TIKTOK_*`, `MAIL_*`, `SENTRY_LARAVEL_DSN`, …) → `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build`. App/worker/scheduler nhận cấu hình qua `env_file: ./.env` (+ ép `APP_ENV=production`, `APP_DEBUG=false`, `TRUSTED_PROXIES=*`); `RUN_MIGRATIONS=false` ⇒ **migrate là bước deploy có kiểm soát**: `docker compose ... exec app php artisan migrate --force`. `web` build từ `app/docker/nginx/Dockerfile` (đa stage: build asset Vite → nginx + `public/`), join network `proxy`, healthcheck `/api/v1/health`. Tất cả service `restart: unless-stopped` + log json-file xoay vòng 10m×5.

| Service | Base | Dev (override) | Prod (prod.yml) |
|---|---|---|---|
| `app` | PHP-FPM (image `app/docker/Dockerfile`), `expose 9000` | + bind-mount `./app`, vendor anon volume, `APP_ENV=local`, `RUN_MIGRATIONS=true`, mail→mailpit | + `env_file ./.env`, `APP_ENV=production`, `RUN_MIGRATIONS=false`, `TRUSTED_PROXIES=*`, restart, logging |
| `web` | tên container; image/build khai ở dev/prod | nginx:1.27-alpine + bind-mount `app/docker/nginx.conf` & `app/public`, port `8000:80` | build `app/docker/nginx/Dockerfile`, network `proxy`, healthcheck `/api/v1/health` |
| `worker` | `php artisan horizon`, healthcheck `horizon:status` | + bind-mount, `APP_ENV=local`, mail→mailpit | + `env_file ./.env`, `APP_ENV=production` |
| `scheduler` | `php artisan schedule:work` (1 instance) | + bind-mount | + `env_file ./.env`, `deploy.replicas=1` |
| `vite` | — | `node:20-alpine`: `npm ci && npm run dev -- --host 0.0.0.0`, port `5173`, bind-mount `./app`, node_modules anon | — (asset baked vào image `web`) |
| `mailpit` | — | `axllent/mailpit` (`:1025` SMTP, `:8025` UI) | — (dùng SMTP thật) |
| `postgres` | PG 15, volume `pgdata`, healthcheck `pg_isready` | + port `5432:5432` | + restart, logging |
| `redis` | Redis 7 (appendonly), volume `redisdata`, healthcheck `redis-cli ping` | + port `6379:6379` | + restart, logging |
| `minio` + `minio-init` | MinIO + job một-lần tạo bucket | + port `9000`/`9001` | + restart, logging |
| `gotenberg` | `gotenberg/gotenberg:8` (HTML→PDF) | — | + restart, logging |
| `meilisearch` / `reverb` | (Phase sau) | | |

- Healthcheck cho `postgres`/`redis`/`minio`/`worker` (+ `web` ở prod); `app`/`worker`/`scheduler` chờ `postgres`/`redis` healthy mới khởi động. Mọi service log JSON ra stdout (`LOG_CHANNEL=stack`, `LOG_STACK=json`).
- Volume: `pgdata`, `redisdata`, `miniodata` (dữ liệu) + (dev) volume ẩn danh cho `vendor`/`node_modules` (giữ deps đã baked trong image, không bị bind-mount `./app` che mất). Sau khi đổi `composer.lock`: `docker compose run --rm app composer install`. Backup: `scripts/backup.sh`/`scripts/restore.sh` (xem `observability-and-backup.md`).
- **Lưu ý**: gọi `withMiddleware()` trong `bootstrap/app.php` **đúng 1 lần** (mỗi lần ghi đè global middleware/group/alias của kernel, không merge) — `trustProxies` (đọc `TRUSTED_PROXIES`) nằm cùng closure với `AssignRequestId`/`statefulApi`/alias `tenant`.
- Trong dev, code `./app` được bind-mount vào các container PHP (sửa là thấy ngay); muốn build asset production tại chỗ: `docker compose exec vite npm run build`.

## 3. Triển khai (giai đoạn đầu)
- Build image (CI) → push registry → trên VPS `docker compose pull && docker compose up -d` (hoặc deploy script qua SSH). Migration chạy như một bước có kiểm soát (`php artisan migrate --force`) — **không** tự chạy lúc container start ở prod (tránh race khi nhiều replica).
- Worker tách máy/scale riêng khi tải tăng. App đứng sau Nginx/Caddy (TLS) hoặc Cloudflare.
- Lên managed (RDS/ElastiCache/S3/ECS) khi cần — chỉ đổi biến môi trường + IaC, không đổi code (xem `tech-stack.md`).

## 4. Cấu hình bật/tắt tính năng tích hợp
- `config/integrations.php`: danh sách sàn bật, ĐVVC bật, ĐVVC mặc định, throttle per sàn, bật/tắt đồng bộ ngược tồn... ⇒ đổi vận hành không cần đổi code.
- Feature flags cấp tenant (theo gói) đọc từ `Subscription` / `plans.features`.
