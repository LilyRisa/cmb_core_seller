# SPEC 0004: Tồn kho thủ công hàng loạt + Liên kết SKU nhanh từ đơn

- **Trạng thái:** Implemented (2026-05-15 — Phase 2 mở rộng; "phiếu có header + duyệt" để Phase 5)
- **Phase:** 2 *(mở rộng SPEC-0003; phần "phiếu nhập/xuất có header + duyệt" để Phase 5)*
- **Module backend liên quan:** Inventory (chính), Orders, Products, Channels
- **Tác giả / Ngày:** Team · 2026-05-15
- **Liên quan:** SPEC-0003 (sản phẩm/SKU/tồn kho lõi), docs `03-domain/inventory-and-sku-mapping.md`, `06-frontend/orders-filter-panel.md`.

## 1. Vấn đề & mục tiêu
Sau SPEC-0003, NV phải điều chỉnh tồn **từng SKU một** (`POST /inventory/adjust`) và **đẩy tồn** chỉ tự động (debounce qua `InventoryChanged`). Đơn về mà SKU chưa ghép thì chỉ có cờ `has_issue` — không có cách sửa nhanh. SPEC này thêm 3 thao tác hàng loạt, theo đúng nếp "1 nguồn sự thật + mọi thay đổi tồn có dòng sổ cái":
1. **Phiếu nhập/xuất kho thủ công hàng loạt** — một lần nhập nhiều dòng `(SKU, số lượng)` vào/ra một kho (nhập đầu kỳ, kiểm kê thô, xuất huỷ…).
2. **Đẩy tồn thủ công hàng loạt theo bộ chọn** — chọn nhiều SKU rồi "đẩy tồn lên sàn ngay" (không chờ debounce) — dùng khi đối soát lệch hoặc sau khi nhập kho lớn.
3. **Liên kết SKU nhanh từ đơn** — đơn chưa ghép SKU hiển thị **thông báo đỏ + link "Liên kết SKU"**; bấm vào ⇒ modal liên kết nhanh; hoặc **tích chọn hàng loạt đơn** ở danh sách rồi bấm "Liên kết SKU" — hệ thống **gộp các SKU sàn giống nhau** (cùng `channel_account_id` + `external_sku_id`/`seller_sku`) để chỉ chọn master SKU **một lần cho mỗi SKU sàn**, tạo mapping, **tự áp lại tồn** (re-resolve `order_items.sku_id`, reserve, clear `has_issue`) và **tự đẩy tồn**; UI tự tải lại tồn kho + đơn.

## 2. Trong / ngoài phạm vi
**Trong:** 3 endpoint + UI như §6. Tận dụng `InventoryLedgerService` (adjust/receipt), `SkuMappingService` (setMapping/autoMatch), `OrderInventoryService` (apply) đã có; không bảng mới.
**Ngoài (Phase 5 — WMS đầy đủ):** "phiếu" thật có header (`goods_receipts`/`stock_takes`/`stock_transfers` + items + trạng thái draft→confirmed + người duyệt), điều chuyển kho nhiều kho, giá vốn FIFO/`cost_layers`. Bulk-adjust ở SPEC này là thao tác **áp ngay** (không draft, không duyệt) — chỉ ghi `inventory_movements`; nâng cấp lên "phiếu có header" ở Phase 5 sẽ thêm bảng + tham chiếu các movement vào phiếu.

## 3. Luồng chính
### 3.1 Nhập/xuất hàng loạt
SPA → Tồn kho → "Nhập/xuất hàng loạt" → chọn **loại** (`Nhập kho` ⇒ `goods_receipt`, `Điều chỉnh tay (±)` ⇒ `manual_adjust`), kho (mặc định kho mặc định tenant), ghi chú, thêm nhiều dòng `(SKU picker, số lượng)` (số lượng dương cho nhập; ±n cho điều chỉnh) → "Áp" ⇒ `POST /inventory/bulk-adjust` ⇒ mỗi dòng → một movement (`type` = loại, `note` = ghi chú phiếu, `ref_type='manual_bulk'`) ⇒ `available` tính lại ⇒ `InventoryChanged` ⇒ debounce push. UI tải lại bảng tồn + sổ cái.

