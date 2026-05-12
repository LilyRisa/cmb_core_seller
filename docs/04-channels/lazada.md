# Tích hợp Lazada (Việt Nam)

**Status:** Implemented (Phase 4 — lõi: auth + đồng bộ đơn + listings + đẩy tồn + webhook). Code: `app/Integrations/Channels/Lazada/`; cấu hình: `config/integrations.lazada`; spec: [`docs/specs/0008-lazada-channel.md`](../specs/0008-lazada-channel.md). RTS/AWB (in vận đơn của Lazada) + đối soát = follow-up Phase 4/6. · **Cập nhật:** 2026-05-17

> Cùng nếp với `tiktok-shop.md`: connector implement `ChannelConnector`, trả **DTO chuẩn** — core không biết tên "lazada". Mọi thứ Lazada-specific (ký, status/message map, endpoint) nằm dưới `app/Integrations/Channels/Lazada/` + `config('integrations.lazada')`.
>
> ⚠️ Một số chi tiết — danh sách `message_type` của push webhook, shape chính xác của `/product/price_quantity/update` — implement theo tài liệu Open Platform + hiểu biết hiện có; đối chiếu lại với sandbox thật. Bảng map ở `config/integrations.lazada.status_map` / `webhook_message_types` để tinh chỉnh không cần đổi code; mọi event đơn vẫn `fetchOrderDetail` lại + polling backup.

## 1. Đăng ký & môi trường
- **Lazada Open Platform** → tạo app (sandbox app riêng để dev) → lấy `app_key`, `app_secret`. Site = **VN** (`lazada.vn`).
- Gateway VN: `https://api.lazada.vn/rest` (`LAZADA_API_BASE_URL`). Auth host: `https://auth.lazada.com/rest` (`LAZADA_AUTH_BASE_URL`). Trang ủy quyền seller: `https://auth.lazada.com/oauth/authorize` (`LAZADA_AUTHORIZE_URL`).
- **Cấu hình trong app console:**
  - **Callback URL** (Authorization): chính xác `https://<APP_URL host>/oauth/lazada/callback` (vd `https://app.cmbcore.com/oauth/lazada/callback`). Lazada **có** dùng `redirect_uri` query param — phải khớp.
  - **App Push** (webhook): URL `https://<APP_URL host>/webhook/lazada`; chọn các message type cần (Trade Order status, Product, App authorization revoke...). Lazada **không** có API "subscribe webhook per shop" — đăng ký ở console.
  - **API scopes/permissions**: bật Orders, Products, Logistics (cho Phase 4 fulfillment sau), Finance (Phase 6).
- ENV (xem `.env.example`): `LAZADA_APP_KEY`, `LAZADA_APP_SECRET`, `LAZADA_SANDBOX=true`, (tuỳ chọn) `LAZADA_REDIRECT_URI` / `LAZADA_*_BASE_URL`. Bật connector: `INTEGRATIONS_CHANNELS=manual,tiktok,lazada`.

## 2. Xác thực & ký request (đã implement)
- **OAuth:** SPA gọi `POST /api/v1/channel-accounts/lazada/connect` → backend tạo `oauth_states` → trả `auth_url` = `https://auth.lazada.com/oauth/authorize?response_type=code&force_auth=true&client_id={app_key}&redirect_uri={cb}&state={state}` → seller đăng nhập & đồng ý → Lazada redirect `GET /oauth/lazada/callback?code=…&state=…` → đổi `code` lấy token tại **`POST https://auth.lazada.com/rest/auth/token/create`** (có ký) → trả `{access_token, refresh_token, expires_in (giây), refresh_expires_in (giây), account, country, country_user_info:[{country, seller_id, short_code, ...}]}`. `short_code`/`seller_id` từ `country_user_info` → `channel_account.external_shop_id`. Refresh: `POST /auth/token/refresh` với `refresh_token` (token sống ~30 ngày — `RefreshChannelToken` lo gia hạn).
- **Ký request** — `LazadaSigner` (sign_method=`sha256`):
  1. lấy mọi param request (system: `app_key`, `timestamp` (ms), `sign_method`, `access_token`; + business params), **trừ `sign`**, sắp xếp key tăng dần;
  2. nối `{key}{value}` (không phân cách) → `concatenated`;
  3. ghép **đường dẫn API** lên đầu: `apiPath + concatenated`;
  4. `HMAC-SHA256(key=app_secret, message=…)` → **hex viết HOA** = `sign`.
  (Không kèm body — Lazada gửi business params trong query/form nên chúng đã nằm trong param map.) Pure & test được bằng vector cố định.
- Mọi call qua `LazadaClient` (GET = query string; POST = `application/x-www-form-urlencoded`). Envelope `{code, type, message, request_id, data}` — `code != "0"` ⇒ `LazadaApiException` (có `lazadaCode`, `httpStatus`; `isAuthError()` / `isRateLimited()`). Throttle per-(provider, shop) qua `RateLimiter` (`THROTTLE_LAZADA_PER_MIN`). Không log token/secret.

