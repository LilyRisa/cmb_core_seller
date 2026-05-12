# 📚 Tài liệu dự án — OmniSell (SaaS quản lý bán hàng đa sàn)

> **Mục đích thư mục này:** Mọi rule, pipeline, spec, quyết định kiến trúc của dự án nằm ở đây. Trước khi code bất kỳ thứ gì — đọc tài liệu liên quan. Khi quyết định gì đó mới — ghi lại vào đây. **Nếu một việc không phù hợp với tài liệu này → hoặc đừng làm, hoặc cập nhật tài liệu trước rồi mới làm.** Đây là "kim chỉ nam" để không lan man.

## Đọc theo thứ tự này nếu bạn là người mới

1. [`00-overview/vision-and-scope.md`](00-overview/vision-and-scope.md) — Dự án là gì, làm gì, **KHÔNG** làm gì.
2. [`00-overview/roadmap.md`](00-overview/roadmap.md) — Các phase, mốc lớn, đang ở đâu.
3. [`01-architecture/overview.md`](01-architecture/overview.md) — Kiến trúc tổng thể.
4. [`01-architecture/extensibility-rules.md`](01-architecture/extensibility-rules.md) — **Luật mở rộng** (thêm sàn / ĐVVC mới thế nào). Đọc kỹ.
5. [`09-process/ways-of-working.md`](09-process/ways-of-working.md) — Quy trình làm việc, Definition of Done.

## Cây thư mục

```
docs/
├── README.md                        ← bạn đang ở đây (index + luật vàng)
├── 00-overview/
│   ├── vision-and-scope.md          Tầm nhìn, phạm vi, non-goals, tiêu chí thành công
│   ├── roadmap.md                   Phase 0→7, mốc lớn, trạng thái
│   └── glossary.md                  Từ điển thuật ngữ (tenant, listing, master SKU, ...)
├── 01-architecture/
│   ├── overview.md                  Sơ đồ kiến trúc, các thành phần
│   ├── tech-stack.md                Quyết định công nghệ đã khoá + lý do
│   ├── modules.md                   Bản đồ domain module + luật phụ thuộc
│   ├── extensibility-rules.md        ★ Connector/Registry pattern, cách thêm sàn/ĐVVC
│   ├── multi-tenancy-and-rbac.md    Tenant, sub-account, phân quyền, cách ly dữ liệu
│   └── adr/                         Architecture Decision Records (mỗi quyết định 1 file)
├── 02-data-model/
│   └── overview.md                  Quy ước DB, partition, danh mục bảng theo module
├── 03-domain/
│   ├── order-status-state-machine.md  Trạng thái chuẩn + mapping từng sàn + tác động tồn kho
│   ├── order-sync-pipeline.md       Pipeline đồng bộ đơn (webhook + polling)
│   ├── inventory-and-sku-mapping.md Master SKU, ghép SKU, đẩy tồn, chống oversell
│   ├── fulfillment-and-printing.md  Vận đơn, in hàng loạt, picking/packing, quét đóng gói
│   ├── manual-orders-and-finance.md Tạo đơn thủ công + đối soát/lợi nhuận
│   └── customers-and-buyer-reputation.md  (Phase 2) Sổ khách hàng — match đơn theo SĐT chuẩn hoá, reputation, ẩn danh hoá
├── 04-channels/
│   ├── README.md                    Hợp đồng ChannelConnector + DTO chuẩn
│   ├── tiktok-shop.md               Đặc tả tích hợp TikTok Shop (làm trước)
│   ├── shopee.md                    (chờ cấp API)
│   └── lazada.md                    (chờ cấp API)
├── 05-api/
│   ├── conventions.md               Quy ước /api/v1, response envelope, lỗi, phân trang
│   ├── endpoints.md                 Danh mục endpoint hiện có (auth, tenant, health) — cập nhật khi thêm API
│   └── webhooks-and-oauth.md        /webhook/{provider}, verify chữ ký, /oauth/callback
├── 06-frontend/
│   ├── overview.md                  React-in-Laravel, Vite, AntD, React Query, cấu trúc
│   └── orders-filter-panel.md       Panel "Lọc" trang Đơn hàng — chip rows kiểu BigSeller + hợp đồng /orders/stats
├── 07-infra/
│   ├── environments-and-docker.md   Môi trường dev/staging/prod, Docker Compose
│   ├── portainer-deploy.md          ★ Runbook deploy prod qua Portainer (checklist, migrate, kiểm webhook/worker)
│   ├── ci-cd-pipeline.md            Pipeline CI/CD, quy trình release
│   ├── queues-and-scheduler.md      Horizon supervisor, job định kỳ
│   └── observability-and-backup.md  Sentry, log, metric, backup/DR
├── 08-security-and-privacy.md       Bảo mật + xử lý dữ liệu cá nhân buyer + secrets
├── 09-process/
│   ├── ways-of-working.md           Git flow, PR, code review, Definition of Done
│   ├── coding-standards.md          Chuẩn code PHP & TypeScript
│   └── testing-strategy.md          Chiến lược test (unit/feature/contract)
└── specs/
    ├── README.md                    Cách viết một feature spec
    ├── _TEMPLATE.md                 Mẫu spec — copy ra khi làm tính năng mới
    └── <NNNN>-<ten-tinh-nang>.md    Mỗi tính năng lớn = 1 spec, đánh số tăng dần
```

## Luật vàng (đọc lại mỗi khi định "phá rào")

1. **Một nguồn sự thật.** Tồn kho = master SKU. Trạng thái đơn = state machine chuẩn. Không tạo nguồn dữ liệu song song.
2. **Core không biết tên sàn.** Mọi thứ riêng của TikTok/Shopee/Lazada nằm trong Connector của nó; core chỉ làm việc với DTO chuẩn. (Xem `extensibility-rules.md`.)
3. **Module nói chuyện qua interface + event.** Không gọi thẳng vào ruột module khác.
4. **Webhook không đáng tin → luôn có polling backup.** Mọi job đồng bộ phải idempotent.
5. **Mọi quyết định kiến trúc → ghi 1 ADR.** Không có "quyết định miệng".
6. **Mọi tính năng lớn → viết spec trước khi code.** PR phải link tới spec.
7. **`tenant_id` ở mọi bảng nghiệp vụ.** Không có query nào không scope theo tenant.
8. **Đổi tài liệu trước, code sau** — không phải ngược lại.

## Trạng thái dự án

| | |
|---|---|
| Phase hiện tại | **Phase 0 — Nền tảng** (code gần xong — xem trạng thái chi tiết trong [`roadmap.md`](00-overview/roadmap.md#phase-0--nền-tảng--code-gần-xong-còn-việc-ngoài-code); còn việc ngoài-code: hồ sơ sàn, branch protection, Sentry DSN, backup script) |
| Sàn đang làm | TikTok Shop (đã có SDK Partner API) |
| Sàn chờ cấp API | Shopee Open Platform, Lazada Open Platform — *đăng ký ngay tuần đầu* |
| Thị trường | Chỉ Việt Nam |
| Chạy thử | `cp app/.env.example app/.env && docker compose up -d --build` (xem README ở gốc repo) |
| Cập nhật gần nhất | 2026-05-11 |
