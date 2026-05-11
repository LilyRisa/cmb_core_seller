# Môi trường & Docker

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Môi trường
| Env | Mục đích | Ghi chú |
|---|---|---|
| `local` | Dev máy cá nhân | Docker Compose; test seller/token sandbox của các sàn |
| `staging` | Kiểm thử trước release | Cấu hình giống prod thu nhỏ; dùng app sandbox của sàn nếu có |
| `production` | Thật | Dữ liệu thật người dùng; backup + monitoring đầy đủ |

- Cấu hình qua biến môi trường (`.env`); **không commit `.env`**, có `.env.example` đầy đủ key. Bí mật prod ở secret manager / file ngoài repo.
- `APP_KEY` (mã hoá token), `APP_URL`, DB, Redis, MinIO, Gotenberg, Sentry DSN, mail, và per-sàn: `TIKTOK_APP_KEY/SECRET`, (sau) `SHOPEE_PARTNER_ID/KEY`, `LAZADA_APP_KEY/SECRET`, per-ĐVVC tokens (hoặc lưu trong `carrier_accounts` theo tenant).

## 2. Dịch vụ trong Docker Compose (local & cơ sở cho staging/prod)
> Hiện thực: `docker-compose.yml` ở gốc repo; Dockerfile + nginx + entrypoint ở `app/docker/`.

| Service | Vai trò |
|---|---|
| `app` | PHP-FPM (image `app/docker/Dockerfile` — đa stage: build asset Vite + composer deps + runtime). `RUN_MIGRATIONS=true` ⇒ container này tự `migrate` + warm cache lúc boot. |
| `web` | Nginx 1.27, proxy `/` & `*.php` → `app:9000`. Tách container thay vì gộp "PHP-FPM + Nginx" — phục vụ web/API/webhook/SPA catch-all. |
| `worker` | `php artisan horizon` — chạy queue (container riêng, scale replica độc lập); healthcheck `horizon:status`. |
| `scheduler` | `php artisan schedule:work` (một instance). |
| `vite` | (local) `npm run dev` — Vite HMR cho React, ghi `public/hot`. Bỏ khi build asset baked vào image (staging/prod). |
| `postgres` | PostgreSQL 15 (volume `pgdata`, healthcheck `pg_isready`). |
| `redis` | Redis 7 (cache/queue/lock; volume `redisdata`, healthcheck `redis-cli ping`). |
| `minio` + `minio-init` | Object storage S3-compatible (volume `miniodata`); `minio-init` là job một-lần tạo bucket `omnisell`. |
| `gotenberg` | Render HTML→PDF cho phiếu in (image `gotenberg/gotenberg:8`). |
| `mailpit` | (local) bắt email để xem (`:8025`). Thay cho MailHog (đã ngừng phát triển). |
| `meilisearch` | (bật khi cần, Phase sau) tìm kiếm. |
| `reverb` | (Phase sau) WebSocket realtime. |

- `cp app/.env.example app/.env` rồi `docker compose up -d --build` ⇒ chạy được toàn bộ; migrate chạy tự động qua `RUN_MIGRATIONS`, hoặc `docker compose exec app php artisan migrate --seed` để có dữ liệu mẫu. README ở gốc repo hướng dẫn từng bước.
- Healthcheck cho `postgres`/`redis`/`minio`/`worker`; `app`/`worker`/`scheduler` chờ `postgres`/`redis` healthy mới khởi động.
- Volume: `pgdata`, `redisdata`, `miniodata` (dữ liệu) + volume ẩn danh cho `vendor`/`node_modules` (giữ lại deps đã baked trong image, không bị bind-mount của `./app` che mất). Sau khi đổi `composer.lock`: `docker compose run --rm app composer install`. Backup script dump Postgres + sync MinIO ra nơi an toàn (xem `observability-and-backup.md`).
- Trong dev, code `./app` được bind-mount vào các container PHP (sửa là thấy ngay); muốn build asset production tại chỗ: `docker compose exec vite npm run build`.

## 3. Triển khai (giai đoạn đầu)
- Build image (CI) → push registry → trên VPS `docker compose pull && docker compose up -d` (hoặc deploy script qua SSH). Migration chạy như một bước có kiểm soát (`php artisan migrate --force`) — **không** tự chạy lúc container start ở prod (tránh race khi nhiều replica).
- Worker tách máy/scale riêng khi tải tăng. App đứng sau Nginx/Caddy (TLS) hoặc Cloudflare.
- Lên managed (RDS/ElastiCache/S3/ECS) khi cần — chỉ đổi biến môi trường + IaC, không đổi code (xem `tech-stack.md`).

## 4. Cấu hình bật/tắt tính năng tích hợp
- `config/integrations.php`: danh sách sàn bật, ĐVVC bật, ĐVVC mặc định, throttle per sàn, bật/tắt đồng bộ ngược tồn... ⇒ đổi vận hành không cần đổi code.
- Feature flags cấp tenant (theo gói) đọc từ `Subscription` / `plans.features`.
