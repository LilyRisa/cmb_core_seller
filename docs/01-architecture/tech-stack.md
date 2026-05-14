# Tech stack — quyết định đã khoá

**Status:** Stable · **Cập nhật:** 2026-05-11 · Mỗi dòng có ADR tương ứng trong `adr/`.

> Đây là danh sách "đã chốt". Muốn đổi → mở ADR mới ghi rõ lý do thay thế, không tự ý đổi.

## Backend

| Hạng mục | Chọn | Lý do |
|---|---|---|
| Ngôn ngữ/Framework | **PHP 8.3 + Laravel 11** | Yêu cầu của chủ dự án; hệ sinh thái đủ (queue/scheduler/Sanctum/Horizon). |
| Kiến trúc | **Modular monolith** (domain modules) | Đủ cho ~100 nhà bán; dễ hiểu/vận hành; mở rộng bằng module + connector, không bằng microservice. → ADR-0003 |
| DB | **PostgreSQL 15** | JSONB + GIN cho payload sàn; partition RANGE theo tháng cho bảng lớn; CTE/window mạnh cho báo cáo. → ADR-0002 |
| Cache / Queue / Lock | **Redis 7** + **Laravel Horizon** | Hàng đợi nhiều supervisor; distributed lock cho tồn kho; rate-limit per shop; cache. |
| Auth | **Laravel Sanctum (SPA, cookie)** | SPA cùng domain → không cần quản token bearer; CSRF cookie. |
| File / Object storage | **MinIO** (S3-compatible), Flysystem | Label PDF, ảnh SP, file export. Lên AWS S3 sau = đổi config. |
| Render PDF | **Gotenberg** (container Chromium) qua HTTP | In tem/picking/packing bằng HTML template → sửa layout không đụng code. |
| Search (sau) | Postgres trigram trước → **Meilisearch** khi cần | Tìm đơn/SP theo SĐT/mã/tracking trên triệu dòng. |
| Realtime (sau) | **Laravel Reverb** (WebSocket) | Cập nhật đơn mới / tiến độ in lên UI. |
| Tĩnh tích hợp ngoài | HTTP client của Laravel (Guzzle) | Viết client PHP riêng cho mỗi sàn/ĐVVC; SDK TikTok TS chỉ tham khảo schema. |
| Cổng thanh toán (Phase 6.4) | **SePay** (chuyển khoản qua webhook sao kê) + **VNPay** (redirect + IPN HMAC-SHA512) + **MoMo** skeleton | SePay = không phí cổng (chỉ phí ngân hàng), UX QR + memo; VNPay = quẹt thẻ/ATM/QR ngay trong 1 cú click; MoMo để khi shop yêu cầu. Pattern `PaymentGatewayConnector` + `PaymentRegistry` — thêm cổng mới = 1 connector + 1 dòng register. SPEC-0018. |
| Static analysis | **Larastan/PHPStan** (level cao dần), **Laravel Pint** | Bắt lỗi sớm; format thống nhất. |
| Test | **Pest** (unit/feature) + contract test cho connector | Xem `09-process/testing-strategy.md`. |

## Frontend

| Hạng mục | Chọn | Lý do |
|---|---|---|
| Framework | **React 18 + TypeScript** | Yêu cầu của chủ dự án. |
| Build | **Vite** + `laravel-vite-plugin` | Nhúng vào Laravel; HMR khi dev; build ra `public/build`. |
| UI kit | **Ant Design 5** | Bảng/filter/form/upload sẵn — hợp app quản lý đơn–kho kiểu BigSeller. |
| Data fetching | **TanStack Query (React Query)** + Axios (`withCredentials`) | Cache/refetch/optimistic; gọi `/api/v1`. |
| State cục bộ | **Zustand** | UI state nhẹ; không cần Redux. |
| Routing | **React Router v6** | Catch-all bên Laravel trỏ mọi path về SPA. |
| Form | **React Hook Form** + zod | Validate client; type-safe. |
| i18n | tiếng Việt mặc định (kết cấu i18n để mở rộng sau) | Thị trường VN. |
| Lint/format | **ESLint + Prettier**, `tsc --noEmit` trong CI | |

## Hạ tầng

| Hạng mục | Giai đoạn đầu | Sau này |
|---|---|---|
| Chạy app | **Docker Compose** trên VPS/VM (app + worker tách container) | Managed compute (ECS/Cloud Run) + nhiều worker |
| DB | Postgres trong Docker (volume + backup script) | RDS/Cloud SQL + read replica + PITR |
| Cache/Queue | Redis trong Docker | ElastiCache/Memorystore |
| Storage | MinIO trong Docker | AWS S3 |
| CI/CD | GitHub Actions (lint + test + build); deploy bằng SSH/`docker compose pull && up` | Pipeline build image → registry → rolling deploy |
| Monitoring | Sentry (lỗi) + log file/JSON + Horizon dashboard | + APM/metrics (Prometheus/Grafana) |

## Phiên bản & nâng cấp
- Khoá phiên bản chính trong `composer.json` / `package.json`; nâng cấp có chủ đích, kèm test.
- Client API sàn **versioned theo version của sàn** (TikTok: v202309 → v202601...); một adapter chuyển version → DTO chuẩn → nâng version không vỡ core. Xem `04-channels/`.
