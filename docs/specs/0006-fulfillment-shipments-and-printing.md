# SPEC 0006: Giao hàng & in ấn — vận đơn, ĐVVC riêng (Manual + GHN), in tem hàng loạt, picking/packing, quét đóng gói

- **Trạng thái:** Implemented (2026-05-17 — Phase 3 lõi; phần "logistics của sàn"/TikTok arrange-shipment, GHTK/J&T, template in tuỳ biến, lưu-in-lại-90-ngày để follow-up)
- **Phase:** 3 *(lõi)*
- **Module backend liên quan:** Fulfillment (mới — lần đầu có code), Orders, Inventory, Channels
- **Tác giả / Ngày:** Team · 2026-05-17
- **Liên quan:** `docs/03-domain/fulfillment-and-printing.md` (domain), `docs/03-domain/order-status-state-machine.md`, SPEC-0003 (tồn kho), `docs/07-infra/cloudflare-r2-uploads.md` (kho lưu file), `app/Integrations/Carriers/Contracts/CarrierConnector.php`. Mẫu UI: BigSeller "Vận đơn" / "Đóng gói".

## 1. Vấn đề & mục tiêu
Hết Phase 2, đơn có thể trừ/đẩy tồn nhưng **không có đường tạo vận đơn, in tem, soạn hàng, đóng gói**. Phase 3 mở module **Fulfillment**: từ danh sách đơn ở `processing`/`ready_to_ship` → tạo vận đơn (đơn lẻ/hàng loạt) qua ĐVVC riêng → lấy label PDF lưu trên kho media (R2) → in tem **ghép 1 file** + in **picking list** (gộp theo SKU) + **packing list** (mỗi đơn 1 phiếu) → **quét mã đóng gói** xác nhận đóng/bàn giao ⇒ đơn `shipped` + trừ tồn.

Phạm vi v1: **luồng B — ĐVVC riêng** (`CarrierConnector`) với 2 connector: **Manual** (tự nhập/tự quản tracking — chạy được end-to-end không cần creds) và **GHN** (gọi API thật). In PDF qua **Gotenberg**.

## 2. Trong / ngoài phạm vi
**Trong:**
- `carrier_accounts` (creds ĐVVC theo tenant, mã hoá), `shipments` + `shipment_events`, `print_jobs` (bản tối giản: type/scope/file_url/status…), connector `manual`+`ghn` + `AbstractCarrierConnector` + `GhnClient` + `GotenbergClient`.
- API: carrier-accounts CRUD; tạo/huỷ/track vận đơn (đơn lẻ + hàng loạt); danh sách "cần giao" + danh sách vận đơn; tạo print job (label/picking/packing) + tải file; quét đóng gói; bàn giao (handover).
- Đồng bộ trạng thái: shipment `created`⇒đơn `ready_to_ship`; `picked_up`/`in_transit`⇒`shipped`; `delivered`⇒`delivered`; `failed`⇒`delivery_failed` — phát `OrderUpserted` ⇒ tồn được trừ/hoàn tự động (SPEC 0003) + ghi `order_status_history(source=carrier|user|system)`.
- FE: trang **Giao hàng** (tab *Cần giao* / *Vận đơn* / *Quét đóng gói*) + **Cài đặt → ĐVVC** + thông tin vận đơn ở chi tiết đơn.
- Job in PDF chạy ở queue `labels` (rule 3 của domain doc); FE poll `print_jobs`.
**Ngoài (follow-up Phase 3 / Phase sau):**
- **Luồng A — logistics của sàn** (TikTok "arrange shipment" + lấy label/invoice của sàn): cần xác nhận shape Partner API ⇒ spec/PR riêng. Hiện đơn sàn vẫn dùng luồng B (tự ship) nếu nhà bán chọn.
- **GHTK, J&T Express** (và đợt 2: ViettelPost, NinjaVan, SPX, VNPost, Best, Ahamove/Grab) — thêm = một class connector + 1 dòng registry + config (đã sẵn khung).
- **Template in tuỳ biến** (`print_templates`, layout JSON, logo) — v1 dùng template HTML dựng sẵn cho picking/packing; label thì dùng đúng file ĐVVC cấp.
- **Lưu & in lại phiếu 90 ngày** (`order_print_documents`, `expires_at`/`purged_at`, `PrunePrintDocuments`) — domain doc §8 nói rõ "logic làm sau" ⇒ spec riêng `NNNN-print-document-retention`.
- **Pickup batch** (lô bàn giao có phiếu ký nhận) — v1 chỉ có "handover" theo bộ chọn (đặt `picked_up_at`, đơn→`shipped`); bảng `pickup_batches` để sau.
- **Carrier webhook** (cập nhật tracking realtime) — v1 chỉ có job poll `getTracking` định kỳ + nút "Cập nhật tracking" thủ công.
- **Tách nhiều kiện / đơn** (`order.is_split`, nhiều shipment/đơn) — v1: 1 đơn = 1 shipment.
- **Đối soát phí ship thực tế** — Phase 6 (Finance); v1 lưu `fee` ước tính trên `shipments`.

