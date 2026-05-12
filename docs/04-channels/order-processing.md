# Xử lý đơn hàng các sàn — TikTok Shop / Lazada / Shopee

**Status:** Living document · **Cập nhật:** 2026-05-12 (SPEC 0013)

> **Rà soát SPEC 0013:** ánh xạ trạng thái đã đổi — "đơn sàn đã arrange/in phiếu" (TikTok `AWAITING_COLLECTION`, Shopee `PROCESSED`, Lazada `packed`/`ready_to_ship`) nay → `processing` (không phải `ready_to_ship`); `ready_to_ship` (chuẩn) **chỉ đạt được bằng thao tác nội bộ** ("đã gói & quét đơn" / `markPacked`). Bước "Chuẩn bị hàng" (`createForOrder`) đẩy đơn `pending → processing`, **chặn nếu đơn có SKU âm tồn** (`∑on_hand−∑reserved<0` ⇒ `422`), và sinh **phiếu giao hàng tự tạo** (`print_jobs.type=delivery`) — kéo tem/AWB **thật** của sàn vẫn là "luồng A" (follow-up). 3 "stage" `prepare/pack/handover` trong tài liệu này nay tương ứng các tab trạng thái `pending`/`processing`/`ready_to_ship` (không còn tab-bước riêng trên web; endpoint `/fulfillment/processing*` giữ cho app/API). Xem [`../specs/0013-order-fulfillment-flow-and-out-of-stock.md`](../specs/0013-order-fulfillment-flow-and-out-of-stock.md) và [`../03-domain/order-status-state-machine.md`](../03-domain/order-status-state-machine.md).



> Tài liệu nền cho **màn "Xử lý đơn hàng"** (SPEC [0009](../specs/0009-order-processing-screen.md)) và phần fulfillment của các connector. Mục tiêu: hiểu **vòng đời fulfillment của từng sàn** rồi **gộp vào một luồng chung** (`prepare → pack → handover`) ánh xạ qua bảng `shipments` + `StandardOrderStatus`, để xử lý đơn cùng màn hình — tránh in thiếu đơn, gói thiếu hàng. Đọc kèm: [`../03-domain/order-status-state-machine.md`](../03-domain/order-status-state-machine.md), [`../03-domain/fulfillment-and-printing.md`](../03-domain/fulfillment-and-printing.md), SPEC-0001 (TikTok), SPEC-0006 (vận đơn/in), SPEC-0008 (Lazada).
>
> ⚠️ Tên trạng thái/endpoint dưới đây theo tài liệu Open API hiện có; **đối chiếu lại với sandbox thật** khi wiring "luồng A — logistics của sàn". Các bảng map ở `config/integrations.<provider>.status_map` để tinh chỉnh không cần đổi code; mọi event đơn vẫn `fetchOrderDetail` lại + polling backup.

## 0. Hai cách giao hàng (nhắc lại domain doc §1)
- **Luồng B — ĐVVC riêng** (đã implement, SPEC-0006): ta tự gọi `CarrierConnector` (Manual/GHN/…) tạo vận đơn → lấy tem → đóng gói → bàn giao. Áp cho **đơn manual** và **đơn sàn mà sàn cho người bán tự xử lý** (TikTok `FULFILLMENT_BY_SELLER`, Lazada non-FBL, Shopee non-SLS-managed). Trạng thái đơn (`StandardOrderStatus`) do **ta** cập nhật khi bàn giao (`→ shipped`).
- **Luồng A — logistics của sàn** (follow-up Phase 4): sàn chỉ định ĐVVC; ta gọi API sàn để "sắp xếp vận chuyển" (TikTok: tạo package + ship → lấy AWB; Lazada: RTS + lấy AWB; Shopee: `ship_order` + `get_shipping_document`) → lưu tem PDF → trạng thái đơn theo **sàn** (webhook/polling) chứ không phải ta. Khi làm: nối vào module Fulfillment như "luồng A".

Màn "Xử lý đơn" hiện dùng **luồng B** cho mọi đơn (kể cả đơn sàn — tạo vận đơn ĐVVC riêng); khi luồng A xong thì cùng màn đó thêm nhánh "đơn dùng logistics sàn" (chỉ khác bước "tạo vận đơn").

