# SPEC 0012: Lợi nhuận ước tính sau phí sàn + cài đặt giá vốn + in hoá đơn đơn hàng

- **Trạng thái:** Implemented
- **Phase:** 2–6 (mở rộng SPEC-0003 / SPEC-0006 / SPEC-0010; là một slice của SPEC-0007 "cài đặt đơn hàng")
- **Module backend liên quan:** Orders, Inventory, Fulfillment
- **Tác giả / Ngày:** đội phát triển · 2026-05-12
- **Liên quan:** SPEC-0003 (SKU/giá vốn/đơn thủ công), SPEC-0004 (liên kết SKU nhanh), SPEC-0006 (in), SPEC-0010 (phiếu nhập kho + giá vốn bình quân), doc `03-domain/manual-orders-and-finance.md`

## 1. Vấn đề & mục tiêu
Người bán muốn biết **lợi nhuận ước tính sau phí sàn** ngay trên danh sách / chi tiết đơn — không chờ tới đối soát tài chính (Phase 6). Cần: (a) cấu hình % phí sàn theo từng nền tảng; (b) chọn cách tính giá vốn cho mỗi SKU (giá vốn **bình quân** gia quyền, hay đơn giá **lô nhập kho gần nhất**); (c) phiếu nhập kho nhập kèm đơn giá vốn của lô. Ngoài ra: bổ sung **nghiệp vụ in hoá đơn / phiếu đơn hàng** (một trang/đơn) — trước đây mới có in tem / picking / packing.

Đồng thời sửa lỗi: sau khi "liên kết SKU nhanh" từ đơn, các đơn khác có cùng SKU sàn không tự ghép — vì việc re-resolve chạy bất đồng bộ qua queue.

## 2. Trong / ngoài phạm vi của spec này
- **Trong:** `tenant.settings.platform_fee_pct` (map nền tảng→%); `skus.cost_method` (`average` | `latest`) + `skus.last_receipt_cost`; cập nhật giá vốn bình quân + `last_receipt_cost` khi xác nhận phiếu nhập kho; trường `profit` trên `OrderResource`; trang `/settings/orders`; hiển thị lợi nhuận ở danh sách & chi tiết đơn; chọn `cost_method` ở form SKU; loại `print_jobs.type = invoice` + template hoá đơn; sửa `linkFromOrders` chạy đồng bộ.
- **Ngoài (làm sau):** phí ship thực tế / phí thanh toán / hoa hồng theo từng danh mục ngành hàng; FIFO cost layers; đối soát đơn ↔ tiền về (Phase 6 Finance); template in tuỳ biến; lợi nhuận theo kỳ / báo cáo.

## 3. Câu chuyện người dùng / luồng chính
1. **Cài đặt → Cài đặt đơn hàng:** đặt % phí sàn cho TikTok / Shopee / Lazada / Đơn thủ công → lưu (`PATCH /tenant` merge vào `settings.platform_fee_pct`).
2. **Tạo / sửa SKU:** chọn "Cách tính giá vốn" = Bình quân (mặc định) hoặc Lô nhập gần nhất; nhập "Giá vốn".
3. **Nhập kho (phiếu nhập kho):** mỗi dòng nhập kèm "đơn giá vốn lô" (`unit_cost`); khi **Xác nhận** phiếu → ghi tồn + cập nhật giá vốn bình quân của SKU theo công thức gia quyền, và lưu `last_receipt_cost`.
4. **Đơn hàng (list & chi tiết):** mỗi đơn hiển thị "Lợi nhuận ƯT" = `tổng tiền − phí sàn − phí vận chuyển − giá vốn hàng`; nếu có dòng chưa có giá vốn SKU → đánh dấu ⚠ "ước tính, thiếu giá vốn".
5. **In hoá đơn:** từ chi tiết đơn bấm "In hoá đơn" → tạo `print_job` loại `invoice` → hệ thống render PDF (một trang/đơn: tên cửa hàng, mã đơn/ngày, khách & địa chỉ giao, bảng hàng + tiền, COD, ghi chú) → tải về.
6. **Liên kết SKU nhanh:** sau khi map, mọi đơn còn dòng chưa ghép được re-resolve **ngay trong request** → phản hồi trả về `orders_resolved`.

