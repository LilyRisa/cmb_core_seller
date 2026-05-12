# Tích hợp TikTok Shop (Việt Nam)

**Status:** Implemented (Phase 1) — version API đang dùng: **`202309`** (order/authorization/event). Code: `app/Integrations/Channels/TikTok/`; cấu hình: `config/integrations.tiktok`; spec: [`docs/specs/0001-tiktok-order-sync.md`](../specs/0001-tiktok-order-sync.md). · **Cập nhật:** 2026-05-12

> Đây là sàn **làm trước**. Trong repo có `sdk_tiktok_seller/` = SDK TikTok Shop Partner API (TypeScript, sinh bằng openapi-generator, nhiều version v202309 → v202601). **Dùng để tham khảo endpoint/schema/version** — backend là PHP nên ta viết HTTP client PHP riêng (đã làm: `TikTokClient`).
>
> ⚠️ Một số chi tiết — **danh sách `type` của webhook** và toàn bộ chuỗi `order status` — được implement theo schema trong `sdk_tiktok_seller/` + hiểu biết hiện có; **đối chiếu lại với tài liệu Partner Center thật** (`partner.tiktokshop.com/docv2`) khi chạy sandbox thật. Bảng map ở `config/integrations.tiktok.status_map` / `webhook_event_types` để tinh chỉnh không cần đổi code; mọi event đơn vẫn `fetchOrderDetail` lại + polling backup nên sai event-type không gây mất đơn.

## 1. Đăng ký & môi trường
- Tạo app trên **TikTok Shop Partner Center**, lấy `app_key`, `app_secret`, `service_id`. Tạo test seller + test access token để dev (xem `sdk_tiktok_seller/README.md`).
- Region: **VN**. Lưu ý TikTok có nhiều region; ta chỉ làm VN nhưng `AuthContext.region` vẫn mang giá trị để chừa đường.
- **Cấu hình Partner Center cho app prod:**
  - **Redirect URL** (Authorization Settings): `https://app.cmbcore.com/oauth/tiktok/callback` (chính xác — TikTok luồng "service" redirect về URL đăng ký, **không** dùng `redirect_uri` query param).
  - **Webhook URL**: `https://app.cmbcore.com/webhook/tiktok`.
  - **Scopes** (BẮT BUỘC bật, nếu không seller authorize xong sẽ lỗi `105005 Access denied`):
    - **`Authorization`** / **`Shop`** — để gọi `/authorization/202309/shops` lấy danh sách gian hàng + `shop_cipher` ngay sau khi ủy quyền. Thiếu cái này = không hoàn tất kết nối.
    - **`Order`** — cho `/order/202309/orders/search` & `/orders` (poll + chi tiết đơn).
    - **`Webhook`** / **`Event`** — cho `/event/202309/webhooks` (đăng ký webhook).
    - **`Product`** / **`Fulfillment`** / **`Finance`** — khi sang Phase 2/3/6.
  - Sau khi thay đổi scopes, người bán phải **ủy quyền lại** (disconnect ở app, rồi connect lại) để token mới có scope mới.

