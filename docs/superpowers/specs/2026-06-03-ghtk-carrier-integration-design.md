# Tích hợp ĐVVC Giao Hàng Tiết Kiệm (GHTK) + gợi ý phí ship lúc tạo đơn

**Status:** Approved (brainstorm) · **Ngày:** 2026-06-03 · **Tác giả:** lilyrisa + Claude

## 1. Mục tiêu

Thêm connector **GHTK** vào tầng Integration (Carriers) theo đúng pattern Connector + Registry mà GHN
đã thiết lập, để đơn (thủ công và đơn sàn đóng gói qua GHTK) tạo được vận đơn, lấy tem, tra trạng thái,
huỷ, nhận webhook. Bổ sung **gợi ý phí ship GHTK** ở màn tạo đơn (dùng API tính phí của GHTK), triển khai
**cô lập** — không can thiệp luồng COD, GHN, hay connector khác.

Quyết định phạm vi (đã chốt khi brainstorm):
- Capabilities: **mirror GHN + thêm `quote()`** (GHTK có API tính phí, GHN không có).
- Môi trường: **base_url cấu hình được** (mặc định prod), không thêm toggle sandbox riêng.
- Gợi ý phí: **nút bấm "Gợi ý phí GHTK"** (không auto), **hiện gợi ý + nút "Áp dụng"** (không tự đè ô user nhập).

## 2. Non-goals

- Không tự động hoá pickup scheduling nâng cao, đối soát công nợ GHTK, hay đa kho phức tạp.
- Không đổi core/Fulfillment ngoài việc thêm route webhook + endpoint quote carrier-agnostic.
- Không sửa connector GHN hay logic COD vừa hoàn thành.
- Không xử lý retroactive cho đơn cũ.

## 3. Kiến trúc

Namespace `CMBcoreSeller\Integrations\Carriers\Ghtk` (file tại `app/app/Integrations/Carriers/Ghtk/`):

| File | Vai trò |
|---|---|
| `GhtkConnector.php` | `extends AbstractCarrierConnector`; `code()='ghtk'`, `displayName()='Giao Hàng Tiết Kiệm (GHTK)'` |
| `GhtkClient.php` | HTTP client mỏng: `createOrder`, `fee`, `track`, `cancel`, `label`, `listPickAddr` |
| `GhtkStatusMap.php` | `status_id` (int) → trạng thái shipment chuẩn |

**KHÔNG có resolver địa chỉ** — GHTK nhận TÊN tỉnh/huyện/xã trực tiếp (khác GHN cần ID/Code).

**Đăng ký registry:** thêm 1 dòng vào `IntegrationsServiceProvider::$carrierConnectors`:
`'ghtk' => GhtkConnector::class`. (Cơ chế: env trống ⇒ load tất cả carrier, nên GHTK tự bật.)

## 4. Cấu hình (giống chuỗi precedence GHN)

- `config/fulfillment.php`: `'ghtk_base_url' => env('GHTK_BASE_URL', 'https://services.giaohangtietkiem.vn')`.
- `SystemSettingsCatalog`: khoá `carriers.ghtk.base_url` (group `fulfillment`, env `GHTK_BASE_URL`) — admin đè ở `/admin/settings`.
- `GhtkClient` resolve base URL: `system_setting('carriers.ghtk.base_url', config('fulfillment.ghtk_base_url'))`.
- `.env.example`: thêm `GHTK_BASE_URL=https://services.giaohangtietkiem.vn`. Staging = `https://services-staging.ghtklab.com`.

**Credentials per-tenant** (`CarrierAccount.credentials`, đã mã hoá): `{ token, client_source, pick_address_id? }`.
- `token` → header `Token`; `client_source` → header `X-Client-Source`.
- `meta.from_address` (đã có) → map sang `pick_name/pick_tel/pick_address/pick_province/pick_district/pick_ward` theo TÊN.

## 5. Endpoint GHTK (đã xác minh từ docs)

| Hành động | Method + path | Ghi chú |
|---|---|---|
| Tạo đơn | `POST /services/shipment/order/?ver=1.5` | body JSON `{products[], order{}}` |
| Tính phí | `GET /services/shipment/fee` | query params (xem §8) |
| Tra trạng thái | `GET /services/shipment/v2/{label_or_partner_id}` | trả `status` (số) + `status_text` |
| Huỷ | `GET /services/shipment/cancel/{label_or_partner_id}` | chỉ status 1/2/12 |
| In tem | `GET /services/label/{label}?page_size=A6` | trả **PDF nhị phân** trực tiếp |
| Pick addresses | `GET /services/shipment/list_pick_add` | dùng cho `verifyCredentials` |