## 4. Hành vi & quy tắc nghiệp vụ
- **Phí sàn:** `platform_fee = round(grand_total × pct/100)` với `pct = settings.platform_fee_pct[order.source] ?? 0`. Lưu `settings` là **merge** (không ghi đè các key khác). Quyền sửa: `tenant.settings` (owner/admin).
- **Giá vốn hiệu lực của SKU (`effectiveCost`)**: `cost_method = latest` và có `last_receipt_cost` ⇒ dùng `last_receipt_cost`; ngược lại dùng `cost_price`. `cost_price` chính là giá vốn **bình quân gia quyền** hiện tại (đã có từ SPEC-0010 ở mức kho; mức SKU = bình quân theo `on_hand` các kho, làm tròn).
- **COGS của đơn** = `Σ effectiveCost(sku) × max(1, quantity)` trên các dòng đã có `sku_id`. `cost_complete = true` ⟺ đơn có dòng **và** mọi dòng đều có `sku_id` với `effectiveCost > 0`.
- **Lợi nhuận ước tính** = `grand_total − platform_fee − shipping_fee − cogs` (số nguyên VND, có thể âm).
- **Cập nhật giá vốn khi nhập kho** (`recordReceiptCost`): cập nhật `cost_price` mức kho (bình quân gia quyền với lô vừa nhập), rồi `skus.cost_price = bình quân theo on_hand các kho`, `skus.last_receipt_cost = đơn giá lô vừa nhập`. Chỉ chạy khi `unit_cost > 0`. Idempotency: nằm trong giao dịch "xác nhận phiếu" (đã idempotent ở SPEC-0010 — xác nhận lại không double-apply).
- **In hoá đơn:** `print_jobs.type = 'invoice'` (`type` là chuỗi tự do, không migration). Render qua Gotenberg (queue `labels`), một `.box`/trang, ngắt trang giữa các đơn. Quyền: `fulfillment.print`.
- **Liên kết SKU nhanh (sửa lỗi):** `POST /orders/link-skus` sau khi tạo listing+mapping sẽ duyệt **đồng bộ** mọi đơn `source != manual` còn dòng `sku_id` null và gọi `OrderInventoryService::apply()` (resolve sku_id / reserve / clear has_issue / phát `InventoryChanged` → `PushStockForSku` debounce) — idempotent vì ledger dedupe theo `(order_item, sku, type)`.

## 5. Dữ liệu
- `skus.cost_method` `string(16)` default `average`; `skus.last_receipt_cost` `bigint` nullable. Migration reversible (`2026_05_20_100001_add_cost_method_to_skus`).
- `tenants.settings` (JSON, đã có): thêm key `platform_fee_pct: { tiktok: number, shopee: number, lazada: number, manual: number }`.
- Không bảng mới cho `print_jobs` (loại `invoice` dùng cột `type` sẵn có).
- Không event mới (`linkFromOrders` dùng lại đường `InventoryChanged`).