### 3.2 Đẩy tồn hàng loạt theo bộ chọn
SPA → Tồn kho → tab "Danh mục SKU" → tích chọn nhiều SKU → "Đẩy tồn lên sàn" ⇒ `POST /inventory/push-stock { sku_ids }` ⇒ với mỗi SKU dispatch `PushStockForSku` **ngay** (không delay) ⇒ tính `available` tổng → đẩy lên các `channel_listing` ghép nếu khác `channel_stock`. UI báo "Đã yêu cầu đẩy tồn N SKU".

### 3.3 Liên kết SKU nhanh từ đơn
- **Cờ đỏ**: đơn có `issue_reason='SKU chưa ghép'` ⇒ ở list đơn, dưới mã đơn hiện tag đỏ "Chưa liên kết SKU" + link "Liên kết"; trên `OrderDetailPage` hiện `Alert` đỏ + nút "Liên kết SKU". Danh sách đơn còn có **banner**: "Có N đơn chưa liên kết SKU — [Liên kết hàng loạt]".
- **Modal liên kết** (mở từ 1 đơn, hoặc từ nhiều đơn đã tích chọn, hoặc từ banner = mọi đơn chưa ghép):
  1. `GET /orders/unmapped-skus?order_ids=…` (bỏ trống = mọi đơn chưa ghép của tenant) ⇒ trả **danh sách SKU sàn distinct** đã gộp: mỗi mục `{ channel_account_id, channel_account_name, external_sku_id, seller_sku, sample_name, order_count, item_count, existing_listing_id?, suggested_sku_id? }` (`suggested_sku_id` = SKU có `sku_code` chuẩn-hoá trùng `seller_sku`, nếu có).
  2. Mỗi mục → một `SkuPicker` (tìm master SKU theo code/tên/barcode), gợi ý sẵn `suggested_sku_id`.
  3. "Liên kết" ⇒ `POST /orders/link-skus { links:[{ channel_account_id, external_sku_id, seller_sku?, sku_id }] }`:
     - với mỗi link: `channel_listing` `firstOrCreate` theo `(channel_account_id, external_sku_id)` (tạo từ dữ liệu `order_item`: `seller_sku`, `title=sample_name` nếu chưa có) → `SkuMappingService::setMapping(single×1)` → gom các order id có item khớp SKU sàn này → cuối cùng với mỗi order ảnh hưởng: fire `OrderUpserted` ⇒ listener Inventory re-resolve `order_items.sku_id`, reserve tồn, clear `has_issue`, phát `InventoryChanged` ⇒ push tồn.
     - trả `{ linked: <số link>, listings_created: <n>, orders_resolved: <n> }`.
  4. UI: invalidate `orders` / `inventory-levels` / `skus` / `channel-listings` (≈ "tự tải lại tồn kho để xử lý").

## 4. Hành vi & quy tắc
- **Bulk-adjust**: KHÔNG idempotent (thao tác có chủ đích, có confirm); `goods_receipt` ⇒ mọi `qty_change` phải > 0; `manual_adjust` ⇒ ≠ 0. Mỗi dòng vẫn đi qua `InventoryLedgerService` (transaction + lock + movement + balance_after + `InventoryChanged`). SKU không thuộc tenant ⇒ `422`. Dòng trùng SKU trong một phiếu ⇒ cộng dồn? **Không** — báo `422` (người dùng gộp tay) để sổ cái rõ ràng.
- **Bulk-push**: chỉ dispatch job (không chặn request); SKU không thuộc tenant ⇒ bỏ qua (đếm `queued` là số dispatch thật). Listing `is_stock_locked` ⇒ `PushStockForSku` tự bỏ qua như cũ.
- **link-skus**: gộp theo `(channel_account_id, external_sku_id)` (fallback `seller_sku` khi `external_sku_id` rỗng). Tạo `channel_listing` tối thiểu nếu chưa có (để mapping bám vào + đơn tương lai + push tồn hoạt động). `setMapping` thay thế mapping cũ của listing đó (cho phép sửa lại). Re-resolve **mọi** đơn có item khớp SKU sàn vừa link (không chỉ đơn trong `order_ids`) — vì mapping có hiệu lực toàn cục. Idempotent: chạy lại = không trừ tồn lặp (ledger dedupe per `(order_item,sku,type)`).
- **Phân quyền**: `inventory.adjust` cho bulk-adjust; `inventory.map` cho bulk-push + link-skus + unmapped-skus. (`orders.view` cũng cần để `unmapped-skus` đọc đơn — endpoint kiểm cả hai.)

