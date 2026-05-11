# CI/CD Pipeline

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. CI (mỗi push & pull request) — GitHub Actions
Pipeline phải **xanh** thì PR mới được merge (branch protection).

```
jobs:
  backend:
    - composer install
    - php artisan key:generate (env testing)
    - vendor/bin/pint --test            # format
    - vendor/bin/phpstan analyse          # Larastan, level cố định (tăng dần)
    - php artisan migrate --env=testing   # migration chạy được
    - vendor/bin/pest --coverage          # unit + feature + contract tests; ngưỡng coverage tối thiểu (đặt ở Phase 1, tăng dần)
  frontend:
    - npm ci
    - npm run lint                        # ESLint
    - npx tsc --noEmit                    # type check
    - npm run test                        # vitest (nếu có)
    - npm run build                       # build phải thành công
  contract-tests (channels):
    - chạy contract test cho mỗi ChannelConnector/CarrierConnector với fixtures (không gọi mạng thật)
```

- Cache: composer, npm, phpstan result.
- Chạy song song backend/frontend; báo lỗi rõ ràng.
- (Tuỳ chọn) build & push Docker image khi merge vào `main`/`release/*`.

## 2. Quality gates (RULES)
1. Không merge khi CI đỏ. Không "skip CI".
2. Không giảm ngưỡng coverage để cho qua — viết test.
3. PHPStan không thêm `@phpstan-ignore` bừa; nếu cần, giải thích trong PR.
4. Mọi PR có người review (xem `09-process/ways-of-working.md`).
5. Migration mới phải reversible (`down()`), hoặc ghi rõ lý do không reversible.

## 3. CD — quy trình release
- Trunk-based hoặc git-flow nhẹ (chốt ở `ways-of-working.md`): code vào `main` qua PR; release cắt từ `main` (tag `vX.Y.Z`).
- Deploy `staging` tự động khi merge `main`; deploy `production` thủ công (bấm nút) sau khi smoke test staging.
- Bước deploy prod: pull image → `php artisan down` (hoặc maintenance mode mềm) → `migrate --force` → `up` → warm cache → `artisan up`. Migration tương thích ngược (expand/contract pattern) để giảm downtime.
- Rollback: giữ N image gần nhất; rollback = deploy image cũ; migration nguy hiểm phải có kế hoạch rollback dữ liệu riêng.
- Sau deploy: kiểm health endpoint, Horizon đang chạy, Sentry không spike lỗi mới.

## 4. Bí mật trong CI/CD
- Secrets ở GitHub Actions Secrets / secret manager; không in ra log; không commit. Token sàn dùng cho CI là token sandbox/test, không phải prod.

## 5. Việc Phase 0
- [ ] Tạo workflow CI ở trên (backend + frontend + contract-tests).
- [ ] Branch protection cho `main` (require CI + 1 review).
- [ ] Script deploy staging (SSH + docker compose) hoặc workflow CD tối thiểu.
- [ ] Đặt ngưỡng coverage khởi điểm + level PHPStan khởi điểm (ghi vào đây).