## 2. Xác thực & ký request (đã implement)
- **OAuth (seller authorization):** SPA gọi `POST /api/v1/channel-accounts/tiktok/connect` → backend tạo `oauth_states(state,…)` → trả `auth_url` = `https://services.tiktokshop.com/open/authorize?service_id=…&state=…&redirect_uri=…` (config `integrations.tiktok.authorize_url` / `service_id`) → user đồng ý → TikTok redirect `GET /oauth/tiktok/callback?app_key=…&code=…&state=…` → đổi `code` lấy token tại **`GET https://auth.tiktok-shops.com/api/v2/token/get?app_key=&app_secret=&auth_code=&grant_type=authorized_code`** (host auth, **không ký** — app_key+app_secret là query). Trả `{code,message,data:{access_token, access_token_expire_in (Unix ts hết hạn), refresh_token, refresh_token_expire_in, open_id, seller_name, seller_base_region, user_type}}`. Sau đó `GET /authorization/202309/shops` (Open API, **có ký**, không cần `shop_cipher`) lấy `data.shops[].cipher` → lưu vào `channel_account.meta.shop_cipher`.
- **Ký request Open API (`open-api.tiktokglobalshop.com`)** — implement trong `TikTokSigner` (khớp `sdk_tiktok_seller/utils/generate-sign.ts`):
  1. lấy mọi query param **trừ `sign` và `access_token`**, sắp xếp key tăng dần;
  2. nối `{key}{value}` (không dấu phân cách) → `paramString`;
  3. ghép **đường dẫn API** lên đầu: `signString = path + paramString`;
  4. nếu `Content-Type` ≠ `multipart/form-data` và body khác rỗng: nối thêm chuỗi JSON body;
  5. bọc hai đầu bằng `app_secret`: `secret + signString + secret`;
  6. `HMAC-SHA256(key=app_secret, message=…)` → hex thường = `sign` (đặt vào query).
  Query param chuẩn: `app_key`, `timestamp` (Unix giây), `sign` (+ `shop_cipher` với API theo shop). Header: `Content-Type: application/json`, `x-tts-access-token: <access_token>`. Base URL & version đọc từ config (`integrations.tiktok.base_url`, `…version.*`) ⇒ **sandbox vs prod chỉ là cấu hình**.