## 5. Dữ liệu
Không bảng/cột mới. `inventory_movements` thêm giá trị `ref_type='manual_bulk'` (đã là cột string tự do). `/orders/stats` thêm field đếm `unmapped` (= số đơn `issue_reason='SKU chưa ghép'`, cùng base với `has_issue`). Domain event: dùng lại `InventoryChanged`, `OrderUpserted` (không event mới).

## 6. API & UI
**Endpoint mới** (cập nhật `05-api/endpoints.md`):
- `POST /api/v1/inventory/bulk-adjust` (`inventory.adjust`) `{ kind: 'goods_receipt'|'manual_adjust', warehouse_id?: int, note?: string, lines:[{ sku_id:int, qty_change:int }] }` ⇒ `201 { data:{ applied:N, movements:[InventoryMovementResource] } }`.
- `POST /api/v1/inventory/push-stock` (`inventory.map`) `{ sku_ids:[int] }` ⇒ `{ data:{ queued:N } }`.
- `GET /api/v1/orders/unmapped-skus` (`orders.view`+`inventory.map`) query `order_ids?` (csv) ⇒ `{ data:[{ channel_account_id, channel_account_name, external_sku_id, seller_sku, sample_name, order_count, item_count, existing_listing_id, suggested_sku_id }] }`.
- `POST /api/v1/orders/link-skus` (`inventory.map`) `{ links:[{ channel_account_id:int, external_sku_id?:string, seller_sku?:string, sku_id:int }] }` ⇒ `{ data:{ linked:N, listings_created:N, orders_resolved:N } }`.

**FE** (cập nhật `06-frontend/overview.md` / `orders-filter-panel.md`):
- `OrdersPage`: cột "Đơn hàng" hiện tag đỏ "Chưa liên kết SKU" + link "Liên kết" cho đơn `issue_reason='SKU chưa ghép'`; banner đỏ trên bảng "Có N đơn chưa liên kết SKU — [Liên kết hàng loạt]" (N từ `stats.unmapped`); `rowSelection` (checkbox) ⇒ nút "Liên kết SKU (n)" mở `<LinkSkusModal order_ids={selected}>`.
- `OrderDetailPage`: `Alert` đỏ + nút "Liên kết SKU" ⇒ `<LinkSkusModal order_ids={[id]}>`.
- `<LinkSkusModal order_ids?>` (component dùng chung): gọi `useUnmappedSkus`, mỗi SKU sàn → 1 `SkuPicker` (gợi ý `suggested_sku_id`), "Liên kết" ⇒ `useLinkOrderSkus`; onSuccess invalidate `orders`/`inventory-levels`/`skus`/`channel-listings` + đóng modal + toast.
- `InventoryPage`: tab "Danh mục SKU" — `rowSelection` ⇒ nút "Đẩy tồn lên sàn (n)" (`useBulkPushStock`); nút "Nhập/xuất hàng loạt" ⇒ modal `Form.List` các dòng (SKU + số lượng) + chọn loại + ghi chú + kho (`useBulkAdjustStock`).

Không thêm logic theo tên sàn — `link-skus` chỉ dùng dữ liệu `order_items`/`channel_listings` chung; tạo listing tối thiểu là dữ liệu nội bộ (`fetchListings` ở SPEC-0003 sẽ làm giàu sau).

