# Roadmap

**Status:** Living document · **Cập nhật:** 2026-05-11

> Quy tắc: mỗi phase chỉ "Done" khi đạt **Exit criteria** của nó. Không nhảy phase. Việc nào không thuộc phase hiện tại → ghi vào backlog của phase sau, **không làm xen**. Ước lượng tính cho team 2–4 dev.

## Tổng quan các phase

| Phase | Tên | Mục tiêu | Ước lượng |
|---|---|---|---|
| 0 | Nền tảng | Skeleton, hạ tầng, multi-tenant, khung connector | 2–3 tuần |
| 1 | TikTok — Đồng bộ đơn | Kết nối shop TikTok, đơn tự về, trạng thái chuẩn | 3–5 tuần |
| 2 | Đơn thủ công + SKU + Tồn kho lõi | Master SKU, ghép SKU, trừ/đẩy tồn | 3–4 tuần |
| 3 | Giao hàng & in ấn (TikTok) | Vận đơn, in hàng loạt, picking/packing, scan-to-pack, ĐVVC đợt 1 | 4–6 tuần |
| 4 | Shopee + Lazada | Slot 2 sàn vào sau khi có API | 6–10 tuần |
| 5 | WMS đầy đủ + Đăng bán đa sàn | Nhập/xuất/chuyển kho, kiểm kê, giá vốn FIFO, mass listing, ĐVVC đợt 2 | 8–12 tuần |
| 6 | Tài chính + Mua hàng + Báo cáo + Billing | Settlement, lợi nhuận, PO/NCC, báo cáo, gói thuê bao | 8–12 tuần |
| 7+ | Hậu mãi & nâng cao | Trả hàng/hoàn, chat hợp nhất, HĐĐT, tối ưu hiệu năng, PWA quét hàng | liên tục |

**Mốc lớn:** dùng được nội bộ ~hết Phase 3 (**~3–4 tháng**) · ra mắt 3 sàn ~hết Phase 5 (**~8–12 tháng**) · tiệm cận full BigSeller ~Phase 7 (**~18–24 tháng**).

---

## Phase 0 — Nền tảng  ◑ (code gần xong; còn việc ngoài-code)
**Việc:** mono repo Laravel 11 + React (Vite) embedded · routing `/api/v1/*` + `/webhook/*` + `/oauth/{provider}/callback` + catch-all → SPA · Docker Compose (app, worker, postgres, redis, minio, gotenberg, horizon, mailhog) · Sanctum SPA auth · multi-tenant + RBAC + sub-account khung · khung module + `ChannelRegistry`/`CarrierRegistry` + interface `ChannelConnector`/`CarrierConnector` + DTO chuẩn · migration nền + cơ chế partition theo tháng · Horizon supervisors · Sentry + logging · SPA shell (layout AntD, router, auth flow, trang Dashboard/Gian hàng rỗng) · CI lint+test · **(song song, không code) nộp hồ sơ Shopee + Lazada Open Platform, đăng ký app TikTok Shop Partner**.
**Exit criteria:** đăng ký/đăng nhập được, tạo tenant + mời thành viên, SPA chạy, queue chạy, CI xanh, Docker `up` là chạy được toàn bộ. Hồ sơ 2 sàn đã nộp.

