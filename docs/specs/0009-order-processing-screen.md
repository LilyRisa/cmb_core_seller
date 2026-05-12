# SPEC 0009: Màn "Xử lý đơn hàng" — chuẩn bị → in tem → đóng gói → bàn giao ĐVVC (cùng màn hình, cùng luồng)

- **Trạng thái:** Implemented (2026-05-18 — mở rộng SPEC-0006; luồng A "logistics của sàn" + tách nhiều kiện = follow-up). **Cập nhật 2026-05-12 (SPEC-0012):** bỏ 3 tab-bước riêng `📦 Cần chuẩn bị / 🖨 Chờ đóng gói / 🚚 Chờ bàn giao ĐVVC` — thao tác (Tạo vận đơn · In tem · Đóng gói · Bàn giao ĐVVC · In hoá đơn) giờ là **các nút trong cột "Thao tác"** ngay trên các tab trạng thái đơn có sẵn (`Chờ xử lý` / `Đang xử lý` / `Chờ bàn giao`); trạng thái đơn vẫn đồng bộ theo trạng thái gốc (canonical), không sinh trạng thái phụ. Tab `🏷 Vận đơn` giữ nguyên; nút "Quét đơn" mở modal đóng gói/bàn giao. Endpoint `/fulfillment/processing` + `/processing/counts` còn giữ cho app/API nhưng FE web không còn dùng. Luồng nghiệp vụ (create→pack→handover, trừ tồn ở bước bàn giao) **không đổi**.
- **Phase:** 3 *(mở rộng SPEC-0006; áp cho mọi nền tảng — TikTok/Shopee/Lazada/đơn tay)*
- **Module backend liên quan:** Fulfillment (chính), Orders, Inventory, Channels
- **Tác giả / Ngày:** Team · 2026-05-18
- **Liên quan:** SPEC-0006 (vận đơn/ĐVVC/in tem/scan-to-pack — nền tảng), `docs/04-channels/order-processing.md` (vòng đời fulfillment TikTok/Lazada/Shopee + luồng chung), `docs/03-domain/fulfillment-and-printing.md`, `docs/03-domain/order-status-state-machine.md`. Mẫu UI: BigSeller "Xử lý đơn / Đóng gói / Bàn giao".

## 1. Vấn đề & mục tiêu
SPEC-0006 đã có vận đơn + in tem + quét đóng gói nhưng rời rạc (tab "Cần giao" / "Vận đơn" / "Quét"); thiếu: (a) một **luồng tuyến tính** đơn-sàn đi qua các bước cần xử lý — **chuẩn bị hàng → lấy phiếu in → đóng gói → bàn giao ĐVVC** — trên **cùng một màn hình**; (b) phân biệt "đóng gói" với "bàn giao" (SPEC-0006 gộp chung khi quét); (c) **đếm số lần in tem** + cảnh báo in lại; (d) chặn in tem nhiều nền tảng/ĐVVC cùng lúc; (e) bộ lọc theo nền tảng + 2 ô lọc khách hàng / sản phẩm; (f) API cho **app quét đơn** đẩy sang trạng thái bàn giao.

Mục tiêu: NV xử lý đơn trên một màn — thấy ngay đơn nào sót (chống **in thiếu đơn**), đơn nào đã in chưa gói (chống **gói thiếu hàng**), đơn nào đã gói chưa bàn giao; in tem hàng loạt an toàn (cùng nền tảng + ĐVVC, cảnh báo in lại); và để app/máy quét gọi API quét đơn ở bước đóng gói & bàn giao.