## 1. Luồng chung của ta (3 bước — màn "Xử lý đơn")
Ánh xạ qua `shipments.status` (SPEC-0006/0009): `pending → created → packed → picked_up → in_transit → delivered | failed | returned | cancelled`. Đơn (`orders.status`): `processing` → (tạo vận đơn) `ready_to_ship` → (bàn giao) `shipped` → `delivered` → `completed` (theo sàn).

| Bước (stage) | Đơn ở đâu | Hành động | Kết quả |
|---|---|---|---|
| **prepare** ("Cần xử lý") | đơn `processing`/`ready_to_ship` **chưa có vận đơn**, hoặc có vận đơn `created` **chưa in tem** (`print_count=0` & có tem để in — ĐVVC `manual` không có tem nên bỏ qua bước in) | **Tạo vận đơn** (chọn tài khoản ĐVVC) → connector tạo tracking + lấy tem PDF; **In tem** (chọn nhiều đơn → ghép 1 file — **cùng 1 nền tảng + cùng 1 ĐVVC**, sai ⇒ 422). | đơn `→ ready_to_ship`; vận đơn `created`; `print_count++`; rời `prepare`, sang `pack`. |
| **pack** ("Chờ đóng gói") | vận đơn `created` đã sẵn sàng đóng gói (`print_count≥1` hoặc không có tem để in) | **Đóng gói** (đánh dấu hàng loạt `POST /shipments/pack`, hoặc **quét** `POST /scan-pack {code}` — máy quét/app quét mã vận đơn hay mã đơn). Có thể **in lại tem** (từ lần 2 phải xác nhận popup — tránh in trùng). | vận đơn `→ packed`, `packed_at`; đơn **vẫn** `ready_to_ship`, **chưa** trừ tồn; sang `handover`. |
| **handover** ("Chờ bàn giao") | vận đơn `packed` | **Bàn giao ĐVVC** (hàng loạt `POST /shipments/handover`, hoặc **app quét** `POST /scan-handover {code}`). | vận đơn `→ picked_up`, `picked_up_at`; đơn `→ shipped` (ghi `order_status_history`, phát `OrderUpserted` ⇒ ledger **trừ tồn** `order_ship`, idempotent). |

Sau đó tracking (`POST /shipments/{id}/track` / job định kỳ) cập nhật `in_transit → delivered/failed/returned` → đồng bộ ngược về `orders.status`.

**Chống in thiếu / gói thiếu:** `prepare` liệt kê **mọi** đơn chưa có vận đơn/chưa in (thấy ngay đơn nào sót); `pack` liệt kê **mọi** vận đơn đã in chưa gói; `handover` liệt kê **mọi** đơn đã gói chưa bàn giao. Bộ đếm badge mỗi bước. "đã in N lần" hiển thị icon trên từng dòng.

## 2. TikTok Shop — vòng đời đơn & fulfillment
- **`order.status`** (chuỗi `202309`, map ở `config/integrations.tiktok.status_map`): `UNPAID → AWAITING_SHIPMENT → AWAITING_COLLECTION → IN_TRANSIT → DELIVERED → COMPLETED` (nhánh `CANCELLED`, `PARTIALLY_SHIPPING`, `ON_HOLD`). Ánh xạ chuẩn: `AWAITING_SHIPMENT`→`pending` (→`processing` nếu đã có package), `AWAITING_COLLECTION`→`ready_to_ship`, `IN_TRANSIT`→`shipped`, `DELIVERED`→`delivered`, `COMPLETED`→`completed`.
- **`fulfillment_type`** trên đơn: `FULFILLMENT_BY_SELLER` (người bán tự ship — dùng luồng B của ta) vs `FULFILLMENT_BY_TIKTOK` (FBT — TikTok lo kho/ship; ta hầu như chỉ theo dõi). Hiện ta xử lý chủ yếu FBS.
- **Luồng A của TikTok** (follow-up — `app/Integrations/Channels/TikTok/`, capability `shipping.*`):
  1. `order` có `packages[]` (sàn tự tạo, hoặc ta gọi `/fulfillment/202309/packages` để tạo) — mỗi package có `id`, ĐVVC TikTok gán, `tracking_number`.
  2. "Sắp xếp vận chuyển": `/fulfillment/202309/orders/{order_id}/packages/ship` (hoặc theo package) ⇒ đơn `→ AWAITING_COLLECTION`, có `tracking_number`.
  3. Lấy tem: `/fulfillment/202309/packages/{package_id}/shipping_documents?document_type=SHIPPING_LABEL` (hoặc `PACKING_SLIP`) ⇒ PDF (base64 hoặc URL) ⇒ lưu kho media ⇒ `shipments.label_url`.
  4. Bàn giao: ĐVVC tới lấy / drop-off; webhook `PACKAGE_UPDATE`/`ORDER_STATUS_CHANGE` ⇒ `IN_TRANSIT` ⇒ ta map `shipped`. (Ta **không** tự set `shipped` cho luồng A — sàn là nguồn sự thật, rule 1.)