**Trạng thái (2026-05-11):**
- ✅ Mono repo + routing 4 nhóm (`/api/v1`, `/webhook`, `/oauth/{provider}/callback`, catch-all → SPA).
- ✅ Sanctum SPA auth · multi-tenant + global scope + `BelongsToTenant` · RBAC (Role enum + permission map + `Gate::before`) · sub-account scope cột sẵn · audit log.
- ✅ Khung 11 module (service provider mỗi module) · `ChannelRegistry`/`CarrierRegistry` + interface `ChannelConnector`/`CarrierConnector` + DTO chuẩn + `ManualConnector`.
- ✅ Horizon supervisors (config theo `07-infra/queues-and-scheduler.md`) · scheduler skeleton (`routes/console.php`) · `db:partitions:ensure` + helper `MonthlyPartition`/`PartitionRegistry`.
- ✅ Sentry (web + queue) · log JSON ra stdout + middleware `request_id`/`trace_id` (vào log context, Sentry tag, header `X-Request-Id`, error envelope) · `GET /api/v1/health` (DB/cache/Redis/queue).
- ✅ SPA shell (layout AntD, router, auth flow, Dashboard, Gian hàng, **Cài đặt → Thành viên & phân quyền** với mời/đổi vai trò).
- ✅ CI GitHub Actions: backend (Pint · PHPStan/Larastan level 5 + baseline · migrate · PHPUnit `--coverage --min=60`, hiện ~72%) + frontend (ESLint · typecheck · build); workflow CD staging tối thiểu (`deploy-staging.yml`, manual). Docker Compose stack (`docker compose up`). Script backup/restore Postgres + MinIO (`scripts/backup.sh`, `scripts/restore.sh`) + runbook trong `07-infra/observability-and-backup.md`.
- ✅ ADR-0001…0010 (đã có file đầy đủ trong `01-architecture/adr/`): SPA-in-Laravel, Postgres+partition, modular monolith, connector/registry, VN-only, AntD 5, webhook+polling, master SKU, PK strategy, RBAC tự viết.
- ⏳ **Việc ngoài-code (không thuộc repo):** nộp hồ sơ Shopee + Lazada Open Platform, đăng ký app TikTok Shop Partner · bật branch protection cho `main` (require CI + 1 review) · cấu hình `SENTRY_LARAVEL_DSN` + alert rules + giám sát queue-depth ở môi trường thật · dựng host staging + secrets cho `deploy-staging.yml` · bật WAL/PITR Postgres + versioning/replicate MinIO + đẩy backup off-site + test khôi phục định kỳ.
- ⏳ **Còn lại trong code (nhỏ, không chặn Exit criteria):** contract-test job (thêm khi có connector sàn đầu tiên — Phase 1) · Prettier config FE · trang `/forgot-password` + `/onboarding` (sitemap FE) · backfill seeder/factory cho các bảng module khi schema ra đời.