- **Refresh token:** `GET https://auth.tiktok-shops.com/api/v2/token/refresh?app_key=&app_secret=&refresh_token=&grant_type=refresh_token` (không ký). Lệnh scheduled `channels:refresh-expiring-tokens` (mỗi 30') dispatch `RefreshChannelToken` cho account có token hết hạn trong ≤ 24h; refresh fail ⇒ `channel_account.status=expired` + event `ChannelAccountNeedsReconnect`. `TikTokClient`/job cũng tự thử refresh 1 lần khi gặp lỗi auth giữa chừng.
- **Webhook signature:** header `Authorization` = `HMAC-SHA256(key=app_secret, message = app_key + rawBody)`, hex thường (`TikTokWebhookVerifier`).
- **Versioned:** version các nhóm endpoint khoá ở `config/integrations.tiktok.version` (hiện đều `202309`). Nâng version = đổi config (+ test); SDK có sẵn v202309→v202601 để tham khảo schema mới.

## 3. Endpoint dự kiến dùng (đối chiếu SDK & docs)
| Mục đích | Nhóm API (theo SDK) | DTO chuẩn |
|---|---|---|
| Lấy danh sách đơn (theo update_time, phân trang) | `order/search` (orderVxxxxApi) | `Page<OrderDTO>` (rút gọn) |
| Lấy chi tiết đơn | `order/detail` / `orders` (orderVxxxxApi) | `OrderDTO` |
| Lấy thông tin shop | `authorization` / `seller` (authorizationVxxxx / sellerVxxxx) | `ShopInfoDTO` |
| Listing/sản phẩm | `product/search`, `product/detail` (productVxxxxApi) | `Page<ListingDTO>` |
| Danh mục & thuộc tính | `product/categories`, `product/attributes` | (mass listing — Phase 5) |
| Cập nhật tồn | `product/{version}/products/{product_id}/inventory/update` (productVxxxxApi) — **đã implement Phase 2** trong `TikTokConnector::updateStock` (signed POST, body `{skus:[{id,inventory:[{warehouse_id?,quantity}]}]}`; path config-able qua `integrations.tiktok.endpoints.update_inventory`). ⚠️ **Shape/endpoint cần xác nhận với Partner API/sandbox thật.** Capability `listings.updateStock=true`. | — |
| Cập nhật giá | `product/price/update` | — |
| Sắp xếp vận chuyển / package | `fulfillment` (fulfillmentVxxxxApi) — get package, ship package, get shipping document | `ShipmentDTO`, `BinaryFile` (label) |
| Logistics (ĐVVC sàn gán, tracking) | `logistics` (logisticsVxxxxApi) | `TrackingDTO` |
| Đối soát / settlement / statement | `finance` (financeVxxxxApi) | `SettlementLineDTO[]` |
| Trả hàng / hoàn tiền | `return` (returnVxxxxApi nếu có) / order cancellations | `ReturnDTO` |
| Webhook | `event` (eventVxxxxApi) — đăng ký, danh sách event | — |
| Đối soát dữ liệu (reconciliation) | `dataReconciliation` (dataReconciliationVxxxxApi) | (đối chiếu đơn — dùng cho job kiểm tra) |

## 4. Webhook
- URL: `/webhook/tiktok`. Verify chữ ký theo quy tắc TikTok (header `Authorization`/`sign` + body + `app_secret`) — implement `TikTokWebhookVerifier`.
- Event quan tâm (map sang `WebhookEventDTO.type`): order status update / order create / package update → `order_status_update`/`order_created`; cancellation / return → `return_update`; settlement available → `settlement_available`; product status → `product_update`; **authorization revoked / shop deauthorized** → `shop_deauthorized`; **data deletion request** → `data_deletion` (xem `08-security-and-privacy.md`).
- Webhook chỉ dùng làm tín hiệu — luôn `fetchOrderDetail` để lấy dữ liệu chuẩn. Polling mỗi ~10' làm backup.

## 5. Mapping trạng thái
Xem bảng trong `03-domain/order-status-state-machine.md` §4. Toàn bộ chuỗi raw_status của TikTok nằm trong `TikTokStatusMap` (config) — **nơi duy nhất**. Khi code, đối chiếu danh sách `order status` thật trong docs/SDK và cập nhật bảng đó + mục §4 kia.

## 6. Lưu ý riêng
- `shop_cipher` / `shop_id`: API theo shop cần `shop_cipher` (lấy khi authorize) — lưu trong `channel_account.meta`.
- Rate limit: tôn trọng giới hạn của TikTok, throttle per shop bằng Redis limiter; xử lý 429/`Retry-After`.
- Số tiền TikTok trả thường là chuỗi có đơn vị tiền tệ — parse cẩn thận về `bigint` đồng (VND không có phần thập phân).
- Đơn nhiều package: một `order` ↔ nhiều `shipments`; label theo từng package.
- Version drift: TikTok đổi version liên tục (thư mục SDK có v202309→v202601) — khoá version đang dùng, theo dõi changelog, nâng cấp có test.

## 7. Việc cụ thể Phase 1 (checklist)
- [x] `TikTokConnector` implement `ChannelConnector` (orders + auth + webhook; `updateStock` ném `UnsupportedOperation` — `capability listings.updateStock=false`; listing/finance/fulfillment khai `false` trong capability map cho Phase sau).
- [x] `TikTokClient` (HTTP + ký HMAC + timestamp + version + throttle per shop + auto-refresh khi lỗi auth), `TikTokSigner`, `TikTokWebhookVerifier`, `TikTokStatusMap`, `TikTokMappers` (raw → DTO), `TikTokApiException`.
- [x] Đăng ký `ChannelRegistry::register('tiktok', TikTokConnector::class)` (`IntegrationsServiceProvider`) + bật trong `config/integrations.php` (`INTEGRATIONS_CHANNELS=manual,tiktok`) + block `integrations.tiktok`.
- [x] Route `/webhook/tiktok` (→ `WebhookController@handle` → `WebhookIngestService` → `ProcessWebhookEvent`) + `/oauth/tiktok/callback` (→ `OAuthCallbackController` → `ChannelConnectionService`).
- [x] Contract test với fixtures (`tests/Fixtures/Channels/tiktok/TikTokFixtures.php`, `Http::fake`): `tests/Feature/Channels/TikTokConnectorContractTest.php` + feature test `TikTokSyncTest`/`ChannelConnectFlowTest` + unit `TikTokSignerTest`.
- [x] Version API đang dùng = **`202309`** (ghi ở đầu file & trong `config/integrations.tiktok.version`).
- [ ] **Còn lại:** kết nối shop TikTok **sandbox thật** + xác nhận đơn tự về (cần `APP_URL` HTTPS công khai cho callback — ngrok cho dev); rà soát `status_map` + `webhook_event_types` với tài liệu Partner Center thật; `registerWebhooks` (đăng ký event qua `POST /event/202309/webhooks`) — hiện best-effort, nhiều app cấu hình event trong Partner Center thay vì gọi API; UI "Nhật ký đồng bộ" để re-drive `webhook_events`.
