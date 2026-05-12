# SPEC 0013: Sửa luồng xử lý đơn (chờ xử lý → đang xử lý → chờ bàn giao) + chặn in phiếu khi hết hàng + phiếu giao hàng

- **Trạng thái:** Implemented
- **Phase:** 3+ (rà soát SPEC-0009; mở rộng SPEC-0006; nền tảng cho "luồng A — logistics của sàn")
- **Module backend liên quan:** Fulfillment, Orders, Inventory, Channels
- **Tác giả / Ngày:** đội phát triển · 2026-05-12
- **Liên quan:** SPEC-0006 (vận đơn/ĐVVC/in tem), SPEC-0009 (màn xử lý đơn), `docs/03-domain/order-status-state-machine.md`, `docs/04-channels/order-processing.md`

## 1. Vấn đề & mục tiêu
SPEC-0009/0006 map "đơn sàn đã arrange/in phiếu" (TikTok `AWAITING_COLLECTION`, Shopee `PROCESSED`, Lazada `packed`/`ready_to_ship`) thẳng về `ready_to_ship` ⇒ **mọi đơn đổ vào tab "Chờ bàn giao"** dù shop **chưa hề gói + quét đơn nội bộ**. Cần phân tách rõ:
- **Chờ xử lý** (`pending`) = đã xác nhận, **chưa in/arrange phiếu giao hàng**.
- **Đang xử lý** (`processing`) = **đã in/arrange phiếu** — đang **gói hàng + quét đơn nội bộ** (chống thiếu/lẫn đơn).
- **Chờ bàn giao** (`ready_to_ship`) = đã gói + đã quét xong (hoặc bấm "giao hàng thủ công") — sẵn sàng đưa ĐVVC.
- `shipped`/`delivered`/`completed`/return/`cancelled` đến từ webhook/polling của sàn (không phải ta tự set, trừ đơn manual).

Thêm: **chặn "Chuẩn bị hàng / lấy phiếu giao hàng" khi đơn có SKU âm tồn** — nếu in phiếu mà không có hàng để giao, ĐVVC tới lấy hụt ⇒ shop bị sàn phạt. Và bổ sung **"phiếu giao hàng" tự tạo** (`print_jobs.type='delivery'`) cho bước "Chuẩn bị hàng" khi chưa kéo được tem/AWB thật của sàn.

## 2. Trong / ngoài phạm vi
- **Trong:** đổi bảng map trạng thái (`config/integrations.{tiktok,lazada}.status_map`) — "đã arrange/in phiếu" → `processing` thay vì `ready_to_ship`; `TikTokStatusMap` bỏ ngoại lệ "AWAITING_SHIPMENT + có package → processing" (luôn `pending`); `ShipmentService::createForOrder` (= "Chuẩn bị hàng") nay (a) validate âm tồn, (b) đẩy đơn `pending → processing` (không phải `ready_to_ship`); `markPacked` (= "đã gói & quét") đẩy đơn `processing → ready_to_ship`; `handover` giữ nguyên (`ready_to_ship → shipped`, trừ tồn — cho đơn manual & override); `print_jobs.type='delivery'` + `PrintTemplates::deliverySlip` + `PrintService::renderDeliverySlip`; `OrderResource.out_of_stock` + filter `GET /orders?out_of_stock=1` + `stats.out_of_stock` + tab "⚠️ Hết hàng"; cột "Thao tác" trên danh sách đơn với nút theo trạng thái.
- **Ngoài (follow-up):** "luồng A — logistics của sàn" (gọi API sàn để arrange ship + kéo tem/AWB/hoá đơn thật: TikTok `/shipping_documents`, Lazada `/order/document/get`, Shopee `download_shipping_document`) — hiện "Chuẩn bị hàng" chỉ sinh phiếu giao hàng **tự tạo**; **lưu trữ phiếu 90 ngày** (`order_print_documents` + `expires_at` + prune job — spec riêng; hiện file giữ trên kho media qua `print_jobs`); phân bổ FIFO giữa các đơn cùng SKU (hiện chặn tất cả đơn chứa SKU âm tồn cho an toàn); trừ tồn ở bước bàn giao thủ công thay vì `shipped` (giữ nguyên — trừ ở `shipped`).