## 2. Trong / ngoài phạm vi
**Trong:**
- Trạng thái vận đơn mới **`packed`** (giữa `created` và `picked_up`); cột `shipments.print_count` + `last_printed_at` + `packed_at`.
- `ShipmentService::markPacked()` (created → packed, **không** đổi trạng thái đơn, **không** trừ tồn); `handover()` (created/packed → picked_up, đơn → `shipped`, trừ tồn) — tách bạch "đóng gói" vs "bàn giao". `RenderPrintJob` chạy đúng tenant (fix scope khi chạy qua queue).
- API: `GET /fulfillment/processing?stage=prepare|pack|handover` + `…/processing/counts` (lọc: `source` csv, `carrier` csv, `customer`, `product`); `POST /shipments/pack` (bulk đóng gói); `POST /scan-pack {code}` (= đóng gói) & `POST /scan-handover {code}` (= bàn giao — app gọi cái này); giữ `POST /shipments/handover` (bulk bàn giao). `PrintService` đếm `print_count` mỗi lần in tem + chặn in tem khi bộ chọn lẫn nhiều nền tảng / ĐVVC (`422`). `OrderResource.shipment`/`ShipmentResource` thêm `print_count`/`last_printed_at`/`packed_at`.
- FE: **xử lý đơn nằm NGAY TRONG trang Đơn hàng** (`/orders` — BigSeller-style, không tách trang riêng; route cũ `/fulfillment` redirect về `/orders?tab=prepare`, bỏ mục sidebar riêng). **3 bước = 3 tab nằm cùng dải tab với các tab trạng thái đơn ở trên** (KHÔNG lồng sub-tab): `📦 Cần chuẩn bị (n)` · `🖨 Chờ đóng gói (n)` · `🚚 Chờ bàn giao ĐVVC (n)` + `🏷 Vận đơn`. Chọn 1 tab-bước ⇒ hiện chips nền tảng (TikTok/Shopee/Lazada/Đơn tay, có màu) + 2 ô lọc (khách hàng / sản phẩm) + bảng đơn của bước đó (`StageBoard`); quét = nút "Quét đóng gói"/"Quét bàn giao" mở **modal** (`Segmented` Đóng gói/Bàn giao). Badge "đã in N×" trên mỗi dòng; nút "In tem" **disable + tooltip** khi bộ chọn lẫn nền tảng/ĐVVC; in lại (chọn có đơn `print_count≥1`) ⇒ **popup xác nhận** trước khi in.
**Ngoài (follow-up):**
- **Luồng A — logistics của sàn** (TikTok arrange-shipment, Lazada RTS, Shopee ship_order + lấy AWB của sàn) — `docs/04-channels/order-processing.md` §2–4; khi làm thì cùng màn này thêm nhánh "đơn dùng logistics sàn" (chỉ khác bước tạo vận đơn). Shopee connector chưa có.
- **Tách nhiều kiện / đơn** (`order.is_split`, nhiều `shipments`/đơn) — v1: 1 đơn = 1 shipment.
- **Lưu & in lại phiếu 90 ngày** (`print_jobs.expires_at`/`purged_at`, `order_print_documents`, `PrunePrintDocuments`) — spec riêng (domain doc §8).
- **Template in tuỳ biến** — SPEC-0007 §7.
- **Pickup batch** (lô bàn giao có phiếu ký nhận) — domain doc §6.
- Lọc khách hàng theo SĐT (cột `buyer_phone` mã hoá ⇒ không LIKE được) — chỉ lọc theo tên / mã đơn ở v1.

## 3. Luồng (3 bước — cùng màn)
| Stage | Đơn nào | Hành động chính | Hệ quả |
|---|---|---|---|
| **prepare** ("Cần xử lý") | đơn `processing`/`ready_to_ship` chưa có vận đơn open; HOẶC có vận đơn `created` chưa in tem (`print_count=0` & có `label_path` — ĐVVC `manual` không có tem nên vào thẳng `pack`). | **Tạo vận đơn** (`POST /shipments/bulk-create` hoặc `/orders/{id}/ship`) → connector tạo tracking + (nếu hỗ trợ) tải tem PDF lưu kho media. **In tem** (`POST /print-jobs {type:label, shipment_ids}` — chọn nhiều đơn) → `RenderPrintJob` ghép 1 file. | đơn `processing → ready_to_ship`; vận đơn `created`; in xong ⇒ `print_count++`, `last_printed_at`; rời `prepare` → `pack`. |
| **pack** ("Chờ đóng gói") | vận đơn `created` & (`print_count≥1` hoặc không có tem để in). | **Đóng gói** (`POST /shipments/pack {shipment_ids}` — bulk; hoặc `POST /scan-pack {code}` — quét mã vận đơn/mã đơn, máy quét/app). **In lại tem** (từ lần 2 ⇒ FE popup xác nhận). | vận đơn `created → packed`, `packed_at`; đơn **vẫn** `ready_to_ship`, **chưa** trừ tồn; → `handover`. |
| **handover** ("Chờ bàn giao") | vận đơn `packed`. | **Bàn giao ĐVVC** (`POST /shipments/handover {shipment_ids}` — bulk; hoặc `POST /scan-handover {code}` — **app quét đơn gọi API này**). | vận đơn `packed → picked_up`, `picked_up_at`; đơn `→ shipped` (`order_status_history`, phát `OrderUpserted` ⇒ ledger trừ tồn `order_ship`, idempotent); → tracking. |