- **Webhook** (`type` int, `config.tiktok.webhook_event_types`): `1`/`3`/`4` (order/address/package) ⇒ `order_status_update` ⇒ re-fetch detail; `12` ⇒ `order_cancel`; `2`/`13` ⇒ `return_update`; `6`/`14` ⇒ `shop_deauthorized`. Đơn luôn re-fetch — không tin webhook làm nguồn sự thật.
- **Tem**: phải dùng đúng PDF TikTok cấp (mã vạch ĐVVC chuẩn) — không tự vẽ lại (rule 1). Tem TikTok thường khổ ~A6 nhiệt. Ghép nhiều tem ⇒ cùng shop + cùng ĐVVC (ta enforce ở `PrintService`).

## 3. Lazada — vòng đời đơn & fulfillment
- **Đơn là item-level**: `order.statuses` là mảng (một status / order-item, đã dedup). `LazadaStatusMap::collapse()` gộp thành 1 status order-level (status đảo chiều `canceled`/`returned`/`shipped_back*` chỉ thắng nếu **toàn bộ** item; còn lại lấy forward ít tiến nhất). Status item (map ở `config.lazada.status_map`): `unpaid → pending → topack/ready_to_ship/packed → shipped → delivered` (nhánh `failed`/`lost`/`damaged` ⇒ `delivery_failed`; `shipped_back*` ⇒ `returning`; `returned` ⇒ `returned_refunded`; `canceled` ⇒ `cancelled`). Ánh xạ: `topack`→`processing`, `packed`/`ready_to_ship`→`ready_to_ship`, `shipped`→`shipped`.
- **FBL** (Fulfillment by Lazada — kho LGS lo) vs **non-FBL** (người bán tự ship — luồng B của ta). `item.shipping_type` cho biết.
- **Luồng A của Lazada** (follow-up — `app/Integrations/Channels/Lazada/`, capability `shipping.*`):
  1. "RTS" (Ready To Ship): `/order/rts` (hoặc `/order/pack` rồi `/order/rts`) cho danh sách `order_item_id` ⇒ Lazada gán/xác nhận ĐVVC + `tracking_number` ⇒ item `→ ready_to_ship` rồi `shipped`.
  2. Lấy tem/hoá đơn: `/order/document/get` với `doc_type=shippingLabel` (hoặc `carrierManifest`, `invoice`) + `order_item_ids` ⇒ trả **base64 PDF** ⇒ decode lưu kho media ⇒ `shipments.label_url`.
  3. Theo dõi: `/logistic/order/trace` hoặc push `Trade Order` ⇒ `shipped → delivered`.
- **Webhook (push message)**: `message_type` (int, `config.lazada.webhook_message_types`); push mang `data.trade_order_id`, `data.order_item_status` — ta dùng `order_item_status` làm fast-path cập nhật trạng thái rồi vẫn `fetchOrderDetail` (`/order/get` + `/order/items/get`). Lazada **không** có API subscribe per-shop — đăng ký message type ở console.
- **Tem**: trả base64 PDF — decode lưu MinIO/R2; **một order có thể nhiều package/AWB** (theo item) ⇒ nhiều `shipments` cho một `order` (Phase này v1: 1 đơn = 1 shipment; tách nhiều kiện là follow-up). Ghép tem ⇒ cùng shop + cùng ĐVVC.