## 3. Luồng chính (đơn sàn — "luồng B/ĐVVC riêng" hiện tại)
1. Đơn về sàn ở "chờ xử lý" (TikTok `AWAITING_SHIPMENT`…) → `pending`.
2. **Tab "Chờ xử lý"** — chọn đơn, bấm **"Chuẩn bị hàng (lấy phiếu)"**: server validate đơn không âm tồn → tạo vận đơn (ĐVVC mặc định / `manual`) → tạo `print_job` `delivery` (phiếu giao hàng tự tạo; sau này: kéo tem/AWB thật của sàn) → đơn `pending → processing`. Đơn âm tồn ⇒ nút hiện "⚠ Hết hàng" (disable) / server trả `422`.
3. **Tab "Đang xử lý"** — gói hàng + quét đơn nội bộ; bấm **"Đã gói & sẵn sàng bàn giao"** (hoặc quét `/scan-pack`, hoặc "giao hàng thủ công"): vận đơn `created → packed`, đơn `processing → ready_to_ship`. (Chưa trừ tồn.) Có thể **"In lại phiếu giao hàng"**.
4. **Tab "Chờ bàn giao"** — bấm **"Bàn giao ĐVVC"** (hoặc quét `/scan-handover` — app; hoặc với đơn sàn: ĐVVC lấy hàng ⇒ sàn báo): vận đơn `packed → picked_up`, đơn `ready_to_ship → shipped`, **trừ tồn** (`order_ship`, idempotent).
5. Webhook/polling: `shipped → delivered → completed`; nhánh `delivery_failed`, `returning → returned_refunded`; **`cancelled`** (kể cả sau khi đã in phiếu) ⇒ **không quét được, không bấm bàn giao thủ công** (UI ẩn nút; service no-op/lỗi cho vận đơn đã huỷ).

## 4. Hành vi & quy tắc
- **Mapping** (config, không hard-code): "chưa in/arrange" → `pending` (TikTok `AWAITING_SHIPMENT`, Shopee `READY_TO_SHIP`, Lazada `pending`/`topack`); "đã in/arrange, sàn chờ ĐVVC" → `processing` (TikTok `AWAITING_COLLECTION`, Shopee `PROCESSED`, Lazada `packed`/`ready_to_ship`); `IN_TRANSIT/SHIPPED/shipped` → `shipped`; v.v. `ready_to_ship` (chuẩn) **chỉ đạt được bằng thao tác nội bộ của ta** (`markPacked`), không từ một raw status nào của sàn.
- **"Đơn hết hàng / âm tồn"** = có ≥1 dòng (`order_items.sku_id` ≠ null) mà SKU đó có `∑ on_hand − ∑ reserved < 0` trên mọi kho (đã đặt vượt tồn vật lý). `ShipmentService::createForOrder` ném `RuntimeException` (controller → `422`, key `order`) khi đơn âm tồn. `InventoryLedgerService::netStockForSku()` cung cấp số liệu (lưu ý `available_cached` bị kẹp `max(0,…)` nên không dùng được cho mục đích này).
- **`createForOrder`** (= "Chuẩn bị hàng"): idempotent (đã có vận đơn open ⇒ trả lại); chặn nếu đơn terminal / returning / **âm tồn**; tạo vận đơn qua `CarrierConnector`; lấy tem (best-effort — `manual` không có tem); đơn `pending → processing` (`OrderStatusSync::apply`, from = `[pending]` ⇒ đơn đã `processing` thì no-op).
- **`markPacked`**: vận đơn `created/pending → packed`; đơn `pending/processing → ready_to_ship`. Idempotent (đã packed/handed-over ⇒ no-op, chống quét trùng — `409`).
- **`handover`**: vận đơn → `picked_up`; đơn → `shipped`; trừ tồn. Idempotent.
- **Trừ tồn**: vẫn ở `shipped` (bàn giao thật / `scan-handover` / sàn báo `SHIPPED`). Reserve vẫn ở `pending`/`processing`.
- **Phân quyền**: `fulfillment.ship` (chuẩn bị hàng / bàn giao), `fulfillment.scan` (quét đóng gói), `fulfillment.print` (in tem/phiếu/hoá đơn).

## 5. Dữ liệu
- Không bảng/cột mới. `print_jobs.type` thêm giá trị `delivery` (cột chuỗi tự do). Đổi 4 dòng trong `config/integrations.php` (`tiktok.status_map` `AWAITING_COLLECTION`; `lazada.status_map` `topack`/`packed`/`ready_to_ship`).
- `OrderResource` thêm `out_of_stock` (bool, tính bằng query batched ở `OrderController::index`/`show`).

