# Giao hàng & In ấn

**Status:** Stable · **Cập nhật:** 2026-05-11

> Hai con đường giao hàng: **(A) Logistics của sàn** (đa số đơn TikTok/Shopee/Lazada — sàn chỉ định ĐVVC, ta gọi API sàn để "sắp xếp vận chuyển" rồi lấy label PDF) và **(B) ĐVVC riêng** (đơn manual, hoặc đơn sàn mà sàn cho tự xử lý — ta gọi thẳng API GHN/GHTK/J&T... qua `CarrierConnector`). In ấn = lấy/ghép PDF (vận đơn) + tự render (picking/packing list).

## 1. Trạng thái vận đơn (`shipments.status`)
`pending` (chưa tạo) → `created` (đã có tracking + label) → `packed` (đã in tem & đóng gói — *bước riêng, thêm ở SPEC-0009*) → `picked_up` (đã bàn giao ĐVVC) → `in_transit` → `delivered` | `failed` (giao hỏng) | `returned` | `cancelled`. Đồng bộ ngược về trạng thái đơn (xem state machine): `created`/`packed`⇒đơn `ready_to_ship`; `picked_up`/`in_transit`⇒`shipped`; `delivered`⇒`delivered`; `failed`⇒`delivery_failed`. **`packed` không trừ tồn** (hàng còn trong kho, chỉ đã đóng thùng); trừ tồn ở bước bàn giao (`picked_up`, đơn `shipped`). `shipments.print_count`/`last_printed_at` đếm số lần in tem (UI hiện "đã in N lần", từ lần 2 có popup xác nhận). Màn xử lý đơn ánh xạ 3 bước này: `prepare` (chưa có vận đơn / chưa in) → `pack` (vận đơn `created` đã in) → `handover` (vận đơn `packed`) — xem [SPEC-0009](../specs/0009-order-processing-screen.md), [`../04-channels/order-processing.md`](../04-channels/order-processing.md).

## 2. Luồng A — Logistics của sàn

```
1. Đơn ở processing, người bán bấm "Tạo vận đơn" (đơn lẻ hoặc hàng loạt)
2. connector.getShippingOptions(order)        → các phương án (pickup/dropoff, dịch vụ, ĐVVC sàn gán)
3. connector.arrangeShipment(ArrangeShipmentDTO)→ trả ShipmentDTO { tracking_no, carrier, package_no, label_ready? }
4. connector.getShippingDocument(ShippingDocQuery{ type: label, order/package })
   → BinaryFile (PDF) ⇒ lưu MinIO: tenants/{tenant}/labels/{shipment}.pdf ; shipments.label_url
5. shipments.status = created ; order → ready_to_ship ; order_status_history(source=system)
6. (tuỳ sàn) connector cũng cho lấy invoice/packing list của sàn — lưu kèm
```
- Một đơn có thể tách **nhiều kiện** (sàn trả nhiều `package_id`) ⇒ nhiều `shipments` cho một `order` ⇒ `order.is_split = true`. Mỗi kiện có label riêng.
- "Sắp xếp lại" (re-arrange) nếu hủy vận đơn cũ.

## 3. Luồng B — ĐVVC riêng (`CarrierConnector`)

```
1. Đơn (manual hoặc sàn cho tự ship) → chọn ĐVVC + dịch vụ (UI gợi ý qua carrier.quote())
2. carrier.createShipment(CreateShipmentDTO{ người gửi/nhận, kiện, COD, ghi chú, dịch vụ })
   → ShipmentDTO { tracking_no, ... }  ⇒ tạo shipments
3. carrier.getLabel(tracking_no, format A6/A5/PDF) → BinaryFile ⇒ lưu MinIO ; shipments.label_url
4. shipments.status = created ; cập nhật trạng thái đơn
5. carrier.getTracking(tracking_no) định kỳ HOẶC carrier.parseWebhook() nếu ĐVVC có webhook → cập nhật status
6. carrier.cancel(tracking_no) khi hủy
```
- `carrier_accounts` lưu credential ĐVVC (mã hoá) theo tenant. ĐVVC đợt 1: **GHN, GHTK, J&T Express**. Đợt 2: ViettelPost, NinjaVan, SPX/Shopee Express (nếu mở API), VNPost, Best Express, Ahamove/Grab (giao nhanh nội thành).
- Mỗi ĐVVC = một `CarrierConnector` (xem `extensibility-rules.md`). Thêm ĐVVC không sửa core.