## Phase 1 — TikTok Shop: Đồng bộ đơn  ◑ (code xong; còn kiểm thử với sandbox thật)
**Việc:** OAuth connect/disconnect gian hàng TikTok · HTTP client PHP cho TikTok (auth + ký HMAC, refresh token, orders, order detail) versioned · webhook receiver `/webhook/tiktok` + verify chữ ký + `ProcessWebhookEvent` job · polling `SyncOrdersForShop` (5–15') + backfill 90 ngày · trạng thái chuẩn + mapping TikTok + `order_status_history` · màn Đơn hàng (list/filter/detail/đổi trạng thái/tag/note) + Dashboard cơ bản · auto refresh token + cảnh báo re-connect.
**Exit criteria:** kết nối shop TikTok thật → đơn mới tự xuất hiện trong vòng vài phút (qua webhook hoặc polling), xem/lọc/đổi trạng thái được; mất webhook vẫn không mất đơn nhờ polling.

**Trạng thái (2026-05-12 · spec [`docs/specs/0001-tiktok-order-sync.md`](../specs/0001-tiktok-order-sync.md)):**
- ✅ Module **Channels** (`channel_accounts`, `oauth_states`, `webhook_events`, `sync_runs`) + module **Orders** (`orders`, `order_items`, `order_status_history`) — migrations, models, `OrderUpsertService` idempotent, `OrderStateMachine`, domain events.
- ✅ **TikTok connector** (`app/Integrations/Channels/TikTok/`): `TikTokConnector` (orders+auth+webhook; listing/fulfillment/finance khai `capability=false` cho Phase sau) · `TikTokClient` (ký HMAC-SHA256, version `202309`, refresh token, throttle per shop) · `TikTokSigner` · `TikTokStatusMap` · `TikTokMappers` (raw→DTO) · `TikTokWebhookVerifier`. Đăng ký `ChannelRegistry::register('tiktok', …)` + `INTEGRATIONS_CHANNELS=manual,tiktok` + block `config/integrations.tiktok` (sandbox vs prod = config; app key/secret từ `.env`).
- ✅ OAuth connect/disconnect/resync (`/api/v1/channel-accounts*` + callback `/oauth/tiktok/callback` + `WebhookController` cho `/webhook/tiktok`); webhook verify→ghi→`ProcessWebhookEvent` (re-fetch detail rồi upsert); polling `SyncOrdersForShop` (scheduled ~10') + `BackfillOrders`; auto refresh token (`RefreshChannelToken` + lệnh `channels:refresh-expiring-tokens` mỗi 30') → fail thì `channel_account.status=expired` + event `ChannelAccountNeedsReconnect`.
- ✅ API: `GET /channel-accounts`, `POST /channel-accounts/{provider}/connect`, `DELETE /channel-accounts/{id}`, `POST /channel-accounts/{id}/resync`, `GET /orders`(filter/sort/pagination), `GET /orders/{id}`, `GET /orders/stats`, `POST /orders/{id}/tags`, `PATCH /orders/{id}/note`, `GET /dashboard/summary`.
- ✅ FE phong cách BigSeller: layout sidebar nhóm + header (workspace switcher / chuông / user) · Dashboard (stat cards + to-do) · **Đơn hàng** (status tabs có badge số lượng, filter bar, bảng dày, phân trang) · **Chi tiết đơn** (timeline trạng thái, người nhận, kiện hàng, tags/note) · **Gian hàng** (thẻ gian hàng, kết nối TikTok → redirect OAuth, resync/disconnect, xử lý callback) · **Nhật ký đồng bộ** (2 tab: *Lần đồng bộ* = `sync_runs` với loại/trạng thái/kết quả, *Webhook* = `webhook_events` với loại sự kiện/chữ ký/trạng thái; lọc theo gian hàng, nút **chạy lại** cho owner/admin) · component chung `StatusTag`/`MoneyText`/`DateText`/`ChannelBadge`/`PageHeader`.
- ✅ API nhật ký đồng bộ: `GET /sync-runs`, `POST /sync-runs/{id}/redrive`, `GET /webhook-events` (không lộ payload thô), `POST /webhook-events/{id}/redrive` (`channels.view` để xem, `channels.manage` để re-drive).
- ✅ Test: contract TikTok (fixtures, `Http::fake`), feature webhook→upsert (idempotent) + polling→upsert + refresh token + orders API + tenant isolation + connect flow + nhật ký đồng bộ (list scoped + re-drive + phân quyền); unit `TikTokSigner`/`OrderStateMachine`/`StandardOrderStatus`. (79 test xanh.)
- ⏳ **Còn lại cho Exit criteria đầy đủ:** kết nối shop TikTok **sandbox thật** (cần `APP_URL` HTTPS công khai để TikTok redirect callback — dùng ngrok cho dev; app key/secret đã có trong `.env`) và xác nhận đơn mới tự về; rà soát mapping webhook event-type + order status với tài liệu Partner API thật; hoàn thiện rate-limit Redis throttle per shop; chuyển `orders`/`order_items`/`order_status_history` sang partition theo tháng khi volume cần.

## Phase 2 — Đơn thủ công + SKU + Tồn kho lõi + Sổ khách hàng  ◑ (Sổ khách hàng xong; SKU/tồn/đơn tay chưa)
**Việc:** sản phẩm/SKU master · kho (warehouses) + `inventory_levels` + `inventory_movements` (sổ cái) · tạo đơn thủ công (reserve tồn) · màn Liên kết SKU (manual + auto-match `seller_sku == sku_code`, hỗ trợ combo 1→N) cho listing TikTok · `PushStockToChannel` (debounce + distributed lock + safety stock) đẩy tồn lên TikTok · cảnh báo hết hàng/âm kho · **Sổ khách hàng & cờ rủi ro** (module `Customers` mới — SPEC [0002](../specs/0002-customer-registry-and-buyer-reputation.md)): match đơn theo SĐT chuẩn hoá → hồ sơ khách + lifetime stats + reputation badge + manual notes; card "Khách hàng" ở Order detail giúp NV soi lịch sử (huỷ/hoàn/giao thất bại) **trước khi xác nhận đơn**; auto-note khi vượt ngưỡng; block khách (rules engine Phase 6 sẽ tự xử lý).
**Exit criteria:** bán TikTok + tạo đơn tay đều trừ chung 1 kho; thay đổi tồn → tự đẩy lên listing TikTok liên kết; mọi thay đổi tồn có dòng trong sổ cái; **đơn mới về tự khớp vào hồ sơ khách (nếu có SĐT) và hiển thị lịch sử + reputation ở chi tiết đơn**.

**Trạng thái (2026-05-13 · spec [`docs/specs/0002-customer-registry-and-buyer-reputation.md`](../specs/0002-customer-registry-and-buyer-reputation.md)):**
- ✅ Module **Customers** (mới): `customers` + `customer_notes` migrations + thêm cột `orders.customer_id` (soft ref) · models `Customer`/`CustomerNote` · `CustomersServiceProvider` (bind `CustomerProfileContract` → `CustomerProfileResolver` + `CustomerProfileDTO`).
- ✅ `CustomerPhoneNormalizer` (chuẩn hoá VN `+84/84 → 0`, loại SĐT mask, canonical quốc tế) + `sha256` hash · `ReputationCalculator` (heuristic v1, config `config/customers.php`).
- ✅ Listener `LinkOrderToCustomer` (listen `OrderUpserted`, queue `customers`) → khớp/tạo khách theo `phone_hash`, set `orders.customer_id`, recompute `lifetime_stats` + reputation + auto-notes (dedupe theo ngưỡng) — idempotent (đọc thẳng từ `orders`).
- ✅ Block/unblock/merge (chuyển `orders.customer_id` + `customer_notes`, soft-delete bên bị gộp), tags, notes (manual + xoá note của mình) · domain events `CustomerLinked`/`CustomerReputationChanged`/`CustomerBlocked`/`CustomerUnblocked`/`CustomersMerged`.
- ✅ Ẩn danh hoá: event `DataDeletionRequested` (Channels phát từ `ProcessWebhookEvent`) + `ChannelAccountRevoked` → job `AnonymizeCustomersForShop` (ngay với data_deletion, trễ `customers.anonymize_after_days`≈90 ngày với disconnect) — giữ `phone_hash`/`lifetime_stats`, clear `phone`/`name`/`email`/`addresses_meta`; khách còn đơn ở shop khác thì giữ.
- ✅ Commands: `customers:backfill` (one-shot, khôi phục từ `orders`), `customers:recompute-stale` (scheduled hằng giờ — safety net) · queue `customers` thêm vào supervisor low-priority của Horizon.
- ✅ API: `GET /customers`(filter/sort/search SĐT→hash), `GET /customers/{id}`(+notes), `GET /customers/{id}/orders`, `POST /customers/{id}/notes`, `DELETE …/notes/{noteId}`, `POST …/block` / `…/unblock` / `…/tags`, `POST /customers/merge` · `OrderResource` thêm field `customer` qua `CustomerProfileContract`.
- ✅ FE: trang **Khách hàng** (`/customers` list: filter reputation tab + search + sort, `/customers/:id`: thông tin + ghi chú + lịch sử đơn + block) · `<ReputationBadge>` · `<CustomerSummaryCard>` (card "Khách hàng" ở `OrderDetailPage`) · mục sidebar "Khách hàng".
- ✅ RBAC: `customers.view` (mọi role), `customers.note`/`customers.view_phone` (owner/admin/staff_order), `customers.block`/`customers.merge` (owner/admin) · test: unit `CustomerPhoneNormalizer`/`ReputationCalculator`, feature linking (idempotent, matching đa-format, tenant isolation, vip), API (list/filter/search/notes/block/tags/merge/orders + customer card + phân quyền), anonymize. (118 test xanh.)
- ☐ **Chưa làm (phần còn lại Phase 2):** Products/SKU master + `channel_listings` · Warehouses + `inventory_levels`/`inventory_movements` (sổ cái) + reserve/release/ship · `OrderUpserted` → trừ/giữ tồn (hiện listener tồn kho vẫn no-op) · màn Liên kết SKU + auto-match + combo · `PushStockToChannel` (debounce + lock + safety stock) · tạo đơn thủ công · cảnh báo hết hàng/âm kho · backfill SKU cho `order_items` · FE Vitest cho trang Khách hàng.

## Phase 3 — Giao hàng & in ấn (TikTok)  ☐
**Việc:** luồng "sắp xếp vận chuyển" TikTok → lấy tracking + label PDF → lưu MinIO · **in vận đơn hàng loạt** (ghép PDF, sắp theo ĐVVC) · **picking/packing list** render bằng Gotenberg + **template tùy biến** · **quét mã đóng gói** → xác nhận đóng/bàn giao → trừ tồn → trạng thái shipped · kết nối ĐVVC riêng đợt 1: **GHN + GHTK + J&T** (quote/createShipment/getLabel/track/cancel) cho đơn manual & đơn tự xử lý · lô lấy hàng (pickup batch).
**Exit criteria:** từ list đơn → tạo vận đơn hàng loạt → in tem 1 file → quét từng kiện để xác nhận đóng gói → bàn giao ĐVVC → trạng thái & tồn cập nhật đúng.

## Phase 4 — Shopee + Lazada  ☐  *(bắt đầu sau khi có API)*
**Việc:** connector + OAuth + client + status map + sync đơn (push+pull) + listing + ghép SKU + push tồn + luồng in/label cho **Shopee** và **Lazada** · xử lý khác biệt (đơn nhiều kiện, COD, cấu trúc phí, document API).
**Exit criteria:** 3 sàn + đơn tay chạy chung một luồng; tồn đồng bộ chéo sàn; in tem cho cả 3 sàn.

## Phase 5 — WMS đầy đủ + Đăng bán đa sàn  ☐
**Việc:** nhập/xuất/điều chuyển kho + kiểm kê (có phiếu, chênh lệch) + giá vốn FIFO/bình quân · đăng sản phẩm lên nhiều sàn từ 1 SP gốc + sao chép listing + sửa hàng loạt + đồng bộ category/attribute sàn · ĐVVC đợt 2 (ViettelPost, NinjaVan, SPX, VNPost, Best, Ahamove/Grab).
**Exit criteria:** quản lý kho khép kín (nhập→bán→kiểm kê→báo cáo tồn) + đăng/sửa listing đa sàn từ một nơi.

## Phase 6 — Tài chính + Mua hàng + Báo cáo + Billing  ☐
**Việc:** kéo settlement từng sàn → `settlement_lines` → đối chiếu + tính lợi nhuận theo đơn/SP/gian hàng/thời gian · NCC + bảng giá nhập + PO + nhận hàng → giá vốn · báo cáo bán hàng/lợi nhuận + export · billing SaaS (gói, hạn mức `usage_counters`, dùng thử, VNPay/MoMo) · rules engine tự động hoá · thông báo đa kênh.
**Exit criteria:** biết được lãi/lỗ từng đơn; bán được gói thuê bao; tự động hoá được các thao tác lặp.

## Phase 7+ — Hậu mãi & nâng cao  ☐
Trả hàng/hoàn tiền; chat hợp nhất đa sàn; HĐĐT; tối ưu hiệu năng (read replica, search engine, archive partition); realtime UI (Reverb); PWA quét đóng gói.

---

## Backlog (chưa xếp phase — đừng làm cho tới khi được xếp)
- Tích hợp nguồn hàng 1688/Taobao · POS bán tại quầy · **CRM marketing đầy đủ** (campaign, gửi tin Zalo/SMS — *không* nhầm với "Sổ khách hàng" ở Phase 2, vốn chỉ là CRM nội bộ cho vận hành đơn) · đa quốc gia · API public cho bên thứ ba · marketplace plugin · gửi tin xác nhận đơn qua Zalo OA/SMS.