## 3. Luồng chính
### 3.1 Cấu hình ĐVVC
Cài đặt → ĐVVC → thêm tài khoản: chọn carrier (`manual` luôn có; `ghn` nếu bật trong `INTEGRATIONS_CARRIERS`) → nhập creds (GHN: `token`, `shop_id`; Manual: không cần) → lưu `carrier_accounts.credentials` (mã hoá qua cast `encrypted:array`). Có thể đánh dấu một tài khoản là `is_default`.

### 3.2 Tạo vận đơn (đơn lẻ / hàng loạt)
Trang Giao hàng → tab "Cần giao" (đơn `status ∈ {processing, ready_to_ship}`, chưa có shipment chưa huỷ) → chọn N đơn → "Tạo vận đơn" (chọn carrier account + service?) ⇒ `POST /shipments/bulk-create` (hoặc `POST /orders/{id}/ship` cho đơn lẻ):
- với mỗi đơn: build payload người gửi (từ kho mặc định / `carrier_accounts.meta.from_address`) + người nhận (`order.shipping_address`/`buyer_*`) + kiện (cân nặng = tổng `skus.weight_grams`×qty, fallback config) + COD (`order.cod_amount` nếu `is_cod`) → `connector.createShipment()` ⇒ `{tracking_no, carrier, status, fee?, raw}` ⇒ tạo `shipments` (`status=created`) + `shipment_events` (event đầu).
- `connector.getLabel(tracking_no)` (nếu connector hỗ trợ) → tải bytes PDF → lưu `tenants/{t}/labels/{shipment_id}.pdf` trên kho media → `shipments.label_url`.
- đơn chuyển `processing → ready_to_ship` (nếu đang trước đó) + `order_status_history(source=system)`; phát `OrderUpserted`.
- Manual carrier: `tracking_no` do người dùng nhập (hoặc tự sinh `MAN-<ulid>` nếu để trống), không có label.
- Lỗi từng đơn ⇒ ghi vào kết quả `errors[]`, không chặn cả batch (rule 4).

### 3.3 In ấn
- **In tem hàng loạt** (`print_jobs type=label`): chọn N shipment đã có `label_url` → `POST /print-jobs {type:'label', shipment_ids}` ⇒ job `GenerateBulkLabel` (queue `labels`): tải các PDF từ kho media, **ghép** bằng `GotenbergClient::mergePdfs` (sắp theo carrier rồi mã đơn), lưu kết quả lên kho media, set `print_jobs.file_url`+`status=done`. FE poll `GET /print-jobs/{id}` đến khi `done` → mở `file_url`. Shipment chưa có label ⇒ bỏ qua + ghi vào `meta.skipped`.
- **Picking list** (`type=picking`, theo `order_ids`): job `GeneratePickingList`: gộp **theo SKU** (`SKU X — tổng N — từ đơn #…`) → HTML template dựng sẵn → `GotenbergClient::htmlToPdf` → lưu → `done`.
- **Packing list** (`type=packing`, theo `order_ids`): job `GeneratePackingList`: mỗi đơn 1 trang (mã đơn, người nhận, items, ghi chú) → HTML → PDF → lưu.
- (v1 chưa lưu `order_print_documents`/in-lại-90-ngày — xem §2 ngoài phạm vi.)