## 6. API & UI
- `GET /orders` & `/orders/stats`: filter `out_of_stock=1` (đơn có ≥1 SKU âm tồn); `OrderResource.out_of_stock`; `stats.out_of_stock`. (Trong `applyFilters`, `out_of_stock` được bỏ qua khi tính `by_status`/facet — như `has_issue` — để các tab khác hiển thị đúng số.)
- `POST /orders/{id}/ship` (= "Chuẩn bị hàng"): nay đẩy đơn `→ processing` (trước: `→ ready_to_ship`); `422` (key `order`) nếu đơn âm tồn.
- `POST /print-jobs`: `type` thêm `delivery` (phiếu giao hàng tự tạo, một trang/đơn: cửa hàng + mã đơn + người nhận + địa chỉ + mã vận đơn/ĐVVC + bảng hàng + COD + ghi chú).
- FE: bỏ 3 tab-bước riêng (đã làm ở rà SPEC-0009 trước đó). Cột **"Thao tác"** trên danh sách đơn (`OrderActions`): `pending` ⇒ "Chuẩn bị hàng (lấy phiếu)" (hoặc "⚠ Hết hàng" disable nếu `out_of_stock`); `processing` ⇒ "In phiếu giao hàng" / "Đã gói & sẵn sàng bàn giao" / "In tem sàn" (nếu có tem thật); `ready_to_ship` ⇒ "Bàn giao ĐVVC" / "In lại phiếu"; luôn ⇒ "In hoá đơn". Tab **"⚠️ Hết hàng"**. Nút "Quét đơn" (header) mở modal đóng gói/bàn giao.
- Cập nhật `docs/05-api/endpoints.md`, `docs/03-domain/order-status-state-machine.md`, `docs/04-channels/order-processing.md`.

## 7. Edge case & lỗi
- Đơn `processing` từ sàn (đã arrange trên app TikTok) **không có** `shipment` trong DB ⇒ "Đã gói & sẵn sàng bàn giao" tự tạo vận đơn (`createForOrder` — vẫn validate âm tồn) rồi `markPacked`.
- Đơn `cancelled` (kể cả sau khi in phiếu) ⇒ UI không hiện nút bàn giao/quét; `markPacked`/`handover`/`scan-*` trên vận đơn đã huỷ ⇒ lỗi/`409`.
- Quét trùng `/scan-pack` ⇒ `409`; mã không thấy ⇒ `404`.
- SKU nhiều kho: "âm tồn" tính trên tổng các kho; chặn **mọi** đơn chứa SKU đó (an toàn; phân bổ FIFO = follow-up).
- Sàn báo trạng thái "lùi" ⇒ vẫn ghi nhận + `has_issue` nếu bất thường (rule 1 state-machine doc).

## 8. Bảo mật & dữ liệu cá nhân
"Phiếu giao hàng" chứa tên/SĐT/địa chỉ người nhận (PII) — chỉ tạo được khi có `fulfillment.print`; file lưu trên kho media tenant (retention 90 ngày = follow-up).

## 9. Kiểm thử
- `FulfillmentTest`: ship → đơn `processing` (không `ready_to_ship`); `scan-pack`/`pack` → đơn `ready_to_ship`; `handover`/`scan-handover` → `shipped` + trừ tồn; **out_of_stock** chặn ship (`422`, key `order`) + `OrderResource.out_of_stock` + filter + `stats.out_of_stock`; tạo `print_job` `delivery` → `done`.
- `TikTokConnectorContractTest`/`LazadaConnectorContractTest`: `AWAITING_COLLECTION`/`packed`/`ready_to_ship` → `Processing`; `AWAITING_SHIPMENT` (kể cả có package) → `Pending`.
- `TikTokSyncTest`: webhook/polling map trạng thái mới đúng + ghi `order_status_history`.

## 10. Tiêu chí hoàn thành
- [x] Bảng map: "đã arrange/in phiếu" → `processing`; `ready_to_ship` chỉ từ thao tác nội bộ.
- [x] `createForOrder` → `processing` + validate âm tồn (`422`); `markPacked` → `ready_to_ship`.
- [x] `print_jobs.type='delivery'` + template + render + nút "Chuẩn bị hàng" sinh phiếu.
- [x] `out_of_stock`: resource + filter + stats + tab "⚠️ Hết hàng" + chặn nút trên UI.
- [x] Test cập nhật + bổ sung; tài liệu cập nhật (spec này, README, roadmap, state-machine, order-processing, endpoints).

## 11. Câu hỏi mở
- Khi wiring "luồng A": "Chuẩn bị hàng" có nên gọi sàn arrange-ship (đẩy TikTok → `AWAITING_COLLECTION`) ngay trong nút, hay tách 2 bước (arrange rồi mới in)? — hiện chỉ sinh phiếu tự tạo, không gọi sàn.
- Có nên cho ép "Chuẩn bị hàng" dù âm tồn (với cảnh báo) trong trường hợp đặc biệt? — hiện chặn cứng.