Headers chung: `Token`, `X-Client-Source`, `Content-Type: application/json`. Response chuẩn:
`{ success: bool, message: string, ... }`; lỗi ⇒ throw `RuntimeException` message tiếng Việt.

## 6. Map dữ liệu tạo đơn (DTO chuẩn → payload GHTK)

`products[]` ← order items: `{ name, weight (KG, double), quantity, product_code, price }` (đổi gram→kg).

`order{}`:
- `id` = order_number/client_order_code
- `pick_name/pick_tel/pick_address/pick_province/pick_district/pick_ward` ← sender meta (TÊN)
- `name/tel/address/province/district/ward/hamlet` ← recipient (TÊN)
- `pick_money` = cod_amount (tiền thu hộ); `value` = insurance_value; `is_freeship` = 0
- `transport` = 'road' (mặc định); `weight_option` = 'gram'; `total_weight`; `note` (≤120 ký tự)

Kết quả trả `{ tracking_no: order.label, carrier: 'ghtk', status: 'created', fee: (int) order.fee, raw: order }`.

## 7. Capabilities (chi tiết)

`capabilities()` = `['createShipment','getLabel','getTracking','cancel','quote','awaiting_pickup_flow','webhook']`.

- `validateShipmentPayload()` — fail-fast (message VN) nếu thiếu: recipient name/phone/address/province/district; sender name/phone/address/province/district.
- `createShipment()` — build payload §6 → `GhtkClient::createOrder` → return tracking.
- `quote()` — `GhtkClient::fee(...)` → return `{ fee, insurance_fee, carrier:'ghtk' }`; lỗi ⇒ throw (FE nuốt lỗi).
- `getLabel()` — `GhtkClient::label(label)` → **PDF bytes** (không qua Gotenberg).
- `getTracking()` — `GhtkClient::track(id)` → map `status` qua `GhtkStatusMap`.
- `cancel()` — `GhtkClient::cancel(id)`.
- `parseWebhook()` — đọc `{ partner_id, label_id, status_id, action_time, reason, weight, fee, pick_money }`; chuẩn hoá + map status.
- `verifyCredentials()` — gọi `listPickAddr` (nhẹ); trả `['ok'=>bool,'message','error_code','expires_at'=>null]`.

## 8. Tính phí (params API fee)

`GET /services/shipment/fee` query: `pick_province, pick_district, pick_ward?, pick_address?, pick_address_id?,
province, district, ward?, address?, weight (GRAM), value?, transport?`. Response: `fee.fee`, `fee.insurance_fee`,
`fee.name` (area1/2/3). **Lưu ý đơn vị: fee dùng GRAM, còn products tạo đơn dùng KG.**

## 9. Bảng map trạng thái (`GhtkStatusMap`: status_id → chuẩn)

| status_id | Ý nghĩa GHTK | Trạng thái chuẩn |
|---|---|---|
| -1 | Hủy đơn | `cancelled` |
| 1, 2 | Chưa/đã tiếp nhận | `awaiting_pickup` |
| 12, 123 | Đang lấy hàng / shipper báo lấy | `awaiting_pickup` |
| 3 | Đã lấy/nhập kho | `picked_up` |
| 4, 45 | Đang giao / shipper báo giao | `in_transit` |
| 5, 6 | Đã giao / đã đối soát | `delivered` |
| 7, 8, 9, 49, 127, 128, 10 | Không lấy/giao được, hoãn, delay | `failed` |
| 13, 20, 21, 11 | Bồi hoàn / đang trả / đã trả | `returned` |

status_id lạ ⇒ giữ `raw_status`, không đổi trạng thái (an toàn). *Sẽ đối chiếu lại với docs khi code.*

## 10. Webhook

- Route mới `POST /webhook/carriers/ghtk` (trong `routes/webhook.php`, không CSRF/auth — như ghn).
- `CarrierWebhookController` thêm nhánh `ghtk`: GHTK gửi `partner_id`+`label_id`+`status_id`. Match shipment theo
  `tracking_no == label_id` (carrier `ghtk` hoặc `manual_ghtk`) → suy ra tenant. Idempotent qua
  `ShipmentEvent(shipment_id, code=status_id, occurred_at=action_time)`. Cập nhật status qua `GhtkStatusMap` +
  `OrderStatusSync`. Luôn trả **200** (tránh GHTK retry loop).