## 3. Endpoint đang dùng
- **Đơn:** `GET /orders/get` (theo `update_after` ISO-8601, `sort_by=updated_at`, `sort_direction=ASC`, `offset`/`limit` — Lazada phân trang offset, không cursor; nếu caller không cho khoảng thời gian → mặc định 30 ngày gần nhất). Mỗi đơn ở list **không kèm line items** ⇒ sau khi lấy page, gọi 1 lần `GET /orders/items/get?order_ids=[id,...]` (≤50 id/lần) để gộp items vào. `GET /order/get?order_id=` + `GET /order/items/get?order_id=` = chi tiết đơn (`fetchOrderDetail`).
- **Listing/tồn:** `GET /products/get?filter=all&offset=&limit=` → flatten mỗi product (`item_id` + `attributes.name`) ra một `ChannelListingDTO` mỗi SKU (`ShopSku` = `external_sku_id`, `SellerSku`, `quantity`, `price`/`special_price`, `Status`, `Images`). `POST /product/price_quantity/update` (param `payload` — JSON `{Request:{Product:{Skus:{Sku:[{ItemId,SellerSku,Quantity}]}}}}` mặc định; đặt `LAZADA_UPDATE_STOCK_FORMAT=xml` nếu account cần XML) — `updateStock` nhận `$context.seller_sku` + `$context.external_product_id`.
- **Shop:** `GET /seller/get` → tên/short_code/location.
- *(Phase 4 fulfillment — chưa làm)* `/order/document/get` (AWB/invoice/packing list — trả base64 PDF), `/logistic/...` (RTS - ready to ship, package, tracking). *(Phase 6)* `/finance/payout/status/get`, `/finance/transaction/details/get` (đối soát). *(Phase 7)* `/reverse/...` (trả hàng).

## 4. Webhook (push message)
- URL `/webhook/lazada` (route đã có). `LazadaWebhookVerifier.verify()`: HMAC-SHA256(key=`app_secret`, message=raw body), hex — Lazada gửi chữ ký ở header (tên thay đổi theo app: kiểm `X-Lazop-Sign` / `X-Lzd-Sign` / `X-Signature` / `Authorization`). Sai chữ ký / thiếu header / chưa cấu hình secret ⇒ `verify=false` ⇒ `WebhookController` trả `401`, không ghi gì.
- `parse()`: body `{ message_type:int, timestamp:ms, site, data:{ trade_order_id, trade_order_line_id, order_item_status, seller_id, ... } }`. `message_type` → `WebhookEventDTO.type` qua `config('integrations.lazada.webhook_message_types')` (unknown nhưng có `trade_order_id` ⇒ coi như `order_status_update`). `order_item_status` → `WebhookEventDTO.orderRawStatus` (cập nhật đơn đã có ngay cả khi re-fetch detail tạm hỏng — fast-path như TikTok). Đơn → `ProcessWebhookEvent` re-fetch `fetchOrderDetail` rồi upsert; polling là lưới an toàn.

## 5. Mapping trạng thái
Lazada là **item-level**: `order.statuses` là mảng (một status / order-item, đã dedup). `LazadaStatusMap::collapse()` gộp lại thành 1 status order-level: status đảo chiều (`canceled`/`returned`/`shipped_back*`) chỉ "thắng" nếu **mọi** item ở trạng thái đó; ngược lại lấy status forward **ít tiến nhất** (đơn còn 1 item `pending` ⇒ `pending`). Rồi `toStandard()` qua `config('integrations.lazada.status_map')` (key chuẩn hoá lowercase + `_`):
`unpaid→unpaid · pending→pending · topack→processing · packed/ready_to_ship→ready_to_ship · shipped→shipped · delivered→delivered · failed/lost/damaged→delivery_failed · shipped_back*→returning · returned→returned_refunded · canceled→cancelled`. Status lạ ⇒ fallback bảo thủ (chứa "cancel"→cancelled, "return/refund"→returning, ...), không bao giờ ném lỗi. (Đồng bộ với `docs/03-domain/order-status-state-machine.md` §4.)

## 6. Tiền & địa chỉ
- Tiền Lazada là string/float 2 chữ số thập phân ("220000.00") — VND không có subunit ⇒ `LazadaMappers::money()` → số nguyên đồng. `price` = tổng đơn; `shipping_fee`, `voucher`/`voucher_seller`/`voucher_platform` (nếu không có breakdown thì coi cả `voucher` là của seller); `item_total` = tổng `item_price` của các item (fallback = `price - shipping + voucher`). COD = `payment_method` chứa "COD"/"cash on delivery".
- Địa chỉ: `address_shipping.{first_name,last_name,phone,address1,address2,address3(ward),address4(district),city(province),country,post_code}` → DTO `shippingAddress`. `buyer_note`/`remarks` → `shippingAddress.note`.

## 7. Lưu ý / còn lại
- `external_product_id` của order item: Lazada không có product id riêng ở cấp item — dùng `shop_sku` làm thay (đủ để re-resolve listing); `sku` = `seller_sku`.
- Đơn nhiều item-status khác nhau ⇒ status order-level là quyết định của `collapse()`; `packages[]` được suy từ item (dedup theo `package_id`/`tracking_code`).
- **Chưa làm (follow-up Phase 4):** RTS/đóng gói Lazada (`/logistic/...`), in AWB/invoice/packing list của Lazada (`/order/document/get` → base64 PDF → lưu kho media), tracking realtime qua push. **Phase 5:** đăng/sửa listing đa sàn (`/products/category/tree/get`, `/category/attributes/get`, `/product/create|update`). **Phase 6:** đối soát (`/finance/...`). **Phase 7:** trả hàng (`/reverse/...`).
- Khi làm Shopee: tạo `app/Integrations/Channels/Shopee/` + một dòng trong `IntegrationsServiceProvider` + block `config/integrations.shopee` — không sửa core (golden rule).