Sau đó `POST /shipments/{id}/track` / job định kỳ ⇒ `in_transit/delivered/failed/returned` → đồng bộ `orders.status`.

## 4. Hành vi & quy tắc
- **`scan-pack` ≠ `scan-handover`** (thay đổi so với SPEC-0006 cũ — `scan-pack` từng đi thẳng `picked_up`+`shipped`): `scan-pack` chỉ đánh dấu **đóng gói**; `scan-handover` mới bàn giao + trừ tồn. App/máy quét: bước đóng gói gọi `/scan-pack`, bước bàn giao gọi `/scan-handover`. Quét trùng ⇒ `409`; mã không thấy ⇒ `404` (báo rõ). ĐVVC `manual` không có tem ⇒ đơn vào thẳng `pack` (bỏ bước in).
- **In tem cùng 1 nền tảng + 1 ĐVVC**: `PrintService::createJob(type=label)` resolve các shipment trong scope, nếu `count(distinct carrier)>1` hoặc `count(distinct order.source)>1` ⇒ `422` ("Không thể in tem nhiều nền tảng/ĐVVC cùng lúc…"). FE chặn trước (disable nút + tooltip) — vì khổ tem & lô lấy hàng khác nhau giữa các sàn/ĐVVC.
- **In lại**: cho phép in nhiều lần; mỗi lần in xong `print_count++` trên các shipment được ghép. FE: khi bộ chọn có đơn `print_count≥1` ⇒ `Modal.confirm` cảnh báo (mất tem/kẹt giấy mới in lại) trước khi gọi API. Dòng nào đã in hiển thị icon "🖨 N×".
- **`RenderPrintJob` chạy đúng tenant**: job render PDF chạy ở worker không có tenant ⇒ `runAs($job->tenant)` để các query tenant-scoped bên trong `PrintService` resolve đúng (fix bug SPEC-0006 khi dùng queue thật).
- **Trừ tồn**: chỉ ở bước **handover** (đơn `→ shipped`). Đóng gói KHÔNG trừ tồn (hàng vẫn trong kho, chỉ đã đóng thùng). Cấu hình `config/fulfillment.deduct_on` (`shipped` mặc định) như SPEC-0006.
- **Idempotent**: `markPacked`/`handover`/`createForOrder` no-op khi đã ở/qua trạng thái đó; ledger dedupe per `(order_item, sku, type)`.
- **Phân quyền** (đã có trong `Role`): `fulfillment.view` (xem bảng), `fulfillment.ship` (tạo/huỷ/track vận đơn, bàn giao), `fulfillment.scan` (đóng gói/quét), `fulfillment.print` (in tem/picking/packing), `fulfillment.carriers` (cấu hình ĐVVC). `pack` chấp nhận `fulfillment.scan` HOẶC `fulfillment.ship`.

## 5. Dữ liệu (migration `2026_05_18_100001_add_processing_columns_to_shipments`)
`shipments` thêm: `print_count` (uint, default 0), `last_printed_at` (ts, null), `packed_at` (ts, null). Hằng `Shipment::STATUS_PACKED='packed'` (vào `OPEN_STATUSES`); `Shipment::HANDED_OVER_STATUSES = [picked_up, in_transit, delivered]`. Không bảng mới. `shipment_events` thêm `code='packed'` (cột string tự do).