- **Bảo mật (hạn chế đã biết):** webhook GHTK không ký mạnh như GHN. v1: nếu request có header `X-Client-Source`
  thì verify khớp `client_source` của account; nếu không có ⇒ chấp nhận theo label_id + **log cảnh báo**
  `webhook.ghtk_unverified`. Khuyến nghị đặt `?hash` ở callback URL (ghi trong doc) để siết sau.

## 11. Gợi ý phí ship lúc tạo đơn (cô lập)

**Backend — endpoint carrier-agnostic** (KHÔNG hardcode tên carrier ở core):
- `POST /api/v1/fulfillment/quote` (FormRequest → service → resource).
- Input: `{ carrier_account_id?, weight_grams, value?, recipient: {province, district, ward?, address?} }`.
- Logic: resolve carrier account (ưu tiên `carrier_account_id`, else default GHTK account của tenant) →
  `connector->quote(account, payload)`. Nếu connector không hỗ trợ (`UnsupportedOperation`) ⇒ trả `{ data: null }`.
- Output: `{ data: { fee, insurance_fee, carrier } }` hoặc `{ data: null }`.

**Frontend — `CreateOrderPage.tsx`:**
- Nút **"Gợi ý phí GHTK"** (icon font, không emoji) cạnh ô "Phí giao hàng". Chỉ render khi tenant có tài khoản GHTK
  + đủ địa chỉ nhận + cân nặng. Bấm ⇒ gọi `/quote` ⇒ hiện dòng "Phí GHTK gợi ý: X đ" + nút **"Áp dụng"**.
- "Áp dụng" mới điền vào ô `shipping_fee` (không tự đè). Lỗi/empty ⇒ hiện "Không lấy được phí" nhẹ, **không chặn** tạo đơn.
- Không đổi logic COD/totals; chỉ ghi vào `shipping_fee` khi user bấm Áp dụng (totals tự tính lại như thường).

**Cô lập:** endpoint generic qua registry; FE gated theo GHTK + fail-safe; không sửa GHN/COD/connector khác.

## 12. Đơn vị & gotchas

- products weight = **KG**; fee API weight = **GRAM** → convert cẩn thận.
- `pick_money`/`value` = integer VND.
- Tem **PDF trực tiếp** → kiểm tra `ShipmentService::fetchLabel`/lưu media set đúng content-type PDF (GHN trả HTML).
- Prefix `manual_ghtk` tự sinh (ShipmentService dùng `'manual_'.$code`); webhook controller xử lý cả `ghtk` & `manual_ghtk`.

## 13. Test (mirror GHN)

- `tests/Feature/Fulfillment/ManualOrderGhtkFulfillmentTest.php` — Http::fake các endpoint create/label/track/cancel/webhook/fee:
  tạo vận đơn gắn tracking, markPacked → awaiting_pickup, webhook đồng bộ + idempotent, COD = tiền còn thiếu đẩy đúng.
- `tests/Unit/Fulfillment/GhtkStatusMapTest.php` — map status_id → chuẩn.
- `tests/Feature/Fulfillment/ShippingQuoteTest.php` — endpoint `/quote`: GHTK trả phí; carrier không hỗ trợ ⇒ null; thiếu account ⇒ null.

## 14. Tài liệu cập nhật (luật vàng: doc trước code)

- Doc carrier GHTK (mô tả connector, credentials, capability) — theo `extensibility-rules.md` checklist.
- `docs/05-api/endpoints.md`: thêm `POST /webhook/carriers/ghtk` và `POST /api/v1/fulfillment/quote`.
- `config/integrations.php` block carrier (nếu cần) + `.env.example`.

## 15. Rủi ro / mục cần xác nhận khi code

- Bảng status_id (§9) lấy từ kiến thức + docs một phần — **đối chiếu lại trang tracking-status khi code**.
- Cách Fulfillment lưu tem PDF (content-type / disk) — verify trong `ShipmentService`.
- Webhook GHTK không ký mạnh — chấp nhận hạn chế v1, ghi rõ + khuyến nghị hash.
