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
| Service | Vai trò |
|---|---|
| `app` | PHP-FPM + Nginx (hoặc Laravel Octane sau) — phục vụ web/API/webhook |
| `worker` | `php artisan horizon` — chạy queue (container riêng, scale được số replica) |
| `scheduler` | `php artisan schedule:work` (hoặc cron gọi `schedule:run`) |
| `postgres` | PostgreSQL 15 (volume dữ liệu, script backup) |
| `redis` | Redis 7 (cache/queue/lock) |
| `minio` | Object storage S3-compatible (bucket `omnisell`) |
| `gotenberg` | Render HTML→PDF cho phiếu in |
| `mailhog` | (local) bắt email để xem |
| `meilisearch` | (bật khi cần, Phase sau) tìm kiếm |
| `reverb` | (Phase sau) WebSocket realtime |

- `docker compose up` ⇒ chạy được toàn bộ; `docker compose exec app php artisan migrate --seed` ⇒ có dữ liệu mẫu. README ở root repo hướng dẫn từng bước.
- Healthcheck cho từng service; `app` & `worker` chờ `postgres`/`redis` ready.
- Volume: `pgdata`, `miniodata`, `redisdata`. Backup script dump Postgres + sync MinIO ra nơi an toàn (xem `observability-and-backup.md`).

## 3. Triển khai (giai đoạn đầu)
- Build image (CI) → push registry → trên VPS `docker compose pull && docker compose up -d` (hoặc deploy script qua SSH). Migration chạy như một bước có kiểm soát (`php artisan migrate --force`) — **không** tự chạy lúc container start ở prod (tránh race khi nhiều replica).
- Worker tách máy/scale riêng khi tải tăng. App đứng sau Nginx/Caddy (TLS) hoặc Cloudflare.
- Lên managed (RDS/ElastiCache/S3/ECS) khi cần — chỉ đổi biến môi trường + IaC, không đổi code (xem `tech-stack.md`).

## 4. Cấu hình bật/tắt tính năng tích hợp
- `config/integrations.php`: danh sách sàn bật, ĐVVC bật, ĐVVC mặc định, throttle per sàn, bật/tắt đồng bộ ngược tồn... ⇒ đổi vận hành không cần đổi code.
- Feature flags cấp tenant (theo gói) đọc từ `Subscription` / `plans.features`.
