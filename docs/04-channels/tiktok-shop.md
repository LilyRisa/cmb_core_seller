# Tích hợp TikTok Shop (Việt Nam)

**Status:** Draft — sẽ hoàn thiện khi code Phase 1, đối chiếu tài liệu Partner API thật · **Cập nhật:** 2026-05-11

> Đây là sàn **làm trước**. Trong repo có `sdk_tiktok_seller/` = SDK TikTok Shop Partner API (TypeScript, sinh bằng openapi-generator, nhiều version v202309 → v202601). **Dùng để tham khảo endpoint/schema/version** — backend là PHP nên ta viết HTTP client PHP riêng (hoặc, nếu cần, dựng Node sidecar wrap SDK; mặc định: client PHP).

## 1. Đăng ký & môi trường
- Tạo app trên **TikTok Shop Partner Center**, lấy `app_key`, `app_secret`. Tạo test seller + test access token để dev (xem `sdk_tiktok_seller/README.md`).
- Region: **VN**. Lưu ý TikTok có nhiều region; ta chỉ làm VN nhưng `AuthContext.region` vẫn mang giá trị để chừa đường.
- Cấu hình app: scopes cần (orders, products/listings, fulfillment/logistics, finance, webhooks), webhook URL = `https://<domain>/webhook/tiktok`.

## 2. Xác thực & ký request
- OAuth: user vào authorization URL → đồng ý → redirect về `/oauth/tiktok/callback?code=...&state=...` → đổi `code` lấy `access_token` + `refresh_token` (kèm `access_token_expire_in`, `refresh_token_expire_in`).
- Mọi API call ký bằng **HMAC-SHA256** với `app_secret`: chuỗi ký gồm path + các query param sắp xếp + body + `app_key` + `timestamp` (theo đúng quy tắc trong tài liệu Partner API — đối chiếu kỹ khi code). Header/param chuẩn: `app_key`, `timestamp`, `sign`, `access_token`, `shop_cipher` (với API theo shop), `Content-Type`.
- Token sắp hết hạn ⇒ job `RefreshChannelToken` gọi refresh trước hạn; refresh fail ⇒ `channel_account.status=expired`.
- Client **versioned**: thư mục `app/Integrations/Channels/TikTok/Vxxxxxx/` theo version endpoint; adapter map → DTO chuẩn. Chọn version "ổn định mới nhất" tại thời điểm code; ghi rõ version đang dùng ở đây.

## 3. Endpoint dự kiến dùng (đối chiếu SDK & docs)
| Mục đích | Nhóm API (theo SDK) | DTO chuẩn |
|---|---|---|
| Lấy danh sách đơn (theo update_time, phân trang) | `order/search` (orderVxxxxApi) | `Page<OrderDTO>` (rút gọn) |
| Lấy chi tiết đơn | `order/detail` / `orders` (orderVxxxxApi) | `OrderDTO` |
| Lấy thông tin shop | `authorization` / `seller` (authorizationVxxxx / sellerVxxxx) | `ShopInfoDTO` |
| Listing/sản phẩm | `product/search`, `product/detail` (productVxxxxApi) | `Page<ListingDTO>` |
| Danh mục & thuộc tính | `product/categories`, `product/attributes` | (mass listing — Phase 5) |
| Cập nhật tồn | `product/stock/update` (productVxxxxApi) | — |
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
- [ ] `TikTokConnector` implement `ChannelConnector` (phần orders + auth + webhook + updateStock; phần listing/finance/fulfillment để Phase sau nhưng khai trong capability map).
- [ ] `TikTokClient` (HTTP + ký HMAC + timestamp + version), `TikTokWebhookVerifier`, `TikTokStatusMap`, `TikTokMappers` (raw → DTO).
- [ ] Đăng ký `ChannelRegistry::register('tiktok', TikTokConnector::class)` + bật trong `config/integrations.php`.
- [ ] Route `/webhook/tiktok` + `/oauth/tiktok/callback` qua controller chung.
- [ ] Contract test với fixtures lấy từ SDK/docs.
- [ ] Ghi version API đang dùng vào file này.
