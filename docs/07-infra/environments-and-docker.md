# Môi trường & Docker

**Status:** Stable · **Cập nhật:** 2026-05-12

## 1. Môi trường
| Env | Mục đích | Ghi chú |
|---|---|---|
| `local` | Dev máy cá nhân | Docker Compose (base + override); cấu hình ở `app/.env` (đã commit — xem dưới); test seller/token sandbox của các sàn |
| `staging` | Kiểm thử trước release | Cấu hình giống prod thu nhỏ; dùng app sandbox của sàn nếu có |
| `production` | Thật | Dữ liệu thật người dùng; cấu hình ở `./.env` cạnh compose (**không** commit); backup + monitoring đầy đủ |

- **Cấu hình `.env`:**
  - `app/.env` — cấu hình **dev/chung**, được **commit vào repo** (repo private, không bao giờ public). Clone về là chạy được luôn (không cần `cp` / `key:generate`). Chứa `APP_KEY` dev, `DB_CONNECTION=sqlite` (dev nhanh ngoài Docker), `INTEGRATIONS_CHANNELS=manual,tiktok`, `TIKTOK_APP_KEY/SECRET` sandbox, … `app/.env.example` giữ làm bản mẫu có chú thích.
  - `./.env` (gốc repo, cạnh các file compose) — **biến cho prod**. `docker-compose.prod.yml` dùng `${VAR}` substitution; `docker compose` tự đọc `./.env` ở thư mục này để thay. Chứa bí mật prod: `APP_KEY` **riêng** (đừng tái dùng key dev), `DB_PASSWORD`, `AWS_ACCESS_KEY_ID/SECRET`, `MAIL_*`, `SENTRY_LARAVEL_DSN`, `TIKTOK_*` prod, … `chmod 600`, **KHÔNG commit** (root `.gitignore` chặn `/.env`). Tạo từ `.env.example` ở gốc repo. **Portainer:** không cần file `./.env` — nhập các biến này vào ô "Environment variables" của stack. Tốt hơn nữa: secret manager.
- Biến chính: `APP_KEY` (mã hoá token), `APP_URL`, DB, Redis, MinIO, Gotenberg, `SENTRY_LARAVEL_DSN`, `TRUSTED_PROXIES`, mail, `INTEGRATIONS_CHANNELS`, và per-sàn: `TIKTOK_APP_KEY/SECRET/SERVICE_ID/SANDBOX`, (sau) `SHOPEE_PARTNER_ID/KEY`, `LAZADA_APP_KEY/SECRET`, per-ĐVVC tokens (hoặc lưu trong `carrier_accounts` theo tenant).

## 2. Docker Compose — dev (base + override) vs prod (file độc lập)
> 3 file ở gốc repo:
> - `docker-compose.yml` — **base cho DEV**; `docker-compose.override.yml` — extras cho DEV (**tự merge** khi `docker compose up`). Hai file này luôn đi cùng nhau cho dev.
> - `docker-compose.prod.yml` — **stack PROD, một file độc lập** (không kèm base/override). Tự chứa mọi service + image + env + volume + network `proxy`; dùng `${VAR}` substitution (không `env_file:`) ⇒ tương thích Portainer (git stack: trỏ "Compose path" tới file này, điền env vars ở UI). **Không** trộn với base.
>
> Dockerfile/nginx/entrypoint của app ở `app/docker/`; image nginx prod ở `app/docker/nginx/Dockerfile` (đa stage tự build asset Vite — không phụ thuộc image app).
>
> - **Dev:** `docker compose up -d --build` (base + override). Override bật: bind-mount `./app` (sửa là thấy ngay), publish port host (8000/5432/6379/9000/9001), service `vite` (HMR, ghi `public/hot`), service `mailpit` (`:8025`), `RUN_MIGRATIONS=true`. App đọc cấu hình từ `app/.env` (bind-mounted, đã commit) + biến `environment:` của container (vd `DB_CONNECTION=pgsql` ghi đè `sqlite` trong `app/.env` cho dev-Docker).
> - **Prod:** `docker network create proxy` (reverse proxy ngoài share network này) → điền env (`cp .env.example .env` cho CLI, hoặc nhập ở Portainer UI) → `docker compose -f docker-compose.prod.yml up -d --build`. `APP_ENV=production`, `APP_DEBUG=false`, `TRUSTED_PROXIES=*`, log JSON, mọi service `restart: unless-stopped` + log json-file xoay vòng 10m×5, có data volumes (`pgdata`/`redisdata`/`miniodata`) + `minio-init` tạo bucket. `RUN_MIGRATIONS=false` ⇒ **migrate là bước deploy có kiểm soát**: `docker compose -f docker-compose.prod.yml exec app php artisan migrate --force`. `web` build từ `app/docker/nginx/Dockerfile`, join `proxy`, healthcheck `/api/v1/health`. **Triển khai qua Portainer:** xem runbook chi tiết [`portainer-deploy.md`](portainer-deploy.md) (checklist deploy, chạy migrate trong container, kiểm `cmb-worker`/Horizon, debug webhook).

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

