# SPEC 0001: TikTok Shop — kết nối gian hàng & đồng bộ đơn

- **Trạng thái:** Reviewed → Implemented (đang triển khai Phase 1)
- **Phase:** 1
- **Module backend liên quan:** Channels (chính), Orders, Integrations/Channels/TikTok
- **Tác giả / Ngày:** Team · 2026-05-12
- **Liên quan:** ADR-0001, ADR-0004, ADR-0007; doc `03-domain/order-status-state-machine.md`, `03-domain/order-sync-pipeline.md`, `04-channels/README.md`, `04-channels/tiktok-shop.md`, `05-api/webhooks-and-oauth.md`.

## 1. Vấn đề & mục tiêu
Nhà bán cần: kết nối **một gian hàng TikTok Shop thật (sandbox)** vào hệ thống → đơn mới **tự xuất hiện trong vài phút** (qua webhook hoặc polling) → xem/lọc/đổi trạng thái; mất webhook vẫn không mất đơn nhờ polling; token tự gia hạn, hỏng thì cảnh báo re-connect. Thuộc **Phase 1** (xem roadmap). TikTok dùng **môi trường sandbox**: base URL + app key/secret cấu hình qua `.env` (`config/integrations.php` → `integrations.tiktok.*`).

## 2. Trong / ngoài phạm vi của spec này
- **Trong:** OAuth connect/disconnect gian hàng TikTok; `TikTokClient` (HTTP + ký HMAC-SHA256 + timestamp + version `202309`, refresh token); webhook receiver `/webhook/tiktok` + verify chữ ký + job `ProcessWebhookEvent`; polling `SyncOrdersForShop` (mỗi ~10') + backfill 90 ngày (`BackfillOrders`); trạng thái chuẩn + `TikTokStatusMap` + `order_status_history`; `OrderUpsertService` idempotent; màn Đơn hàng (list/filter/detail/tag/note) + màn Gian hàng (connect/resync/disconnect) + Dashboard cơ bản; auto refresh token (`RefreshChannelToken` + lệnh `RefreshExpiringTokens`) + cảnh báo re-connect (`channel_account.status = expired`).
- **Ngoài (Phase sau):** đẩy tồn lên TikTok (Phase 2 — `updateStock` để `capability=false` ở Phase 1), listing/sản phẩm (Phase 2/5), arrange shipment / in label (Phase 3), settlement (Phase 6), trả hàng/hoàn (Phase 7); Shopee/Lazada (Phase 4); đơn manual (Phase 2); người dùng tự đổi trạng thái "lõi" của đơn sàn (chỉ qua hành động được sàn cho phép — Phase sau).

## 3. Luồng chính
**Kết nối:** SPA `/channels` → "Kết nối TikTok Shop" → `POST /api/v1/channel-accounts/tiktok/connect` → tạo `oauth_states(state, provider, tenant_id, expires_at)` → trả `{auth_url}` → SPA redirect tới `auth_url` (TikTok) → seller đồng ý → TikTok redirect `GET /oauth/tiktok/callback?app_key=..&code=..&state=..` → tra `oauth_states` lấy `tenant_id` → `connector.exchangeCodeForToken(code)` → `connector.fetchShopInfo()` (lấy `shop_cipher`) → upsert `channel_account` (token mã hoá, `meta.shop_cipher`, `status=active`) → `connector.registerWebhooks()` (best-effort) → dispatch `BackfillOrders(channel_account, 90)` → xoá `oauth_states` → redirect SPA `/channels?connected=tiktok`.

**Đơn về (push):** TikTok → `POST /webhook/tiktok` → `TikTokWebhookVerifier.verify()` (sai ⇒ 401, không ghi) → `WebhookIngestService`: parse loại event + `external_order_id` + `external_shop_id`, dedupe `(provider, external_id, event_type)`, ghi `webhook_events(pending, payload)`, trả 200 ngay → dispatch `ProcessWebhookEvent` (queue `webhooks`) → resolve tenant + channel_account theo `shop_id` → với event đơn: `connector.fetchOrderDetail(id)` → `OrderUpsertService.upsert(OrderDTO)` → fire `OrderUpserted`/`OrderStatusChanged`; `shop_deauthorized` ⇒ `channel_account.status=revoked` + dừng sync; `data_deletion` ⇒ enqueue job ẩn danh (Phase 7, hiện log). Hoàn tất ⇒ `webhook_events.status=processed`.

**Đơn về (pull):** Scheduler mỗi ~10' → mỗi `channel_account` active (TikTok) → `SyncOrdersForShop` (queue `orders-sync`, `ShouldBeUnique` theo shop) → `connector.fetchOrders(updatedFrom = last_synced_at − overlap, cursor)` lặp phân trang → mỗi `OrderDTO` → `OrderUpsertService.upsert` → ghi `sync_runs(stats)` → `channel_account.last_synced_at = thời điểm bắt đầu run − overlap`. Backfill: `BackfillOrders` cùng cơ chế, `updatedFrom = now − 90 ngày`, `sync_runs(type=backfill)`.

## 4. Hành vi & quy tắc nghiệp vụ
- **State machine & mapping:** theo `03-domain/order-status-state-machine.md`; chuỗi `raw_status` TikTok **chỉ** nằm trong `TikTokStatusMap`. Sàn là nguồn sự thật; lùi trạng thái ⇒ vẫn ghi + `has_issue` nếu lùi bất thường.
- **Idempotency:** `orders` unique `(source, channel_account_id, external_order_id)`; bỏ qua nếu `OrderDTO.sourceUpdatedAt <= order.source_updated_at`; `webhook_events` dedupe `(provider, external_id, event_type)`; chạy lại job 2 lần = kết quả như 1 lần; upsert đơn trong 1 transaction, fire event `afterCommit`.
- **Token:** sắp hết hạn (`<24h` trước `token_expires_at`) ⇒ `RefreshChannelToken`; refresh fail ⇒ `channel_account.status=expired`, dừng sync shop đó, fire `ChannelAccountNeedsReconnect`. Mọi token lưu `encrypted`; không log token/secret.
- **Rate limit:** throttle per `(provider, shop)` bằng Redis limiter trong `TikTokClient`; tôn trọng 429/`Retry-After`. Một shop lỗi không làm nghẽn queue chung (job cô lập, throttle).
- **Tác động tồn kho:** Phase 1 **chưa** có module Inventory ⇒ `OrderUpserted` listener cho tồn kho là **no-op stub** (sẽ nối ở Phase 2). Không reserve/release tồn ở Phase 1.
- **Phân quyền:** `channels.view` để xem gian hàng; chỉ `owner`/`admin` được connect/disconnect/resync (permission `channels.manage`). `orders.view` để xem đơn; `orders.update`/`orders.status` để sửa tag/note (đổi trạng thái lõi của đơn sàn: chặn ở Phase 1).

## 5. Dữ liệu
**Module Channels** (sở hữu): `channel_accounts` (tenant_id, provider, external_shop_id, shop_name, shop_region, seller_type, status[active|expired|revoked|disabled], access_token🔒, refresh_token🔒, token_expires_at, refresh_token_expires_at, last_synced_at, last_webhook_at, meta jsonb{shop_cipher, scope, open_id...}, created_by; unique `(tenant_id, provider, external_shop_id)`; index `(tenant_id, provider, status)`) · `oauth_states` (state🔑, provider, tenant_id, redirect_after, created_by, expires_at; **không** dùng `BelongsToTenant` — callback resolve tenant từ đây) · `webhook_events` (provider, event_type, external_id, external_shop_id, tenant_id?, channel_account_id?, signature_ok, payload jsonb, headers jsonb, received_at, processed_at?, status[pending|processed|ignored|failed], attempts, error?; index dedupe `(provider, event_type, external_id)`) · `sync_runs` (tenant_id, channel_account_id, type[poll|backfill|webhook], status[running|done|failed], started_at, finished_at?, cursor?, stats jsonb{fetched, created, updated, skipped, errors}, error?).

**Module Orders** (sở hữu): `orders` (tenant_id, source, channel_account_id?, external_order_id?, order_number?, status, raw_status, payment_status?, buyer_name?, buyer_phone🔒?, shipping_address jsonb, currency, item_total, shipping_fee, platform_discount, seller_discount, tax, cod_amount, grand_total, is_cod, fulfillment_type?, placed_at, paid_at?, shipped_at?, delivered_at?, completed_at?, cancelled_at?, cancel_reason?, note?, tags jsonb, has_issue, issue_reason?, packages jsonb, raw_payload jsonb, source_updated_at, last_synced_at; unique `(source, channel_account_id, external_order_id)`; index `(tenant_id, status)`, `(tenant_id, source, placed_at)`, `(tenant_id, channel_account_id)`; soft delete) · `order_items` (tenant_id, order_id, external_item_id, external_product_id?, external_sku_id?, seller_sku?, sku_id?[null tới Phase 2], name, variation?, quantity, unit_price, discount, subtotal, image?, raw jsonb; unique `(order_id, external_item_id)`) · `order_status_history` (tenant_id, order_id, from_status?, to_status, raw_status?, source[channel|polling|webhook|user|system|carrier], changed_at, payload jsonb).

> **Partition theo tháng:** `02-data-model/overview.md` §1 rule 9 liệt kê `orders`/`order_items`/`order_status_history`/`webhook_events`/`sync_runs` là đích partition. Phase 1 tạm để **bảng thường** (đơn giản, tránh ràng buộc khoá-phân-vùng-trong-unique phải thiết kế migration kỹ); helper `MonthlyPartition` + lệnh `db:partitions:ensure` + `PartitionRegistry` đã sẵn sàng để chuyển khi volume cần. Ghi nhận ở đây để không mất dấu.

**Domain event:** `OrderUpserted(Order, bool created)`, `OrderStatusChanged(Order, from, to, source)` (Orders phát) · `ChannelAccountConnected`, `ChannelAccountNeedsReconnect`, `ChannelAccountRevoked` (Channels phát). Listener tồn kho: stub no-op (Phase 2 nối).

**Migration:** reversible; index như trên; không cascade delete xuyên module (orders giữ `channel_account_id` lỏng).

## 6. API & UI
**Endpoint mới** (cập nhật `05-api/endpoints.md`):
- `GET /api/v1/channel-accounts` — danh sách gian hàng (auth + tenant; `channels.view`).
- `POST /api/v1/channel-accounts/{provider}/connect` — bắt đầu OAuth, trả `{auth_url}` (`channels.manage`). `provider ∈ {tiktok}` ở Phase 1.
- `DELETE /api/v1/channel-accounts/{id}` — ngắt kết nối (`connector.revoke` best-effort → `status=revoked`, giữ lịch sử đơn; dừng sync) (`channels.manage`).
- `POST /api/v1/channel-accounts/{id}/resync` — dispatch `SyncOrdersForShop` ngay (`channels.manage`).
- `GET /api/v1/orders` — list (filter `status`, `source`, `channel_account_id`, `q`[order#/buyer phone], `placed_from`/`placed_to`, `has_issue`; `sort=-placed_at|placed_at|-grand_total`; page-based `page`/`per_page`≤100; `include=items` whitelist). `orders.view`.
- `GET /api/v1/orders/{id}` — chi tiết (kèm `items`, `status_history`). `orders.view`.
- `POST /api/v1/orders/{id}/tags` — `{add?:[], remove?:[]}` cập nhật tags (`orders.update`).
- `PATCH /api/v1/orders/{id}/note` — `{note}` (`orders.update`).
- `GET /api/v1/orders/stats` — đếm theo trạng thái (cho tab counts ở UI). `orders.view`.
- `GET /api/v1/dashboard/summary` — đếm gian hàng / đơn hôm nay / đơn chờ xử lý / đơn lỗi. `dashboard.view`.
- Web: `GET /oauth/tiktok/callback` — đã có route stub, nay nối handler chung.
- Webhook: `POST /webhook/tiktok` — đã có route stub, nay nối `WebhookController@handle('tiktok')`.

**Connector** (`ChannelConnector` của TikTok dùng): `buildAuthorizationUrl`, `exchangeCodeForToken`, `refreshToken`, `fetchShopInfo`, `registerWebhooks`, `revoke`, `fetchOrders`, `fetchOrderDetail`, `parseWebhook`, `verifyWebhookSignature`, `mapStatus`. `updateStock` → `UnsupportedOperation` (capability `listings.updateStock=false` ở Phase 1). **Không** thêm `if ($provider==='tiktok')` ở module Channels/Orders — khác biệt nằm trong connector.

**Job mới** (cập nhật `07-infra/queues-and-scheduler.md`): `ProcessWebhookEvent` (queue `webhooks`), `SyncOrdersForShop` (queue `orders-sync`, unique/shop, scheduled ~10'), `BackfillOrders` (queue `orders-sync`), `RefreshChannelToken` (queue `tokens`), lệnh scheduled `RefreshExpiringTokens` (mỗi 30'), `BackfillRecentOrders` (hằng ngày — gọi `SyncOrdersForShop` với cửa sổ rộng hơn).

**FE** (`06-frontend/overview.md`): `features/channels` (list gian hàng, connect/resync/disconnect, trang callback success), `features/orders` (list + filter + status tabs + detail + tag/note + bulk select), `features/dashboard` (stat cards + to-do), `features/sync-logs` (trang Nhật ký đồng bộ: 2 tab `sync_runs` / `webhook_events` + lọc + nút chạy lại). Component chung: `<StatusTag>`, `<MoneyText>`, `<DateText>`, `<ChannelBadge>`, `<PageHeader>`, `<DataTable>` (bọc AntD Table). Filter phản ánh trong URL. Phân quyền UI bằng `useCan`. Giao diện theo phong cách BigSeller (sidebar tối có nhóm, header có workspace switcher + chuông + user menu, bảng dày, status tabs có badge số lượng).

## 7. Edge case & lỗi
- Shop đã kết nối ở tenant khác ⇒ callback báo lỗi rõ ràng, không tạo `channel_account` nửa vời.
- `state` hết hạn / không có ⇒ trang lỗi thân thiện, redirect SPA `/channels?error=oauth_state`.
- Token hết hạn giữa chừng ⇒ `TikTokClient` thử `refreshToken` 1 lần; vẫn lỗi ⇒ `channel_account.status=expired`, dừng sync, cảnh báo.
- Rate limit 429 ⇒ tôn trọng `Retry-After`, retry job với backoff.
- TikTok API lỗi `code != 0` ⇒ ném exception có `code`/`message`; job retry; quá hạn ⇒ `sync_runs.status=failed` / `webhook_events.status=failed` + cảnh báo; màn **Nhật ký đồng bộ** cho xem và **chạy lại** (`POST /sync-runs/{id}/redrive`, `POST /webhook-events/{id}/redrive`).
- Webhook trùng / đến trễ / out-of-order ⇒ dedupe + so `source_updated_at`; webhook event không nhận diện được loại ⇒ `webhook_events.status=ignored` + log.
- SKU chưa ghép (`order_items.sku_id = null`) ⇒ Phase 1 chấp nhận (ghép SKU là Phase 2); không reserve tồn.
- Số tiền TikTok dạng chuỗi có đơn vị ⇒ `TikTokMappers` parse cẩn thận về `bigint` đồng (VND không thập phân).
- Đơn nhiều package ⇒ lưu `orders.packages` jsonb (mảng); shipment riêng để Phase 3.

## 8. Bảo mật & dữ liệu cá nhân
- `access_token`/`refresh_token`/`buyer_phone` lưu `encrypted` (Laravel cast). Không log token/secret/SĐT đầy đủ (mask). Webhook payload thô lưu `webhook_events.payload` (có thể chứa PII buyer) — chỉ dùng nội bộ, không lộ ra API ngoài; ẩn danh hoá khi `data_deletion`/disconnect (job ẩn danh — Phase 7, hiện log + TODO).
- `data_deletion` webhook ⇒ enqueue job ẩn danh dữ liệu buyer của shop đó (xem `08-security-and-privacy.md`) — Phase 1: log + đánh dấu, job thật ở Phase 7.
- OAuth `state` chống CSRF (random, hết hạn 10'); resolve tenant từ `oauth_states` chứ không từ session ở callback.

## 9. Kiểm thử
- **Unit:** `TikTokSigner` (ký đúng theo vector cố định); `TikTokStatusMap` (mọi `raw_status` → đúng chuẩn); `OrderStateMachine` (cạnh hợp lệ/không hợp lệ); `TikTokMappers` parse tiền/địa chỉ.
- **Feature:** webhook → verify → ghi `webhook_events` → `ProcessWebhookEvent` → `OrderUpsertService` (idempotent: chạy 2 lần không tạo dòng `order_status_history` thừa); polling → upsert; refresh token (mock HTTP) → cập nhật token; refresh fail → `status=expired`; `GET /orders` filter/sort/pagination trả đúng envelope; tenant isolation (đơn tenant A không lộ cho user tenant B); `POST /tags`, `PATCH /note`; connect flow (`POST /channel-accounts/tiktok/connect` trả `auth_url`, callback tạo `channel_account`).
- **Contract (TikTok):** fixtures ở `tests/Fixtures/Channels/tiktok/` (response mẫu `orders/search`, `orders` detail, `token/get`, `authorization/shops`, webhook body) → `TikTokConnector` trả đúng `OrderDTO`/`TokenDTO`/`ShopInfoDTO`/`WebhookEventDTO`; `verifyWebhookSignature` đúng/sai. **Không gọi mạng thật** — `Http::fake()`.
- **FE (Phase 1 tối thiểu):** smoke render trang Orders/Channels; (Vitest đầy đủ — sau).

## 10. Tiêu chí hoàn thành
- [x] Migrations + models Channels & Orders; OAuth connect/disconnect; webhook receiver + verify + `ProcessWebhookEvent`; polling `SyncOrdersForShop` + `BackfillOrders`; `OrderUpsertService` idempotent; `TikTokStatusMap` + `order_status_history`; auto refresh token + cảnh báo re-connect.
- [x] `TikTokConnector` + `TikTokClient` (ký HMAC, version 202309) + `TikTokWebhookVerifier` + `TikTokMappers`; đăng ký `ChannelRegistry::register('tiktok', ...)` + bật trong `config/integrations.php`; route `/webhook/tiktok` + `/oauth/tiktok/callback` qua controller chung.
- [x] API `GET/POST/DELETE /channel-accounts*`, `GET /orders*`, `POST /orders/{id}/tags`, `PATCH /orders/{id}/note`, `GET /orders/stats`, `GET /dashboard/summary`.
- [x] FE: màn Đơn hàng (list/filter/status tabs/detail/tag/note), màn Gian hàng (connect/resync/disconnect + callback success), màn **Nhật ký đồng bộ** (2 tab `sync_runs` / `webhook_events`, lọc theo gian hàng, nút chạy lại), Dashboard cơ bản — phong cách BigSeller.
- [x] API + UI **Nhật ký đồng bộ**: `GET /sync-runs`, `GET /webhook-events` (payload thô không lộ ra — §8), `POST /sync-runs/{id}/redrive` (re-dispatch `SyncOrdersForShop`), `POST /webhook-events/{id}/redrive` (reset `pending` + re-dispatch `ProcessWebhookEvent`); xem = `channels.view`, re-drive = `channels.manage`.
- [x] Test: contract TikTok (fixtures), feature webhook→upsert (idempotent) + polling→upsert + refresh token + orders API + tenant isolation + connect flow + nhật ký đồng bộ (list scoped + re-drive + phân quyền).
- [x] Tài liệu cập nhật: `04-channels/tiktok-shop.md` (version `202309` đang dùng + thuật toán ký đã implement), `05-api/endpoints.md`, `07-infra/queues-and-scheduler.md`, `roadmap.md` (tiến độ Phase 1), spec này (Implemented).
- [ ] **Còn lại để "Exit criteria Phase 1" đầy đủ:** kết nối shop TikTok sandbox **thật** và xác nhận đơn mới tự về (cần app key/secret sandbox thật trong `.env`); rà soát mapping webhook event-type + status với tài liệu Partner API thật; rate-limit Redis throttle hoàn chỉnh per shop.

## 11. Câu hỏi mở
- Phiên bản API TikTok khoá ở `202309` — khi nào nâng? (theo dõi changelog Partner Center; nâng có test).
- Webhook event-type của TikTok (số `type` ↦ ý nghĩa): danh sách trong `config/integrations.php → tiktok.webhook_event_types` — cần đối chiếu với tài liệu thật (hiện điền theo hiểu biết + `TYPE_UNKNOWN` cho phần chưa chắc).
- Sandbox: base URL & cơ chế test seller/token — xác nhận với `sdk_tiktok_seller/README.md` và Partner Center; cấu hình qua `integrations.tiktok.base_url` / `auth_base_url` / `sandbox`.
