# OmniSell / CMBcoreSeller

SaaS quản lý bán hàng đa sàn cho thị trường Việt Nam — đồng bộ đơn, tồn kho, in vận đơn, đối soát… cho TikTok Shop (trước), rồi Shopee + Lazada. Modular monolith Laravel 11 + React (Vite) SPA nhúng cùng repo.

> **Đọc tài liệu trước khi code.** Mọi rule / kiến trúc / pipeline / phase nằm trong [`docs/`](docs/README.md). Bắt đầu từ [`docs/README.md`](docs/README.md) → [`docs/00-overview/roadmap.md`](docs/00-overview/roadmap.md) (đang ở **Phase 0 — Nền tảng**).

## Cấu trúc repo

```
.
├── app/                  # Laravel 11 + React (Vite) SPA — toàn bộ ứng dụng
├── docs/                 # tài liệu dự án (kim chỉ nam) — đọc trước khi code
├── sdk_tiktok_seller/    # SDK TikTok Shop (TypeScript, sinh từ OpenAPI) — chỉ tham khảo schema
├── docker-compose.yml    # stack local: app, web(nginx), worker(horizon), scheduler, vite, postgres, redis, minio, gotenberg, mailpit
└── .github/workflows/    # CI (Pint · PHPStan · migrate · PHPUnit · FE typecheck + build)
```

## Chạy nhanh bằng Docker (khuyên dùng)

```bash
cp app/.env.example app/.env
docker compose up -d --build           # build image + khởi động toàn bộ
docker compose exec app php artisan migrate --seed   # (migrate cũng chạy tự động khi boot)
```

- App: <http://localhost:8000>
- Horizon (queue dashboard): <http://localhost:8000/horizon>
- Mailpit (email bắt được): <http://localhost:8025>
- MinIO console: <http://localhost:9001> (user `omnisell` / pass `omnisell-secret`)

Trong dev, code trong `./app` được bind-mount nên sửa là thấy ngay; Vite HMR chạy ở service `vite`. Sau khi đổi `composer.lock`: `docker compose run --rm app composer install`. Build asset production tại chỗ: `docker compose exec vite npm run build`. Chi tiết: [`docs/07-infra/environments-and-docker.md`](docs/07-infra/environments-and-docker.md).

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