## 4. In ấn

### 4.1 Vận đơn (shipping label)
- Nguồn: PDF từ sàn (luồng A) hoặc từ ĐVVC (luồng B). **Không tự vẽ lại label của ĐVVC** — phải dùng đúng file họ cấp (mã vạch chuẩn của ĐVVC).
- **In hàng loạt** (`PrintJob type=label`): user chọn N đơn/shipment → job `GenerateBulkLabel` (queue `labels`): đảm bảo mỗi shipment đã có label (chưa có thì tạo vận đơn trước), tải các PDF, **ghép** thành 1 file (sắp theo ĐVVC rồi theo mã đơn), lưu MinIO, set `print_jobs.file_url`+`status=done`. SPA nhận realtime/poll → tải về in.
- Hỗ trợ khổ A6 (máy in nhiệt tem) và A4 (4 tem/trang) — tuỳ chọn khi tạo print job.

### 4.2 Picking list (phiếu soạn hàng) — **tự render**
- `PrintJob type=picking` cho một nhóm đơn → gom **theo SKU**: "SKU X — tổng 12 cái — từ các đơn #...". Mục đích: nhân viên ra kho lấy hàng một lượt.
- Render: HTML (template) → **Gotenberg** → PDF.

### 4.3 Packing list (phiếu đóng gói) — **tự render**
- `PrintJob type=packing`: mỗi đơn một phiếu (mã đơn, người nhận, danh sách item, ghi chú) để bỏ vào kiện.

### 4.4 Template in tuỳ biến (`print_templates`)
- Lưu dạng dữ liệu: `paper_size`, `layout` (JSON mô tả các block: logo, mã vạch/QR, bảng item, vùng địa chỉ...), `logo_url`, `is_default`. Render bằng engine HTML → Gotenberg. Thêm/sửa mẫu **không cần deploy**.
- Mặc định có sẵn template picking & packing; người dùng clone & chỉnh.

## 5. Quét mã đóng gói (scan-to-pack / scan-to-ship)
- Màn "Đóng gói": người dùng quét **mã vạch trên vận đơn** (tracking no hoặc mã đơn) bằng máy quét → hệ thống:
  1. Tìm `shipment`/`order` tương ứng (trong tenant). Không thấy / sai trạng thái ⇒ báo lỗi rõ ràng.
  2. (Tuỳ chọn) yêu cầu quét tiếp barcode từng **SKU** trong đơn để xác nhận đủ/đúng hàng (chống nhầm).
  3. Khi đủ ⇒ đánh dấu shipment `packed`/`picked_up`, đơn → `shipped` (hoặc `ready_to_ship`→`shipped` khi bàn giao), **trừ tồn** (`order_ship` movements), ghi `order_status_history(source=user/system)`.
- Hỗ trợ chế độ "quét hàng loạt" (quẹt liên tục nhiều kiện). Có thể làm thành **PWA** ở Phase sau cho điện thoại làm máy quét.
- Cấu hình: trừ tồn ở bước "tạo vận đơn" hay "quét đóng gói" hay "đơn shipped theo sàn" — chọn theo nhu cầu nhà bán (mặc định: khi `shipped`).

## 6. Lô lấy hàng (Pickup Batch)
- Gom các shipment cùng gian hàng / cùng ĐVVC để bàn giao một lần; in danh sách bàn giao (mã đơn, tracking, COD) cho shipper ký nhận. Cập nhật `picked_up_at` hàng loạt.

## 7. Đối soát phí vận chuyển
- Lưu phí ship **ước tính** (lúc tạo vận đơn) và phí **thực tế** (từ settlement của sàn hoặc hoá đơn ĐVVC) ⇒ chênh lệch hiển thị ở báo cáo (module Finance).

## 8. Lưu trữ & in lại phiếu in của đơn — giữ 90 ngày  *(logic tính năng làm sau)*

> **Mục tiêu nghiệp vụ:** mỗi lần hệ thống sinh ra một phiếu in cho đơn (vận đơn / picking list / packing list / hoá đơn của sàn), file PDF đó được **lưu lại và truy ra được theo đơn** trong **90 ngày**, để nhà bán có thể **in lại** (khi mất tem, kẹt giấy, đổi máy in, đối chiếu...). Quá 90 ngày, file bị **dọn (purge)** để tiết kiệm dung lượng và tuân thủ tối thiểu hoá dữ liệu cá nhân; bản ghi metadata vẫn giữ ở dạng "đã hết hạn".
>
> Đây là phần **logic định hướng** — khi triển khai thì viết một spec đầy đủ trong `docs/specs/` (vd `NNNN-print-document-retention.md`) theo `docs/specs/_TEMPLATE.md`.

