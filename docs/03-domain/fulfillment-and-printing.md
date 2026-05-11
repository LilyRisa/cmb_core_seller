# Giao hàng & In ấn

**Status:** Stable · **Cập nhật:** 2026-05-11

> Hai con đường giao hàng: **(A) Logistics của sàn** (đa số đơn TikTok/Shopee/Lazada — sàn chỉ định ĐVVC, ta gọi API sàn để "sắp xếp vận chuyển" rồi lấy label PDF) và **(B) ĐVVC riêng** (đơn manual, hoặc đơn sàn mà sàn cho tự xử lý — ta gọi thẳng API GHN/GHTK/J&T... qua `CarrierConnector`). In ấn = lấy/ghép PDF (vận đơn) + tự render (picking/packing list).

## 1. Trạng thái vận đơn (`shipments.status`)
`pending` (chưa tạo) → `created` (đã có tracking + label) → `picked_up` (đã bàn giao ĐVVC) → `in_transit` → `delivered` | `failed` (giao hỏng) | `returned` | `cancelled`. Đồng bộ ngược về trạng thái đơn (xem state machine): `created`⇒đơn `ready_to_ship`; `picked_up`/`in_transit`⇒`shipped`; `delivered`⇒`delivered`; `failed`⇒`delivery_failed`.

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

## 8. RULES
1. Không bao giờ tự chế lại label của ĐVVC — dùng đúng file họ cấp.
2. Mọi lần tạo/hủy vận đơn ghi audit + cập nhật `shipments` + đồng bộ trạng thái đơn.
3. Sinh PDF luôn chạy ở job (queue `labels`), không trong request HTTP.
4. In hàng loạt phải có **progress** + cho **tải lại** file đã sinh; lỗi từng đơn ⇒ báo rõ đơn nào lỗi, không chặn cả batch.
5. Quét đóng gói phải **chống nhầm tenant** và **chống quét trùng** (cùng kiện quét 2 lần ⇒ no-op + thông báo).