## 4. Shopee — vòng đời đơn & fulfillment *(connector chưa làm — ghi để dev sau)*
- **`order_status`**: `UNPAID → READY_TO_SHIP → PROCESSED → SHIPPED → TO_CONFIRM_RECEIVE → COMPLETED` (nhánh `IN_CANCEL`/`CANCELLED`, `TO_RETURN`, `RETRY_SHIP`). Ánh xạ chuẩn (sẽ vào `config.shopee.status_map`): `READY_TO_SHIP`→`ready_to_ship`, `PROCESSED`→`processing`/`ready_to_ship` (tuỳ có AWB chưa), `SHIPPED`→`shipped`, `TO_CONFIRM_RECEIVE`→`delivered`, `COMPLETED`→`completed`.
- Shopee chia 2 chế độ: **dropoff** (mang ra điểm gửi) vs **pickup** (ĐVVC tới lấy). Phải `get_shipping_parameter` để biết chế độ + slot lấy hàng (liên quan "cài đặt thời gian lấy hàng" — SPEC-0007 §6.2).
- **Luồng A của Shopee** (khi làm — `app/Integrations/Channels/Shopee/`, capability `shipping.*`):
  1. `get_shipping_parameter(order_sn)` → chọn `pickup`/`dropoff` + (pickup) `address_id` + `pickup_time_id`, hoặc (dropoff) `branch_id`.
  2. `ship_order(order_sn, package_number?, pickup/dropoff params)` ⇒ Shopee gán `tracking_number` ⇒ đơn `→ PROCESSED`/`SHIPPED`.
  3. `get_tracking_number(order_sn)` ; `get_shipping_document_parameter` → `create_shipping_document` → `download_shipping_document` (PDF — `NORMAL_AIR_WAYBILL` / `THERMAL_AIR_WAYBILL` / `NORMAL_NO_AWB`) ⇒ lưu kho media ⇒ `shipments.label_url`.
  4. `split_order` (tách kiện) — Shopee hỗ trợ một đơn nhiều package ⇒ nhiều `shipments`/`order` (v1 chưa làm).
- **Webhook (push)**: `code` (int) — `3` order status update, `4` tracking number ready, `5` shipping document ready, ... ⇒ map sang `WebhookEventDTO.type`; re-fetch đơn. Verify chữ ký: HMAC-SHA256 của `url|body` với `partner_key`.
- **Ký request Shopee** khác TikTok/Lazada: `sign = HMAC_SHA256(partner_key, partner_id + api_path + timestamp [+ access_token + shop_id cho shop API])`, hex; OAuth là `code` → `/auth/token/get` → `access_token`+`refresh_token` (4h / 30 ngày), refresh `/auth/access_token/get`. Khi làm: theo nếp connector — class mới + 1 dòng `IntegrationsServiceProvider` + `config/integrations.shopee` — không sửa core.

## 5. Quy tắc chung khi xử lý đơn các sàn (cho dev)
1. **Trạng thái đơn = nguồn sự thật của sàn** cho luồng A (sàn lo ship); ta chỉ tự đổi `orders.status` ở luồng B (ĐVVC riêng) khi bàn giao. Mọi đổi trạng thái channel-driven KHÔNG bị `OrderStateMachine::canTransition()` chặn (xem state-machine doc) — nhưng regression bất thường ⇒ cờ `has_issue`.
2. **Tem do sàn/ĐVVC cấp — dùng đúng file đó**, không tự vẽ lại (mã vạch chuẩn). Picking/packing list thì tự render (Gotenberg, có thể template tuỳ biến — SPEC-0007 §7).
3. **In hàng loạt chỉ cùng 1 nền tảng + 1 ĐVVC** (khổ tem & lô lấy hàng khác nhau). `PrintService` enforce; FE chặn trước + báo lý do.
4. **Trừ tồn**: mặc định khi đơn `shipped` (= lúc bàn giao / `scan-handover` ở luồng B; webhook `SHIPPED`/`IN_TRANSIT` ở luồng A). Đi qua `OrderUpserted` ⇒ ledger idempotent per `(order_item, sku, type=order_ship)`.
5. **Một đơn có thể nhiều kiện** (Lazada theo item; Shopee `split_order`; TikTok nhiều `packages`) ⇒ về lý thuyết nhiều `shipments` / `order` (`order.is_split`). **v1: 1 đơn = 1 shipment** — tách nhiều kiện là follow-up.
6. **Idempotent + chống quét trùng**: tạo vận đơn 2 lần ⇒ trả shipment đã có; quét đóng gói/bàn giao lại ⇒ `409` no-op; track ⇒ dedupe event theo `(shipment_id, code, occurred_at)`.
7. **Lưu & in lại phiếu 90 ngày** (domain doc §8, SPEC-0007 §7) — spec riêng; hiện chỉ giữ file trên kho media, chưa purge.