## 7. Edge case
- Đơn manual (`source=manual`, không `channel_account_id`) chưa-ghép-SKU **không** xuất hiện ở `unmapped-skus` (đơn manual luôn có `sku_id` khi tạo) — nếu có cách nào để `sku_id` null (vd SKU bị xoá) thì hiển thị riêng "đơn lỗi", không gộp.
- `external_sku_id` rỗng cho một item ⇒ dùng `seller_sku` làm khoá; nếu cả hai rỗng ⇒ bỏ qua item đó (không gộp được).
- Sau khi link mà vẫn còn item chưa khớp (vì link sai SKU) ⇒ đơn vẫn `has_issue` → mở lại modal sửa.
- `bulk-adjust` một dòng làm tồn âm (xuất quá) ⇒ vẫn áp + cờ `is_negative` + `available`=0 (như `adjust` đơn lẻ).
- Gộp một SKU sàn có >50 đơn ⇒ re-resolve theo chunk; phản hồi `orders_resolved` là tổng.

## 8. Bảo mật & dữ liệu cá nhân
Không PII mới. Bulk-adjust/push là thao tác nội bộ; ghi log thao tác (`note`/`created_by` trên movement). `link-skus` không lộ dữ liệu khách.

## 9. Kiểm thử
- **Feature `BulkInventoryTest`**: `bulk-adjust` (goods_receipt nhiều dòng → N movements + balance_after đúng; `manual_adjust` ±; qty=0 hoặc trùng SKU → 422; SKU tenant khác → 422); `push-stock` (dispatch `PushStockForSku` đúng số SKU; SKU tenant khác bị bỏ); phân quyền viewer 403.
- **Feature `LinkOrderSkusTest`**: tạo 2 đơn TikTok có cùng `external_sku_id`/`seller_sku` (chưa ghép) + 1 đơn khác SKU → `unmapped-skus` gộp về **1** mục `order_count=2`; có `Sku` trùng code ⇒ `suggested_sku_id` đúng; `link-skus` với mục đó + một `sku_id` ⇒ tạo `channel_listing` (nếu chưa có) + `sku_mapping` single×1 + `order_items.sku_id` set cho cả 2 đơn + `has_issue` clear + tồn reserve + `InventoryChanged` phát (push stock dispatch). Idempotent. Tenant isolation. Phân quyền.
- **FE**: smoke `<LinkSkusModal>`; smoke modal "Nhập/xuất hàng loạt".

## 10. Tiêu chí hoàn thành
- [x] `POST /inventory/bulk-adjust`, `POST /inventory/push-stock`, `GET /orders/unmapped-skus`, `POST /orders/link-skus` + phân quyền + `stats.unmapped`. (`InventoryLedgerService::adjust/receipt` thêm `$refType/$refId`; `SkuMappingController` thêm `unmappedFromOrders`/`linkFromOrders`.)
- [x] FE: tag/link đỏ "Chưa liên kết SKU — Liên kết" ở list đơn + `Alert` đỏ + nút "Liên kết SKU" ở `OrderDetailPage` + banner đỏ ở `OrdersPage` + `rowSelection` (chọn đơn chưa ghép) + nút "Liên kết SKU (n)" + `<LinkSkusModal>` (gộp SKU sàn, gợi ý sẵn, "Liên kết & xử lý lại" → invalidate orders/inventory/skus/listings); nút "Đẩy tồn lên sàn (n)" (rowSelection trên tab SKU) + "Phiếu nhập/xuất hàng loạt" (`Form.List` dòng SKU+số lượng) ở `InventoryPage`.
- [x] Test feature `BulkInventoryTest` (5) + `LinkOrderSkusTest` (4) — gộp/gợi ý, tạo listing+mapping, re-resolve+reserve, idempotent, tenant isolation, phân quyền.
- [x] Tài liệu: spec này (Implemented), `05-api/endpoints.md` (4 endpoint + `stats.unmapped`), `00-overview/roadmap.md`. *(`06-frontend/orders-filter-panel.md` / `03-domain/inventory-and-sku-mapping.md` — bổ sung khi rảnh; cốt lõi đã ghi ở spec này.)*

## 11. Câu hỏi mở
- "Phiếu" có cần số phiếu / template in ngay ở Phase 2 không? *Không — Phase 3/5 (print jobs / WMS).* Hiện chỉ `note`.
- `link-skus` có nên cho phép map combo (1 SKU sàn → nhiều master SKU) ngay trong modal nhanh? *Không — modal nhanh chỉ single×1; combo dùng màn "Liên kết SKU" đầy đủ (`POST /sku-mappings type=bundle`).*
