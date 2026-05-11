# OmniSell / CMBcoreSeller

SaaS quản lý bán hàng đa sàn cho thị trường Việt Nam — đồng bộ đơn, tồn kho, in vận đơn, đối soát… cho TikTok Shop (trước), rồi Shopee + Lazada. Modular monolith Laravel 11 + React (Vite) SPA nhúng cùng repo.

> **Đọc tài liệu trước khi code.** Mọi rule / kiến trúc / pipeline / phase nằm trong [`docs/`](docs/README.md). Bắt đầu từ [`docs/README.md`](docs/README.md) → [`docs/00-overview/roadmap.md`](docs/00-overview/roadmap.md) (đang ở **Phase 0 — Nền tảng**).

## Cấu trúc repo

```
.
├── app/                          # Laravel 11 + React (Vite) SPA — toàn bộ ứng dụng
├── docs/                         # tài liệu dự án (kim chỉ nam) — đọc trước khi code
├── sdk_tiktok_seller/            # SDK TikTok Shop (TypeScript, sinh từ OpenAPI) — chỉ tham khảo schema
├── docker-compose.yml            # DEV base: app(php-fpm), web(nginx), worker(horizon), scheduler, postgres, redis, minio(+init), gotenberg
├── docker-compose.override.yml   # DEV extras: bind-mount ./app, publish ports, vite(HMR), mailpit, RUN_MIGRATIONS — tự load khi `docker compose up`
├── docker-compose.prod.yml       # PROD — file độc lập (Portainer-friendly): ${VAR} substitution, network `proxy`, restart + log rotation, data volumes
├── .env.example                  # mẫu biến cho docker-compose.prod.yml (CLI: `cp .env.example .env`; Portainer: nhập ở UI). app/.env.example = mẫu cho app/.env (dev)
├── scripts/                      # backup.sh / restore.sh (Postgres + MinIO)
└── .github/workflows/            # CI (Pint · PHPStan · migrate · PHPUnit `--coverage` · FE lint+typecheck+build) + deploy-staging
```

## Chạy nhanh bằng Docker — dev (khuyên dùng)

```bash
docker compose up -d --build           # base + override (dev) tự merge
docker compose exec app php artisan migrate --seed   # migrate cũng chạy tự động (RUN_MIGRATIONS) — --seed cho dữ liệu mẫu (owner@demo.local / password)
```

> `app/.env` được **commit thẳng vào repo** (repo private) — clone về là dùng được luôn, không cần `cp .env.example .env` hay `key:generate`. `app/.env.example` giữ làm bản mẫu tài liệu (và làm khung cho `./.env` của prod). Sửa cấu hình dev = sửa `app/.env` rồi commit; bí mật chỉ-dành-cho-prod (APP_KEY prod, MAIL_PASSWORD, …) thì để trong `./.env` của prod, **không** commit.

- App: <http://localhost:8000>  ·  Horizon: <http://localhost:8000/horizon>  ·  Mailpit: <http://localhost:8025>  ·  MinIO console: <http://localhost:9001> (`omnisell` / `omnisell-secret`)
- Code trong `./app` được bind-mount (sửa là thấy ngay); Vite HMR ở service `vite`. Sau khi đổi `composer.lock`: `docker compose run --rm app composer install`. Build asset production tại chỗ: `docker compose exec vite npm run build`.

## Triển khai prod — domain `app.cmbcore.com`

Reverse proxy ngoài cluster (NPM/Caddy trên network `proxy`) map `app.cmbcore.com` → `http://cmb-web:80` + TLS. **Prod = một file độc lập `docker-compose.prod.yml`** (không kèm `docker-compose.yml`).

**CLI:**
```bash
docker network create proxy                              # 1 lần — proxy ngoài share network này
cp .env.example .env && chmod 600 .env                   # rồi điền: APP_KEY (mới — xem dưới), DB_PASSWORD, AWS_ACCESS_KEY_ID/SECRET, MAIL_*, SENTRY_LARAVEL_DSN, TIKTOK_* prod
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force    # migrate là bước có kiểm soát
# kiểm: curl https://app.cmbcore.com/api/v1/health
```

**Portainer (Git stack):** Add stack → Repository = repo này → **Compose path = `docker-compose.prod.yml`** (KHÔNG phải `docker-compose.yml` — file đó là dev base, tự kéo theo override) → điền các biến trên ở mục **Environment variables** → Deploy. Sau lần đầu, mở "Console" của container `cmb-app` chạy `php artisan migrate --force` (hoặc redeploy stack với webhook).

> Tạo `APP_KEY` mới cho prod (đừng tái dùng key trong `app/.env`): `docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show`. `./.env` (gốc repo) bị `.gitignore` chặn — chỉ `app/.env` (dev/chung) được commit (repo private). Chi tiết & Portainer: [`docs/07-infra/environments-and-docker.md`](docs/07-infra/environments-and-docker.md) §3.

## Chạy không cần Docker (dev nhanh, zero-setup)

Mặc định `.env.example` dùng **SQLite** + queue/cache/session **database** ⇒ không cần Postgres/Redis cho việc làm quen.

```bash
cd app
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
composer dev          # chạy song song: php artisan serve · queue:listen · pail (log) · vite
```

> Lưu ý: chạy đầy đủ Horizon cần Redis (`QUEUE_CONNECTION=redis`) — dùng stack Docker cho việc đó. Với `QUEUE_CONNECTION=database` thì `php artisan queue:work` đã đủ cho dev.

## Kiểm thử & chất lượng (giống CI)

```bash
cd app
vendor/bin/pint --test          # format
vendor/bin/phpstan analyse      # static analysis (Larastan level 5 + phpstan-baseline.neon)
php artisan migrate --force     # migration chạy được (sqlite)
php artisan test                # unit + feature (PHPUnit)
npm run typecheck && npm run build   # frontend
```

CI (`.github/workflows/ci.yml`) chạy đúng các bước trên; phải xanh thì PR mới merge — xem [`docs/07-infra/ci-cd-pipeline.md`](docs/07-infra/ci-cd-pipeline.md) và [`docs/09-process/ways-of-working.md`](docs/09-process/ways-of-working.md) (Definition of Done).

## Health check

`GET /api/v1/health` — trả `200` khi DB (critical) + cache + Redis + queue ổn, `503` nếu không. Dùng cho load balancer / uptime monitor.

## Đóng góp

Đọc [`docs/09-process/ways-of-working.md`](docs/09-process/ways-of-working.md): chỉ làm việc thuộc phase hiện tại, tính năng lớn viết spec trước, quyết định kiến trúc ghi ADR, đổi tài liệu trước rồi mới code, PR nhỏ + CI xanh + ≥1 review.
