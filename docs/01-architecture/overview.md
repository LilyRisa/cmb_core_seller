# Kiến trúc tổng thể

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Một câu

**Modular monolith Laravel** (backend + nhận webhook) phục vụ **React SPA** nhúng cùng repo; mọi I/O với sàn/ĐVVC chạy **bất đồng bộ qua hàng đợi Redis (Horizon)**; dữ liệu ở **PostgreSQL**; file ở **MinIO/S3**; render PDF qua **Gotenberg**.

Quy mô mục tiêu (~100 nhà bán, ~500k đơn/tháng) **không cần microservice**. Ưu tiên: dễ hiểu, dễ vận hành, dễ mở rộng theo chiều "thêm sàn / thêm ĐVVC / thêm tính năng" — đạt được bằng **module hoá theo domain** + **connector/registry pattern**, không phải bằng tách service.

## 2. Sơ đồ thành phần

```
                                  ┌─────────────────────────────────────────────┐
   Browser ───────────────────────▶│  Laravel (web tier)                         │
   (React SPA, AntD)               │   /                 → app.blade.php (React)  │
                                   │   /{any}            → catch-all → React     │
   TikTok / Shopee / Lazada ──────▶│   /webhook/{provider} → verify + enqueue     │
   (webhook đẩy về)                │   /oauth/{provider}/callback → đổi token      │
                                   │   /api/v1/*         → REST JSON (Sanctum SPA) │
                                   └───────┬──────────────────────┬──────────────┘
                                           │                      │
                                  ┌────────▼────────┐   ┌─────────▼──────────┐
                                  │ PostgreSQL 15   │   │ Redis               │
                                  │  (partition     │   │  cache / queue /    │
                                  │   theo tháng)   │   │  lock / throttle    │
                                  │  + read replica │   └─────────┬──────────┘
                                  │   (sau, cho BC) │             │ pull jobs
                                  └────────▲────────┘   ┌─────────▼──────────────────┐
                                           │            │ Queue workers (Horizon)     │
   MinIO / S3 ◀── file (label PDF, ───────┤            │  supervisors: webhooks /     │
   ảnh SP, export Excel, picking PDF)      │            │  orders-sync / inventory-push│
                                           │            │  / labels / listings / tokens│
   Gotenberg ◀── render HTML→PDF ──────────┤            │  + Scheduler (cron)          │
   (container Chromium, cho phiếu in)       │            └─────────┬───────────────────┘
                                           │                      │ HTTP (REST + ký HMAC)
                                  ┌────────┴──────────────────────▼───────────────┐
                                  │  Integration layer (adapter/registry)          │
                                  │   ChannelRegistry → TikTokConnector / Shopee.. │
                                  │   CarrierRegistry → GhnConnector / GhtkConnector│
                                  └────────────────────────────────────────────────┘
                                           ▲ Sentry (errors) · structured logs
```

## 3. Tầng (layers) bên trong monolith

1. **HTTP tier** — Controllers mỏng: validate request → gọi Service → trả Resource. 3 nhóm route: `api.php` (`/api/v1/*`, middleware `auth:sanctum` + `tenant`), `webhook.php` (`/webhook/*`, không CSRF, không auth, middleware verify chữ ký), `web.php` (`/oauth/*` callback + catch-all SPA).
2. **Domain modules** (`app/Modules/*`) — chứa Services, Models, Jobs, Events, Listeners, DTO, Policies của từng domain. **Đây là nơi nghiệp vụ sống.** Module chỉ phụ thuộc nhau qua **Contract (interface)** trong `app/Modules/<X>/Contracts` và qua **domain event**. Xem `modules.md`.
3. **Integration layer** (`app/Integrations/Channels/*`, `app/Integrations/Carriers/*`) — implement `ChannelConnector` / `CarrierConnector`, biết HTTP/ký/version của từng bên ngoài, chuyển đổi qua/lại **DTO chuẩn**. **Không** chứa nghiệp vụ. Xem `extensibility-rules.md`.
4. **Infra** — DB (Eloquent + migration + partition), Redis, queue/Horizon, storage (Flysystem→MinIO/S3), PDF (Gotenberg client), mail, Sentry.
5. **Frontend** (`resources/js/*`) — React SPA, build bằng Vite, mount vào 1 blade view. Xem `06-frontend/overview.md`.

## 4. Luồng tiêu biểu