### 8.1 Dữ liệu
- **`print_jobs`** (đã có ở `docs/02-data-model/overview.md`) là nơi gốc của mọi file in được sinh ra: `type` (`label|picking|packing|channel_invoice`), `scope` (json: danh sách `order_id`/`shipment_id` mà file này phục vụ), `template_id?`, `file_url` (key trên MinIO/S3), `file_size?`, `status`, `error`, `created_by`, `created_at`. Bổ sung khi làm tính năng này:
  - `expires_at` = `created_at + 90 ngày` (cấu hình được — xem 8.5).
  - `purged_at` (nullable) — thời điểm file đã bị xoá khỏi object storage; sau khi purge, `file_url` được coi là không còn dùng được.
  - `meta` (json) — vd số trang, khổ giấy, danh sách tracking trong file.
- **`order_print_documents`** (bảng nối tiện tra cứu nhanh "đơn này có những phiếu in nào") — `tenant_id`, `order_id`, `print_job_id`, `type`, `shipment_id?`, `created_at`. Khi một `print_job` bao N đơn (in hàng loạt) ⇒ tạo N dòng ở đây. Giúp màn chi tiết đơn / màn đơn hàng hiển thị nút "In lại tem / In lại phiếu" mà không phải quét toàn bộ `print_jobs.scope`.
  - (Phương án thay thế tối giản: không có bảng nối, query `print_jobs` theo `scope @> '[{"order_id": X}]'` với GIN index trên `scope`. Bảng nối rõ ràng hơn và rẻ hơn cho list view — chọn ở spec.)
- File vẫn lưu theo prefix `tenants/{tenant_id}/print/{yyyy}/{mm}/{print_job_id}.pdf` trên MinIO/S3 (xem `docs/08-security-and-privacy.md`).
- Lưu ý: **label do ĐVVC/sàn cấp** thì `shipments.label_url` cũng trỏ tới file đã lưu — đó cũng là một "print document"; khi sinh `print_job type=label` ta tải bytes từ nguồn (sàn/ĐVVC) một lần rồi lưu, không gọi lại API sàn mỗi lần in lại (xem 8.4).

### 8.2 Luồng sinh phiếu (nhắc lại, đã mô tả ở §4) — điểm bổ sung
Khi job `GenerateBulkLabel` / `GeneratePickingList` / `GeneratePackingList` hoàn tất:
1. Lưu file kết quả lên object storage ⇒ set `print_jobs.file_url`, `file_size`, `status=done`, `expires_at = now + 90d`.
2. Với mỗi `order_id` (và `shipment_id` nếu có) trong `scope` ⇒ upsert một dòng `order_print_documents`.
3. Fire event `PrintDocumentGenerated(print_job)` (để UI realtime cập nhật + có thể đính kèm vào audit).

### 8.3 In lại (re-print)
- API: `POST /api/v1/orders/{id}/print` (chọn `type=label|picking|packing`) và `GET /api/v1/print-jobs/{id}/download`. Trên UI: ở chi tiết đơn và ở list đơn (bulk) có "In lại".
- Khi yêu cầu in lại cho một đơn:
  1. Tìm `order_print_documents` của đơn theo `type`, lấy `print_job` **mới nhất chưa `purged_at`**.
  2. Nếu có và `status=done` ⇒ trả **signed URL** tới `file_url` (hết hạn ngắn, vd 5–15 phút) — KHÔNG sinh lại file. Đây là điểm chính: "in lại" = lấy lại đúng file đã in lần đầu.
  3. Nếu **không có** (chưa từng in, hoặc đã quá 90 ngày và bị purge) ⇒ tuỳ `type`:
     - `picking`/`packing` ⇒ **sinh lại** từ dữ liệu đơn hiện tại (template hiện hành) — chấp nhận khác bản cũ vì đây là phiếu nội bộ; tạo `print_job` mới + `order_print_documents` mới.
     - `label` (vận đơn của sàn/ĐVVC) ⇒ **không tự sinh lại** (mã vạch là của bên thứ ba). Nếu vận đơn còn hiệu lực ⇒ gọi lại connector `getShippingDocument()` / carrier `getLabel()` để tải lại bytes, lưu, tạo `print_job` mới. Nếu vận đơn đã huỷ/hết hạn ⇒ báo rõ "phải tạo vận đơn mới".
  4. In hàng loạt: gom các đơn được chọn, ưu tiên dùng lại file còn hạn; với đơn không còn file thì sinh/lấy lại; ghép thành một file in mới (tạo `print_job type=label` mới bao các đơn đó). Bản chất "in lại hàng loạt" có thể tạo ra `print_job` mới — vẫn ổn, mỗi lần in là một bản ghi.
