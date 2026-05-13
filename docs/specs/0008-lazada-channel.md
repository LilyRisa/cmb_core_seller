# SPEC 0008: Lazada — connector (auth + đồng bộ đơn + listings + đẩy tồn + webhook + arrange shipment)

- **Trạng thái:** Implemented (2026-05-17 lõi Phase 4; **2026-05-13** — bổ sung arrange shipment "luồng A": pack → RTS → AWB; đối soát = SPEC 0016 đã có)
- **Phase:** 4 *(slot sàn thứ 2 — Lazada sandbox đã được duyệt; Shopee đến sau)*
- **Module backend liên quan:** Integrations (Channels), Channels, Orders, Inventory, Products
- **Tác giả / Ngày:** Team · 2026-05-17
- **Liên quan:** `docs/04-channels/lazada.md` (chi tiết kỹ thuật), `docs/04-channels/README.md` (golden rule connector), SPEC-0001 (TikTok — luồng đồng bộ đơn tái dùng nguyên), SPEC-0003 (ghép SKU / đẩy tồn — tái dùng), `docs/03-domain/order-status-state-machine.md`.

## 1. Vấn đề & mục tiêu
Phase 4 = "slot 2 sàn vào sau khi có API". **Lazada sandbox đã được duyệt** ⇒ làm Lazada trước. Tất cả hạ tầng đồng bộ đơn (OAuth connect/disconnect, `oauth_states`, `channel_accounts`, `SyncOrdersForShop` polling + backfill, `RefreshChannelToken`, `ProcessWebhookEvent`, webhook route `/webhook/lazada`, OAuth callback `/oauth/lazada/callback`, `OrderUpsertService`, listener đẩy tồn / link khách) **đã có sẵn từ Phase 1–3 và là provider-agnostic**. SPEC này chỉ thêm **một `LazadaConnector`** implement `ChannelConnector` + các lớp Lazada-specific + config — không sửa core (golden rule).

Mục tiêu: kết nối shop Lazada (sandbox/thật) ⇒ đơn mới tự về (webhook/polling), đổi trạng thái map sang chuẩn, listings kéo về & auto-match SKU, thay đổi tồn tự đẩy lên Lazada — chạy chung một luồng với TikTok + đơn tay.

## 2. Trong / ngoài phạm vi
**Trong:**
- `app/Integrations/Channels/Lazada/`: `LazadaConnector` (implements `ChannelConnector`), `LazadaClient` (HTTP + ký HMAC-SHA256 sha256), `LazadaSigner`, `LazadaStatusMap` (+ `collapse()` cho item-level), `LazadaMappers` (raw→DTO), `LazadaWebhookVerifier`, `LazadaApiException`.
- Khả năng (capabilities): `orders.fetch`, `orders.webhook`, `listings.fetch`, `listings.updateStock` = true; `shipping.arrange`, `shipping.document` = true (mặc định, gated `INTEGRATIONS_LAZADA_FULFILLMENT=true`); `finance.settlements` = gated; `returns.fetch`, `listings.publish/updatePrice` = false (Phase sau — core ẩn các tính năng đó, không branch theo tên).
- **Arrange shipment "luồng A"** (2026-05-13, mode `LAZADA_FULFILLMENT_MODE=auto`): `arrangeShipment` tự re-fetch → idempotent short-circuit nếu item đã packed → resolve `shipment_provider` (`/shipment/providers/get`, cache trong-process) → `POST /order/pack` (`delivery_type=dropship`, `order_items=[...]`) → `POST /order/rts` (`tracking_number` từ pack, `order_item_ids=[...]`) → trả tracking. Mode `refetch_only` (legacy) chỉ re-fetch — cho shop chưa có permission Fulfillment.
- Config: block `config/integrations.lazada` (hosts, endpoint paths `order_pack`/`order_rts`/`shipment_providers`/`document_get`/`update_stock`/`transaction_details`, `status_map`, `webhook_message_types`, `default_delivery_type=dropship`, `default_shipment_provider` optional, `fulfillment_mode`); `INTEGRATIONS_CHANNELS` thêm `lazada`; `.env.example` thêm `LAZADA_*`; `throttle.lazada` (đã có).
- Đăng ký `LazadaConnector` trong `IntegrationsServiceProvider::$channelConnectors`.
- Doc `docs/04-channels/lazada.md`, `docs/04-channels/order-processing.md` §3. Test contract `LazadaConnectorContractTest` cover full pack→RTS flow + short-circuit khi đã packed + mode refetch_only + provider resolution.
**Ngoài (follow-up Phase sau):**
- **Tracking realtime qua push Lazada** — hiện dùng polling + webhook re-fetch (đủ cho v1).
- **Shopee connector** — spec riêng khi bắt đầu (cấu trúc tương tự).
- **Đăng/sửa listing đa sàn** (Phase 5), **trả hàng** (Phase 7 — `/reverse/...`).
- FE: không cần code mới — trang Gian hàng (`channel-accounts`), Đơn hàng, Liên kết SKU đã generic; `CHANNEL_META.lazada` đã có. Nút "Kết nối Lazada" tự hiện khi connector được nạp (`connectable_providers` đọc từ `ChannelRegistry`).