## 6. API & UI
- `GET /orders`, `GET /orders/{id}`: thêm `profit: { cogs, platform_fee, shipping_fee, estimated_profit, platform_fee_pct, cost_complete } | null` (null nếu chưa cấu hình phí sàn). `index` tính theo 1 query batched (items) + 1 query (giá vốn SKU); `show` theo items đã nạp.
- `POST /skus`, `PATCH /skus/{id}`: nhận thêm `cost_method` (`in:average,latest`). `GET /skus*`: resource thêm `cost_method`, `last_receipt_cost`, `effective_cost`.
- `POST /print-jobs`: `type` mở rộng `in:label,picking,packing,invoice`.
- `POST /orders/link-skus`: phản hồi thêm `orders_resolved`.
- FE: trang `/settings/orders` (InputNumber % theo nền tảng, không dùng Select); cột "Lợi nhuận ƯT" ở danh sách đơn + khối lợi nhuận ở chi tiết đơn (`OrderDetailBody`); `Radio.Group` "Cách tính giá vốn" ở form SKU; nút "In hoá đơn" + `PrintJobBar` ở chi tiết đơn; form phiếu nhập kho đã có cột "Đơn giá vốn lô" (`unit_cost`).
- Cập nhật `05-api/endpoints.md` (profit, cost_method, invoice).

## 7. Edge case & lỗi
- Chưa cấu hình phí sàn cho nền tảng ⇒ `pct = 0` ⇒ `platform_fee = 0` (vẫn trả `profit`, `platform_fee_pct = 0`).
- Đơn chưa ghép SKU / dòng "quick product" không có `sku_id` ⇒ `cost_complete = false`, COGS bỏ qua dòng đó; UI cảnh báo ⚠.
- SKU chưa từng nhập kho ⇒ `last_receipt_cost = null` ⇒ `effectiveCost` rơi về `cost_price` kể cả khi `cost_method = latest`.
- In hoá đơn khi không còn đơn hợp lệ (đã xoá) ⇒ job `error` "Không có đơn nào để in."; Gotenberg lỗi ⇒ job `error`, người dùng thấy thông báo.
- `linkFromOrders` với nhiều đơn ⇒ chạy tuần tự trong request; vẫn idempotent. (Khối lượng lớn = follow-up: đẩy phần dư sang queue nếu cần.)

## 8. Bảo mật & dữ liệu cá nhân
Hoá đơn in chứa tên/SĐT/địa chỉ người nhận (PII) — chỉ tạo được khi có quyền `fulfillment.print`; file lưu trên storage tenant theo cơ chế của SPEC-0006 (retention 90 ngày = follow-up). Giá vốn / lợi nhuận chỉ trả cho người xem đơn (`orders.view`).

## 9. Kiểm thử
- Feature `OrderApiTest`: `GET /orders` & `GET /orders/{id}` trả `profit` đúng (`platform_fee = round(total×%)`, `cost_complete=false` khi chưa ghép SKU, `estimated_profit` đúng); tạo `print_job` loại `invoice` → `done`, meta `orders`.
- Feature `InventoryApiTest`: tạo SKU với `cost_method=latest` → resource có `cost_method`, `effective_cost` rơi về `cost_price` khi chưa nhập kho.
- (Đã có) `LinkOrderSkusTest`: link SKU nhanh resolve cả các đơn cùng SKU sàn ngay sau request — nay dùng đường đồng bộ.
- (Đã có) `WarehouseDocumentsTest`: xác nhận phiếu nhập kho cập nhật `cost_price` bình quân.

## 10. Tiêu chí hoàn thành
- [x] `skus.cost_method` / `last_receipt_cost` + cập nhật khi xác nhận phiếu nhập kho.
- [x] `tenant.settings.platform_fee_pct` + trang `/settings/orders`.
- [x] `profit` trên `OrderResource` + hiển thị ở danh sách & chi tiết đơn.
- [x] `print_jobs.type = invoice` + template hoá đơn + nút in.
- [x] `linkFromOrders` re-resolve đồng bộ (sửa lỗi đơn cùng SKU không tự ghép).
- [x] Tài liệu cập nhật (spec này, README sổ spec, roadmap, `05-api/endpoints.md`).

## 11. Câu hỏi mở
- Có nên cho đặt % phí sàn theo từng gian hàng (channel_account) thay vì theo nền tảng? — hiện theo nền tảng cho đơn giản.
- Lợi nhuận có nên trừ thêm phí cố định/đơn (đóng gói, vận hành)? — để Phase 6 Finance.