### 3.4 Quét đóng gói (scan-to-pack) & bàn giao
- Tab "Quét đóng gói": nhập/quét **mã vận đơn hoặc mã đơn** ⇒ `POST /scan-pack {code}`:
  1. tìm `shipment` theo `tracking_no` (hoặc `order.order_number`/`external_order_id`) trong tenant. Không thấy ⇒ `404` báo rõ. Sai trạng thái (đã `picked_up`+ / `cancelled`) ⇒ `409` "đã quét rồi / không hợp lệ" (rule 5: chống quét trùng = no-op + thông báo).
  2. `shipment.status = picked_up`, `picked_up_at = now`, `shipment_events` (`packed_scanned`). Đơn `→ shipped` + `order_status_history(source=user)`; phát `OrderUpserted` ⇒ ledger trừ tồn (`order_ship`, idempotent).
  3. trả `{shipment, order}` để FE hiện "đã đóng gói đơn #…".
- **Bàn giao hàng loạt** (`POST /shipments/handover {shipment_ids}`): với mỗi shipment `created`→`picked_up` (như trên, nguồn `system`). Dùng khi không quét từng kiện.

### 3.5 Track
- `POST /shipments/{id}/track` (hoặc job `SyncShipmentTracking` scheduled mỗi ~30' cho shipment chưa `delivered`/`cancelled`): `connector.getTracking()` → ghi `shipment_events` (mới) → map status → cập nhật `shipments.status` + (nếu đổi) đơn (`delivered`⇒`delivered`+`delivered_at`; `failed`⇒`delivery_failed`; `returned`⇒`returning`) + `order_status_history(source=carrier)` + `OrderUpserted`. Idempotent (dedupe event theo `(shipment_id, occurred_at, code)`).
- `POST /shipments/{id}/cancel`: `connector.cancel()` → `shipment.status=cancelled` + event; đơn quay về `processing` nếu đang `ready_to_ship` & chưa shipped; `OrderUpserted` (ledger release reservation nếu có).

## 4. Hành vi & quy tắc
- **Không tự vẽ lại label của ĐVVC** (rule 1). Manual carrier không có label ⇒ FE chỉ hiển thị tracking để in tay.
- **Trừ tồn ở bước nào**: mặc định khi đơn `shipped` (= lúc quét đóng gói / bàn giao). Cấu hình `config/fulfillment.php` `deduct_on` (`shipped` mặc định | `created`) — v1 chỉ implement `shipped`.
- **Idempotent**: tạo vận đơn 2 lần cho cùng đơn ⇒ trả shipment đã có (`created`) chứ không tạo trùng (1 đơn = 1 shipment chưa huỷ). Quét trùng ⇒ no-op. Track ⇒ dedupe event. Mọi thay đổi trạng thái đơn đi qua `OrderUpserted` ⇒ ledger tự dedupe per `(order_item, sku, type)`.
- **Phân quyền** (dùng permission đã có trong `Role` enum + 1 mới):
  - `fulfillment.view` — xem danh sách cần-giao / vận đơn / ĐVVC (StaffOrder, StaffWarehouse).
  - `fulfillment.ship` — tạo/huỷ/track vận đơn, bàn giao (StaffOrder, StaffWarehouse).
  - `fulfillment.scan` — quét đóng gói (StaffWarehouse).
  - `fulfillment.print` — tạo print job / tải file (StaffOrder, StaffWarehouse).
  - `fulfillment.carriers` — thêm/sửa/xoá `carrier_accounts` (chỉ owner/admin — qua `*`).
- **Audit**: tạo/huỷ vận đơn, in, bàn giao ghi `audit_logs` (rule 2). *(v1 dùng cơ chế audit hiện có nếu có; nếu chưa wired thì để TODO.)*
- **PDF luôn ở job queue `labels`** (rule 3); request HTTP chỉ tạo `print_jobs` (`status=pending`) rồi dispatch.

## 5. Dữ liệu (migrations module Fulfillment — `2026_05_17_*`)
- **`carrier_accounts`**: `tenant_id`, `carrier` (string), `name` (string), `credentials` (text — cast `encrypted:array`), `default_service` (string null), `is_default` (bool), `meta` (json — vd `from_address`), `is_active` (bool), timestamps. Unique `(tenant_id, carrier, name)`.
- **`shipments`**: `tenant_id`, `order_id` (index), `carrier` (string), `carrier_account_id` (null), `package_no` (string null), `tracking_no` (string null, index), `status` (string — `pending|created|picked_up|in_transit|delivered|failed|returned|cancelled`), `service` (string null), `weight_grams` (uint null), `dims` (json null), `cod_amount` (bigint def 0), `fee` (bigint def 0 — ước tính), `label_url` (string null), `label_path` (string null), `picked_up_at`/`delivered_at` (ts null), `raw` (json null), timestamps. Index `(tenant_id, status)`, `(tenant_id, order_id)`.
- **`shipment_events`**: `tenant_id`, `shipment_id` (index), `code` (string), `description` (string null), `status` (string null — trạng thái shipment suy ra), `occurred_at` (ts), `source` (string — `carrier|system|user`), `raw` (json null), `created_at`. Unique `(shipment_id, code, occurred_at)` để dedupe.
- **`print_jobs`** (bản tối giản): `tenant_id`, `type` (`label|picking|packing`), `scope` (json — `{order_ids?:[], shipment_ids?:[]}`), `file_url` (string null), `file_path` (string null), `file_size` (uint null), `status` (`pending|processing|done|error`), `error` (string null), `meta` (json null — vd `pages`, `skipped`), `created_by` (uint null), timestamps. *(Các cột `expires_at/purged_at` + bảng `order_print_documents` để spec retention sau.)*
- Không cột mới trên `orders`/`order_items` ở v1 (`order.carrier` đã có — set khi tạo vận đơn đầu).
- Domain events (module Fulfillment, `Events/`): `ShipmentCreated`, `ShipmentStatusChanged`, `ShipmentCancelled`, `PrintJobCompleted`.

## 6. API & UI
**Endpoint** (cập nhật `docs/05-api/endpoints.md`; tất cả dưới `auth:sanctum`+`tenant`):
- `GET/POST /api/v1/carrier-accounts` (`fulfillment.view` / `fulfillment.carriers`) — list / `{carrier, name, credentials, default_service?, is_default?, meta?}` ⇒ `201`. `PATCH/DELETE /carrier-accounts/{id}` (`fulfillment.carriers`).
- `GET /api/v1/carriers` (`fulfillment.view`) — danh sách carrier khả dụng (`code`, `name`, `capabilities`).
- `GET /api/v1/fulfillment/ready` (`fulfillment.view`) — đơn cần giao (`processing|ready_to_ship`, chưa có shipment chưa huỷ) — filter giống `/orders`.
- `POST /api/v1/orders/{id}/ship` (`fulfillment.ship`) `{carrier_account_id?, service?, tracking_no?, weight_grams?, cod_amount?, note?}` ⇒ `{data: ShipmentResource}` (đã có shipment chưa huỷ ⇒ trả lại nó, `200`).
- `POST /api/v1/shipments/bulk-create` (`fulfillment.ship`) `{order_ids:[], carrier_account_id?, service?}` ⇒ `{data:{created:[ShipmentResource], errors:[{order_id, message}]}}`.
- `GET /api/v1/shipments` (`fulfillment.view`) — filter `status?, carrier?, order_id?, q?(tracking/order)`, pagination. `GET /shipments/{id}` (`fulfillment.view`) — + `events`.
- `POST /api/v1/shipments/{id}/track` (`fulfillment.ship`) ⇒ `ShipmentResource` (+ events). `POST /shipments/{id}/cancel` (`fulfillment.ship`).
- `GET /api/v1/shipments/{id}/label` (`fulfillment.print`) ⇒ redirect tới `label_url` (404 nếu chưa có).
- `POST /api/v1/shipments/handover` (`fulfillment.ship`) `{shipment_ids:[]}` ⇒ `{handed_over:N}`.
- `POST /api/v1/print-jobs` (`fulfillment.print`) `{type:'label'|'picking'|'packing', order_ids?:[], shipment_ids?:[]}` ⇒ `201 {data: PrintJobResource}` (`status=pending`). `GET /print-jobs` / `GET /print-jobs/{id}` (`fulfillment.print`). `GET /print-jobs/{id}/download` ⇒ redirect tới `file_url` (409 nếu chưa `done`).
- `POST /api/v1/scan-pack` (`fulfillment.scan`) `{code}` ⇒ `{data:{shipment: ShipmentResource, order:{id, order_number, status}}}` (`404` không thấy, `409` đã quét/không hợp lệ).
- `OrderResource` thêm `shipment` (shipment mới nhất chưa huỷ: `{id, carrier, tracking_no, status, label_url}` hoặc null).

**UI** (FE):
- Trang **Giao hàng** `/fulfillment` (thay `ComingSoon`): 3 tab — *Cần giao* (bảng đơn ready + `rowSelection` + "Tạo vận đơn (n)" [modal chọn ĐVVC] + "In tem (n)" + "Picking list (n)" + "Packing list (n)" + "Bàn giao (n)"), *Vận đơn* (bảng shipments: mã đơn, ĐVVC, tracking, trạng thái, COD, nút In tem / Track / Huỷ), *Quét đóng gói* (ô input lớn auto-focus, lịch sử quét trong phiên, thông báo xanh/đỏ). Print job đang chạy ⇒ hiện thẻ tiến trình + nút "Tải file" khi xong.
- **Cài đặt → ĐVVC** (`/settings/carriers`): danh sách `carrier_accounts` + thêm/sửa (form theo carrier: GHN cần token+shop_id; Manual không cần).
- **Chi tiết đơn**: thêm card "Vận đơn" (ĐVVC, mã tracking, trạng thái, nút In tem / Track / Huỷ, hoặc nút "Tạo vận đơn" nếu chưa có).
- Mục sidebar "Giao hàng & in" trỏ `/fulfillment`.

## 7. Cách kiểm thử
- `tests/Feature/Fulfillment/`:
  - `CarrierAccountApiTest` — CRUD + RBAC (`fulfillment.carriers` chỉ owner/admin; viewer 403) + tenant isolation + creds không lộ raw.
  - `ShipmentFlowTest` — tạo đơn manual `processing` → `POST /orders/{id}/ship` (carrier `manual`) ⇒ shipment `created`, đơn `ready_to_ship`, history; gọi lại ⇒ trả shipment cũ (không trùng); `POST /scan-pack {code: tracking}` ⇒ shipment `picked_up`, đơn `shipped`, ledger có movement `order_ship`; quét lại ⇒ `409`; `POST /shipments/{id}/cancel` ⇒ `cancelled`, đơn về `processing`.
  - `GhnConnectorTest` — `Http::fake` GHN `create-order` / `print` / `tracking` → `createShipment`/`getLabel`/`getTracking` trả đúng shape; tạo vận đơn qua API với carrier account GHN.
  - `PrintJobTest` — `Http::fake` Gotenberg merge/html → `POST /print-jobs {type:label, shipment_ids}` ⇒ job chạy (queue sync trong test) ⇒ `done` + `file_url`; picking list gộp theo SKU đúng số lượng.
  - `bulk-create` với 1 đơn lỗi ⇒ `errors[]` có đơn đó, các đơn khác vẫn `created`.
- Unit: `GhnStatusMap` (map raw → shipment status), `ScanCodeResolver` (tracking vs order code).

## 8. Triển khai
- `INTEGRATIONS_CARRIERS=ghn` (Manual luôn bật, không cần liệt kê) + creds GHN nhập trong UI (không phải env). `GOTENBERG_URL` đã có trong `.env.example` (mặc định `http://localhost:3000`; trong stack Docker là service `gotenberg`). File label/print lưu trên `media.disk` (R2 ở prod — xem `docs/07-infra/cloudflare-r2-uploads.md`).
- Migration mới (module Fulfillment) — chạy `php artisan migrate --force` như mọi đợt feature (prod `RUN_MIGRATIONS=false`).
- Horizon đã có supervisor cho queue `labels` (Phase 0) — không cần thêm.