## 3. Luồng chính (tái dùng Phase 1)
Y hệt TikTok, chỉ khác provider code:
1. **Connect:** SPA → `POST /api/v1/channel-accounts/lazada/connect` → `auth_url` (Lazada `/oauth/authorize?response_type=code&client_id=&redirect_uri=&state=`) → seller đồng ý → `GET /oauth/lazada/callback?code=&state=` → `LazadaConnector::exchangeCodeForToken` (`/auth/token/create`) → `fetchShopInfo` (`/seller/get`) → tạo `channel_accounts(provider='lazada', external_shop_id=short_code, ...)` + token + (Lazada không có subscribe-webhook API ⇒ `registerWebhooks` no-op, đăng ký ở console) → backfill 90 ngày → redirect vào SPA.
2. **Đồng bộ đơn:** `SyncOrdersForShop` (poll ~10' + backfill) → `LazadaConnector::fetchOrders` (`/orders/get` theo `update_after`, offset paging; gộp items qua `/orders/items/get?order_ids=[...]`) → `OrderUpsertService` upsert → listener Inventory (reserve/ship theo trạng thái) + Customers (link theo SĐT). Webhook `/webhook/lazada` → verify chữ ký → `ProcessWebhookEvent` → `fetchOrderDetail` (`/order/get` + `/order/items/get`) → upsert; `orderRawStatus` từ push = fast-path cập nhật trạng thái nếu re-fetch tạm hỏng.
3. **Listings & tồn:** `POST /channel-accounts/{id}/resync-listings` / scheduled → `FetchChannelListings` → `LazadaConnector::fetchListings` (`/products/get`, offset paging, flatten SKU) → upsert `channel_listings` `(channel_account_id, external_sku_id=ShopSku)` → auto-match SKU. `InventoryChanged` → `PushStockForSku` → `PushStockToListing` → `LazadaConnector::updateStock` (`/product/price_quantity/update`, `$context` mang `seller_sku` + `external_product_id`).
4. **Refresh token:** `channels:refresh-expiring-tokens` (mỗi 30') → `LazadaConnector::refreshToken` (`/auth/token/refresh`). Fail ⇒ `channel_account.status=expired` + event reconnect (đã có).

## 4. Hành vi & quy tắc đặc thù Lazada
- **Ký:** `LazadaSigner::sign` = `strtoupper(HMAC_SHA256(app_secret, apiPath + sorted("{k}{v}"...)))`, sign_method=`sha256` (KHÔNG kèm body — business params đã ở trong query/form). Khác TikTok (TikTok bọc body + secret hai đầu).
- **Phân trang:** offset/limit (không cursor như TikTok). `Page.nextCursor` = offset kế tiếp dạng chuỗi; `hasMore` khi page đầy và (`count` chưa biết hoặc `offset+limit < count`).
- **Item-level status:** `order.statuses` là mảng; `LazadaStatusMap::collapse()` chọn 1 status order-level (đảo chiều chỉ thắng nếu toàn bộ item; còn lại lấy forward ít tiến nhất). `mapStatus($raw, ['statuses'=>[...]])` dùng `collapse` khi được cấp mảng.
- **Tiền:** string/float 2 thập phân → số nguyên VND đồng (`money()`).
- **Webhook:** Lazada không có API subscribe per-shop (đăng ký ở console); chữ ký = HMAC-SHA256 raw body, header tên khác nhau theo app ⇒ verifier kiểm vài header thông dụng. Secret chưa cấu hình ⇒ verify=false (an toàn).
- **`external_product_id` ở order item:** Lazada không có product id riêng cấp item ⇒ dùng `shop_sku` làm thay; `sku` = `seller_sku`.
- Không bảng/cột mới (dùng nguyên `channel_accounts`, `orders`, `order_items`, `channel_listings`, `sku_mappings`, `oauth_states`, `webhook_events`, `sync_runs`).

## 5. API & UI
Không endpoint mới — các route hiện có nhận thêm `provider='lazada'` (`POST /channel-accounts/lazada/connect`, callback `/oauth/lazada/callback`, webhook `/webhook/lazada` — tất cả đã `whereIn(['tiktok','shopee','lazada'])`). `connectable_providers` trong `GET /channel-accounts` tự liệt kê `lazada` khi connector được nạp. FE không đổi.

## 6. Triển khai
- ENV: đặt `LAZADA_APP_KEY` / `LAZADA_APP_SECRET` (sandbox), `LAZADA_SANDBOX=true`, thêm `lazada` vào `INTEGRATIONS_CHANNELS` (vd `manual,tiktok,lazada`). Đăng ký Callback URL `https://<APP_URL host>/oauth/lazada/callback` + App Push URL `https://<APP_URL host>/webhook/lazada` trong Lazada Open Platform console. Cần `APP_URL` HTTPS công khai để callback (ngrok cho dev) như TikTok.
- Không migration mới. Sau redeploy: kết nối shop Lazada sandbox → kiểm đơn mới tự về (poll/webhook), listings resync, đẩy tồn — đối chiếu `webhook_message_types` & `status_map` với dữ liệu sandbox thật rồi tinh chỉnh config (không cần đổi code).

## 7. Cách kiểm thử
- `tests/Feature/Channels/LazadaConnectorContractTest` (11 ca, `Http::fake` — không gọi mạng thật): exchange/refresh token; `fetchShopInfo`; `fetchOrders` (gộp items, tiền VND đồng, COD, status, package dedup); `fetchOrderDetail`; `mapStatus` (kể cả collapse item-level + status lạ không ném); `fetchListings` (flatten SKU, special_price, active/inactive); `updateStock` (assert payload gửi đi); webhook verify (đúng/sai/thiếu chữ ký) + parse; `arrangeShipment` ném `UnsupportedOperation`; signer deterministic & độc lập thứ tự + vector cố định; registry resolve được `lazada`.
- `IntegrationsRegistryTest` vẫn xanh (test env `.env` không bật `lazada` ⇒ `has('lazada')=false`; contract test tự `register('lazada', ...)`).
- Khi có sandbox thật: chạy thử connect + sync + đối chiếu mapping (Exit criteria Phase 4 cần cả Shopee mới "Done" — Lazada xong là một nửa).