**Đơn mới về (đẩy qua webhook):**
`Sàn → POST /webhook/{provider}` → middleware verify chữ ký → ghi `webhook_events` (pending) → trả `200` ngay → dispatch `ProcessWebhookEvent` (queue `webhooks`) → connector `parseWebhook()` xác định loại + id đơn → gọi `fetchOrderDetail()` → `OrderUpsertService` upsert đơn idempotent (unique `source+channel_account+external_order_id`, chỉ ghi nếu `update_time` mới hơn) → map trạng thái → ghi `order_status_history` → fire event `OrderUpdated` → listener: reserve/release tồn → fire `InventoryChanged` → debounce `PushStockToChannel`.

**Đơn không có webhook (bù bằng polling):** Scheduler chạy `SyncOrdersForShop` mỗi 5–15' cho từng channel account active → `fetchOrders(since=last_synced_at, cursor)` phân trang → cùng `OrderUpsertService` ở trên → cập nhật `last_synced_at` & `sync_runs`.

**In vận đơn hàng loạt:** user chọn N đơn ở SPA → `POST /api/v1/print-jobs` → tạo `print_jobs` (pending) → job `GenerateBulkLabel` (queue `labels`): với mỗi đơn, nếu chưa có label thì gọi connector `arrangeShipment()` → `getShippingDocument()` (PDF từ sàn) hoặc CarrierConnector `createShipment()`+`getLabel()` (ĐVVC riêng) → lưu MinIO → ghép tất cả PDF (theo ĐVVC) → lưu file kết quả → cập nhật `print_jobs.file_url` + status `done` → SPA poll/nhận realtime → tải về in.

## 5. Nguyên tắc kiến trúc (rút gọn — chi tiết ở các file khác)

- **Core không biết tên sàn.** Mọi cái riêng của một sàn nằm trong connector của nó.
- **Một nguồn sự thật**: tồn kho = master SKU; trạng thái đơn = state machine chuẩn.
- **Bất đồng bộ mặc định**: gọi API ngoài, gửi mail, sinh PDF → luôn qua job; controller không chờ.
- **Idempotent mọi nơi**: webhook, upsert đơn, push tồn — chạy lại không hỏng.
- **Module tách bạch**: phụ thuộc qua interface + event; cấm import ruột module khác.
- **Multi-tenant cứng**: `tenant_id` mọi bảng nghiệp vụ + global scope + policy.
- **Có thể vứt đi & dựng lại từ Docker Compose** trong vài phút.

## 6. Cây thư mục backend (dự kiến)

```
app/
├── Modules/
│   ├── Tenancy/        (Tenant, User, Member, Role, Policies, global scope)
│   ├── Channels/       (ChannelAccount, OAuth flow, ChannelRegistry, webhook dispatch, sync orders/listings)
│   ├── Orders/         (Order, OrderItem, status state machine, OrderUpsertService, manual orders, merge/split)
│   ├── Inventory/      (SKU, Warehouse, InventoryLevel, InventoryMovement, push stock, sku mapping)
│   ├── Products/       (Product master, ChannelListing, mass listing, copy listing)
│   ├── Fulfillment/    (Shipment, PickupBatch, PrintJob, PrintTemplate, CarrierRegistry, scan-to-pack)
│   ├── Procurement/    (Supplier, PurchaseOrder, GoodsReceipt)
│   ├── Finance/        (Settlement, SettlementLine, OrderCost, ProfitSnapshot)
│   ├── Reports/        (báo cáo, export)
│   ├── Billing/        (Plan, Subscription, Invoice, UsageCounter)
│   └── Settings/       (AutomationRule, Notification, NotificationChannel)
├── Integrations/
│   ├── Channels/
│   │   ├── Contracts/  (ChannelConnector interface, DTO chuẩn)
│   │   ├── TikTok/     (client versioned: v202309.., signing, ánh xạ DTO, status map)
│   │   ├── Shopee/     (sau)
│   │   └── Lazada/     (sau)
│   └── Carriers/
│       ├── Contracts/  (CarrierConnector interface)
│       ├── Ghn/  Ghtk/  JtExpress/  ViettelPost/  ...
│       └── ...
├── Http/               (Controllers mỏng, Requests, Resources, Middleware)
├── Support/            (helpers chung: PDF client, money, address VN, signing utils)
└── Console/            (Kernel scheduler, commands)
```