## 6. API & UI
**Endpoint mới / đổi** (cập nhật `docs/05-api/endpoints.md`):
- `GET /api/v1/fulfillment/processing` (`fulfillment.view`) query `stage` (`prepare`|`pack`|`handover`), `source` (csv nền tảng), `carrier` (csv ĐVVC), `customer` (LIKE tên/mã đơn), `product` (LIKE tên SP / `seller_sku`), `channel_account_id?`, `page`, `per_page≤200` ⇒ `{ data:[OrderResource (đã nạp `shipment`)], meta:{pagination, stage} }`. `GET /fulfillment/processing/counts` (cùng filter) ⇒ `{ data:{ prepare, pack, handover } }`. `GET /fulfillment/ready` = alias `processing?stage=prepare` (giữ tương thích SPEC-0006).
- `POST /api/v1/shipments/pack` (`fulfillment.scan`|`fulfillment.ship`) `{ shipment_ids:[≤500] }` ⇒ `{ data:{ packed:N } }`.
- `POST /api/v1/scan-pack` (`fulfillment.scan`) `{ code }` ⇒ `{ data:{ action:'pack', message, shipment, order } }` — đánh dấu đóng gói (created → packed). Đã đóng gói/bàn giao ⇒ `409`; không thấy ⇒ `404`.
- `POST /api/v1/scan-handover` (`fulfillment.ship`|`fulfillment.scan`) `{ code }` ⇒ `{ data:{ action:'handover', … } }` — bàn giao ĐVVC (created/packed → picked_up, đơn shipped, trừ tồn). Đã bàn giao ⇒ `409`.
- `POST /api/v1/print-jobs {type:label, …}` — nay `422` nếu bộ chọn lẫn nhiều nền tảng / nhiều ĐVVC.
- `OrderResource.shipment` thêm `print_count`, `packed_at`; `ShipmentResource` thêm `print_count`, `last_printed_at`, `packed_at`.

**UI** — `pages/OrdersPage.tsx` thêm vào dải tab (cạnh các tab trạng thái đơn) 3 tab-bước `prepare`/`pack`/`handover` + `shipments` (BigSeller "Đơn hàng → Xử lý đơn hàng" — không phải trang riêng, không lồng sub-tab). Khi tab-bước active: render `<PlatformChips>` (chips nền tảng `Tag.CheckableTag` màu theo `CHANNEL_META` + 2 `Input.Search` "Lọc theo khách hàng" / "Lọc theo sản phẩm") + `<StageBoard stage filters onPrint onGotoScan>` (bảng `Order` + `rowSelection` + hành động theo bước: Tạo vận đơn / In tem / Đóng gói / Bàn giao / Picking / Packing; cột "Vận đơn" = `ĐVVC + trạng thái + 🖨 N× + mã tracking`; "In tem" disable+tooltip khi lẫn nền tảng/ĐVVC, popup xác nhận khi in lại) + `<PrintJobBar>`. `onGotoScan(m)` mở `<Modal>` chứa `<ScanTab initialMode={m}>` (`Segmented` Đóng gói/Bàn giao + ô quét auto-focus + log phiên). Tab `shipments` = `<ShipmentsTab>` (danh sách shipments + track/huỷ/đóng gói/bàn giao/in tem). Các component dùng chung ở `resources/js/components/OrderProcessing.tsx` (`StageBoard`, `ScanTab`, `ShipmentsTab`, `PrintJobBar`, `PlatformChips`). Route `/fulfillment` redirect → `/orders?tab=prepare`; mục sidebar "Giao hàng & in" đã bỏ (gộp vào "Đơn hàng").

## 7. Cách kiểm thử
- `tests/Feature/Fulfillment/FulfillmentTest`:
  - `test_processing_flow_scan_pack_then_handover_then_cancel` — tạo đơn manual → tạo vận đơn (manual = vào thẳng `pack` stage) → `scan-pack` ⇒ `packed`, đơn vẫn `ready_to_ship`, **chưa** có movement `order_ship`, tồn chưa đổi, sang `handover` stage; `scan-pack` lại ⇒ `409`; `scan-handover` ⇒ `picked_up`, đơn `shipped`, có `order_ship`, tồn giảm; `scan-handover` lại ⇒ `409`; mã lạ ⇒ `404`; bulk `pack` + bulk `handover`; huỷ vận đơn `created` ⇒ đơn về `processing`.
  - `test_print_jobs_label_bundle_and_picking_list` — sau khi in tem `print_count=1`, in lại ⇒ `2`; ghép tem 2 ĐVVC khác nhau ⇒ `422`; (giữ các assert cũ: picking gộp theo SKU, `422` khi thiếu input, RBAC).
- `php artisan test` toàn bộ xanh (180+).

## 8. Triển khai
Migration mới ⇒ prod chạy `php artisan migrate --force`. Không config/env mới. Horizon queue `labels` đã có. App/máy quét đơn dùng `POST /scan-pack` (đóng gói) và `POST /scan-handover` (bàn giao) — auth Sanctum + header `X-Tenant-Id` như mọi API; quyền `fulfillment.scan` (đóng gói) / `fulfillment.ship` (bàn giao).