## 3. Triển khai prod — domain `app.cmbcore.com` (Portainer / CLI)
- **Reverse proxy ngoài cluster** (Nginx Proxy Manager / Caddy / Traefik) chạy trên network Docker `proxy` (tạo trước: `docker network create proxy`), cấu hình **host `app.cmbcore.com` → upstream `http://cmb-web:80`** + cấp TLS (Let's Encrypt). Service `web` (nginx trong cluster) join cả `default` lẫn `proxy`; chỉ proxy `*.php` → `app:9000` và phục vụ `public/` (asset Vite đã build trong image). `nginx.conf` để `server_name _` (host đã được proxy ngoài lọc); `bootstrap/app.php` trust `X-Forwarded-*` (`TRUSTED_PROXIES=*`) nên `https://`, URL sinh ra, cookie Sanctum đều đúng.
- **Một file compose độc lập** = `docker-compose.prod.yml` (KHÔNG kèm `docker-compose.yml`). Tự chứa: `app`(php-fpm)/`worker`(horizon)/`scheduler` (build `app/docker/Dockerfile`, image `cmbcoreseller-app:${APP_TAG}`), `web` (build `app/docker/nginx/Dockerfile`), `postgres`/`redis`/`minio`(+`minio-init`)/`gotenberg` (image + volumes + healthcheck). Cấu hình qua `${VAR}` substitution — `docker compose` tự đọc `./.env` cạnh file (CLI), hoặc Portainer nhập ở UI; **không** dùng `env_file:`. `APP_URL` mặc định `https://app.cmbcore.com`, `SANCTUM_STATEFUL_DOMAINS`/`SESSION_DOMAIN` mặc định `app.cmbcore.com`, `SESSION_SECURE_COOKIE=true`. **Bắt buộc điền** (rỗng → lỗi rõ ràng): `APP_KEY` (key **mới** — `... run --rm app php artisan key:generate --show`), `DB_PASSWORD`, `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY` (cũng là root user của MinIO); nên điền `MAIL_*`, `SENTRY_LARAVEL_DSN`, `TIKTOK_APP_KEY/SECRET/SERVICE_ID` (app **prod**), `APP_TAG`. Mẫu: `.env.example` ở gốc repo.
- **Deploy bằng CLI:** `git pull` → `cp .env.example .env && chmod 600 .env` (lần đầu, rồi điền) → `docker compose -f docker-compose.prod.yml up -d --build` → **migration là bước có kiểm soát** (RUN_MIGRATIONS=false nên container không tự migrate — tránh race khi nhiều replica): `docker compose -f docker-compose.prod.yml exec app php artisan migrate --force` → kiểm `https://app.cmbcore.com/api/v1/health`.
- **Deploy bằng Portainer (Git stack):** "Add stack" → Repository = repo này, "Compose path" = `docker-compose.prod.yml` (**không** `docker-compose.yml`!), điền env vars (`APP_KEY`, `DB_PASSWORD`, `AWS_*`, `MAIL_*`, `SENTRY_LARAVEL_DSN`, `TIKTOK_*`, `APP_TAG`) ở mục "Environment variables", Deploy. Sau lần deploy đầu chạy migrate (trong "Console" của container `cmb-app`: `php artisan migrate --force`, hoặc qua webhook/redeploy). Auto-update khi push (bật "GitOps" / polling nếu muốn).
- *(Workflow `.github/workflows/deploy-staging.yml` có khung SSH + `docker compose` cho staging; nhân bản cho prod khi cần — secrets `STAGING_SSH_*` / `STAGING_HEALTH_URL`. Hoặc CI build & push image lên registry rồi đổi `image:` trong prod.yml trỏ registry, bỏ `build:` — sẽ deploy nhanh hơn, không build tại chỗ.)*
- Worker tách máy/scale riêng khi tải tăng (queue Horizon trên Redis). Lên managed (RDS/ElastiCache/S3/ECS) khi cần — chỉ đổi biến môi trường + IaC, không đổi code (xem `tech-stack.md`).

## 4. Cấu hình bật/tắt tính năng tích hợp
- `config/integrations.php`: danh sách sàn bật, ĐVVC bật, ĐVVC mặc định, throttle per sàn, bật/tắt đồng bộ ngược tồn... ⇒ đổi vận hành không cần đổi code.
- Feature flags cấp tenant (theo gói) đọc từ `Subscription` / `plans.features`.