- Mọi lần in/in lại ghi `audit_logs` (`fulfillment.print` / `fulfillment.reprint`, kèm `order_id`, `print_job_id`).

### 8.4 Dọn dẹp (purge sau 90 ngày)
- Job định kỳ `PrunePrintDocuments` (hằng ngày, queue `default` hoặc `labels`): tìm `print_jobs` có `expires_at < now` và `purged_at IS NULL` ⇒ xoá object trên storage ⇒ set `purged_at = now`, `file_url` giữ nguyên nhưng coi như không tải được (hoặc nullable hoá, tuỳ spec). Giữ lại bản ghi metadata (loại, thời điểm in, ai in, số đơn) cho mục đích thống kê/audit — đó là dữ liệu **không định danh** người mua.
- Đơn chưa giao xong (chưa `delivered`/`completed`) thì **không purge** label của nó dù quá 90 ngày kể từ lúc in (vì có thể cần in lại để giao tiếp); tính 90 ngày từ `delivered_at`/`completed_at` cho `type=label`, từ `created_at` cho `picking/packing`. (Chốt công thức chính xác ở spec.)
- Job phải **idempotent** và xử lý lỗi xoá storage (retry; không set `purged_at` nếu xoá thất bại).

### 8.5 Cấu hình
- `config/integrations.php` hoặc `config/fulfillment.php` (tạo khi làm): `print.retention_days` (mặc định `90`), `print.signed_url_ttl_minutes` (mặc định `15`), `print.label_paper_size` mặc định, bật/tắt `order_print_documents` (nếu chọn phương án query trực tiếp).
- Có thể cho tenant cấu hình riêng số ngày giữ (trong giới hạn cho phép) ở `tenant_settings`.

### 8.6 Bảo mật & dữ liệu cá nhân
- File vận đơn/packing list chứa **tên, SĐT, địa chỉ người mua** ⇒ thuộc diện PII (xem `docs/08-security-and-privacy.md`). Quy tắc 90 ngày chính là một biện pháp tối thiểu hoá: không giữ PII trong file lâu hơn nhu cầu vận hành.
- Khi nhận webhook `data_deletion` của sàn, hoặc khi nhà bán ngắt kết nối gian hàng, hoặc khi đơn bị ẩn danh hoá ⇒ **purge ngay** các `print_jobs`/`order_print_documents` liên quan tới đơn đó, không chờ hết 90 ngày.
- Tải file luôn qua **signed URL** ngắn hạn, kiểm `tenant_id` + quyền (`fulfillment.print` / `fulfillment.view`) trước khi cấp; không bao giờ trả URL công khai vĩnh viễn.
- Mọi truy cập tải phiếu (có PII) có thể bật ghi audit.

## 9. RULES
1. Không bao giờ tự chế lại label của ĐVVC — dùng đúng file họ cấp.
2. Mọi lần tạo/hủy vận đơn ghi audit + cập nhật `shipments` + đồng bộ trạng thái đơn.
3. Sinh PDF luôn chạy ở job (queue `labels`), không trong request HTTP.
4. In hàng loạt phải có **progress** + cho **tải lại** file đã sinh; lỗi từng đơn ⇒ báo rõ đơn nào lỗi, không chặn cả batch.
5. Quét đóng gói phải **chống nhầm tenant** và **chống quét trùng** (cùng kiện quét 2 lần ⇒ no-op + thông báo).
6. Phiếu in của đơn được lưu & **in lại được trong 90 ngày** (cấu hình); "in lại" ưu tiên trả đúng file đã sinh (signed URL), không sinh mới; quá hạn ⇒ job `PrunePrintDocuments` xoá file, giữ metadata. Label của ĐVVC/sàn không tự sinh lại — nếu hết file thì tải lại từ nguồn hoặc yêu cầu tạo vận đơn mới. Purge ngay khi có yêu cầu xoá dữ liệu / ngắt kết nối / đơn bị ẩn danh hoá.
