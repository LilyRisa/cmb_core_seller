# SPEC 0010: WMS — phiếu nhập kho / chuyển kho / kiểm kê (có header + duyệt + giá vốn bình quân)

- **Trạng thái:** Implemented (2026-05-19 — Phase 5 lõi WMS; FIFO `cost_layers`, phiếu xuất kho thủ công có header, mass-listing để follow-up)
- **Phase:** 5 *(lõi "WMS đầy đủ" — phần đăng-bán-đa-sàn & ĐVVC đợt 2 ở các PR/spec sau)*
- **Module backend liên quan:** Inventory (chính), Orders/Products (gián tiếp qua sổ cái)
- **Tác giả / Ngày:** Team · 2026-05-19
- **Liên quan:** SPEC-0003 (tồn kho lõi / sổ cái), SPEC-0004 (bulk-adjust — thao tác "áp ngay", spec này thêm "phiếu có header + duyệt" như đã hứa), SPEC-0005 (`inventory_levels.cost_price`), `docs/03-domain/inventory-and-sku-mapping.md`, `docs/02-data-model/overview.md`.

## 1. Vấn đề & mục tiêu
SPEC-0004 đã có `POST /inventory/bulk-adjust` (áp ngay, không header, không duyệt). Phase 5 cần **"phiếu" thật có header + dòng + trạng thái draft→confirmed→cancelled + người duyệt** cho 3 nghiệp vụ kho cốt lõi:
1. **Phiếu nhập kho** (`goods_receipts`) — nhập hàng từ NCC / đầu kỳ; mỗi dòng có `qty` + `unit_cost`; confirm ⇒ `+on_hand` + cập nhật **giá vốn bình quân theo kho** (`inventory_levels.cost_price`).
2. **Phiếu chuyển kho** (`stock_transfers`) — chuyển hàng giữa 2 kho của tenant; confirm ⇒ `transfer_out` ở kho nguồn + `transfer_in` ở kho đích.
3. **Phiếu kiểm kê** (`stocktakes`) — đếm thực tế từng SKU ở một kho; mỗi dòng snapshot `system_qty` (lúc thêm dòng, re-snapshot lúc confirm) + `counted_qty` + `diff`; confirm ⇒ `stocktake_adjust` (`+diff`) cho mỗi dòng `diff != 0`.

Nguyên tắc: phiếu **draft** sửa/huỷ được; **confirmed** bất biến — muốn điều chỉnh thì ra phiếu mới ⇒ sổ cái (`inventory_movements`) luôn là dấu vết kiểm toán trung thực.

## 2. Trong / ngoài phạm vi
**Trong:** 6 bảng (3 header + 3 items), models, 1 `WarehouseDocumentController` (route theo `{type}` ∈ `goods-receipts|stock-transfers|stocktakes`: index/show/store/confirm/cancel), `WarehouseDocumentService` (confirm/cancel — áp vào `InventoryLedgerService`), 3 method ledger mới (`transferOut`/`transferIn`/`stocktakeAdjust`) + `updateAverageCost()` + `onHand()`. FE: tab "Phiếu kho" ở trang Tồn kho. Test.
**Ngoài (follow-up Phase 5 / Phase sau):** giá vốn **FIFO** (`cost_layers`) — hiện chỉ bình quân gia quyền theo kho; phiếu **xuất kho thủ công** có header (huỷ hàng, biếu tặng… — hiện vẫn dùng `bulk-adjust` của SPEC-0004); sửa dòng phiếu draft sau khi tạo (hiện tạo lại); duyệt nhiều cấp / quy trình phê duyệt; phiếu liên kết với PO/NCC (Phase 6 Procurement); **đăng bán đa sàn / sao chép listing / sửa hàng loạt / category-attribute sàn** (phần còn lại của "Phase 5" — spec riêng khi connector sẵn sàng); **ĐVVC đợt 2** (ViettelPost/NinjaVan/SPX/VNPost/Best/Ahamove — connector mới, không sửa core).

## 3. Luồng
SPA → Tồn kho → tab "Phiếu kho" → chọn loại (Segmented) → "Tạo phiếu" → chọn kho (Radio button, không Select), thêm dòng `(SKU picker, số lượng [+ giá vốn / + số đếm thực tế])`, ghi chú → tạo ⇒ phiếu `draft`. Trong danh sách: "Xác nhận" ⇒ `POST /warehouse-docs/{type}/{id}/confirm` ⇒ `WarehouseDocumentService` áp vào sổ cái (mỗi dòng → một/hai `inventory_movements` với `ref_type` = `goods_receipt`/`transfer`/`stocktake`, `ref_id` = id phiếu; `available` tính lại; `InventoryChanged` ⇒ debounce push tồn lên các listing sàn). "Huỷ" (chỉ phiếu draft) ⇒ `cancel`. Xem phiếu = modal hiện header + bảng dòng (kiểm kê hiện `system_qty`/`counted_qty`/`diff`).

## 4. Hành vi & quy tắc
- **Trạng thái phiếu:** `draft → confirmed` (một chiều) | `draft → cancelled` (một chiều). Confirm/cancel phiếu đã confirmed ⇒ `422`. Phiếu trống (0 dòng) ⇒ confirm `422`.
- **Giá vốn (nhập kho):** sau khi `receipt()`, mỗi dòng có `unit_cost > 0` ⇒ `updateAverageCost(tenant, sku, warehouse, recvQty, recvUnitCost)`: `new = round((prevQty*prevCost + recvQty*recvCost) / (prevQty + recvQty))` (prevQty = on_hand mới − recvQty). `total_cost` phiếu = Σ qty×unit_cost.
- **Chuyển kho:** `from != to` (validate lúc tạo); confirm ⇒ với mỗi dòng `transferOut(from, qty)` + `transferIn(to, qty)`. (Có thể đẩy `from` xuống âm — cờ `is_negative` như mọi op khác; không chặn cứng để khớp thực tế.)
- **Kiểm kê:** lúc thêm dòng snapshot `system_qty` = `on_hand` hiện tại của (sku, kho); lúc confirm **re-snapshot** `system_qty` (vì có thể đã thay đổi giữa lúc tạo và lúc confirm) và `diff = counted_qty − system_qty`; `diff != 0` ⇒ `stocktakeAdjust(diff)`.
- **Idempotent:** confirm chạy trong `DB::transaction`. Movement của phiếu **không** dedupe per `(ref, type)` như order ops — vì confirm chỉ chạy 1 lần (trạng thái khoá lại). (Nếu sau này cho retry-confirm thì thêm dedupe.)
- **Phân quyền:** `inventory.view` để xem; `inventory.adjust` (nhập kho), `inventory.transfer` (chuyển kho), `inventory.stocktake` (kiểm kê) để tạo/confirm/huỷ — đã có trong `Role` enum (StaffWarehouse có cả 3). SKU/kho không thuộc tenant ⇒ `422`. `{type}` lạ ⇒ `404`.
- **Mã phiếu:** `PNK-/PCK-/PKK- + yymmdd + 5 ký tự ngẫu nhiên`, unique per tenant. (v1 đủ; muốn số tăng dần liên tục thì thêm sequence per tenant — follow-up.)

## 5. Dữ liệu (migration `2026_05_19_100001_create_warehouse_documents_tables`)
- `goods_receipts` (`code`, `warehouse_id`, `supplier?`, `note?`, `status[draft|confirmed|cancelled]`, `total_cost`, `confirmed_at`, `confirmed_by?`, `created_by?`, timestamps; unique `(tenant_id,code)`) + `goods_receipt_items` (`goods_receipt_id`, `sku_id`, `qty`, `unit_cost`).
- `stock_transfers` (`code`, `from_warehouse_id`, `to_warehouse_id`, `note?`, `status`, `confirmed_*`, `created_by?`) + `stock_transfer_items` (`stock_transfer_id`, `sku_id`, `qty`).
- `stocktakes` (`code`, `warehouse_id`, `note?`, `status`, `confirmed_*`, `created_by?`) + `stocktake_items` (`stocktake_id`, `sku_id`, `system_qty`, `counted_qty`, `diff`).
- `inventory_movements` thêm giá trị `ref_type` = `goods_receipt` / `transfer` / `stocktake` (cột string tự do); type movement dùng các const đã có: `goods_receipt`, `transfer_out`, `transfer_in`, `stocktake_adjust`. Domain event dùng lại `InventoryChanged`.

## 6. API & UI
**Endpoint** (cập nhật `docs/05-api/endpoints.md`) — `{type}` ∈ `goods-receipts|stock-transfers|stocktakes`:
- `GET /api/v1/warehouse-docs/{type}` (`inventory.view`) query `status?, warehouse_id?, q?(code), page, per_page≤100` ⇒ `{ data:[{id,code,status,type,note,item_count,warehouse_id|from_warehouse_id+to_warehouse_id,supplier?,total_cost?,confirmed_at,created_at}], meta:{pagination} }`.
- `GET /api/v1/warehouse-docs/{type}/{id}` (`inventory.view`) ⇒ doc + `items[]` (`{id,sku_id,sku{id,sku_code,name}, qty|unit_cost | system_qty|counted_qty|diff}`).
- `POST /api/v1/warehouse-docs/{type}` (`inventory.adjust|transfer|stocktake` theo type) — body: `{ note?, warehouse_id | from_warehouse_id+to_warehouse_id, supplier?(goods-receipts), items:[{sku_id, qty[, unit_cost] | counted_qty}] (≤500) }` ⇒ `201` phiếu `draft` (+ items; kiểm kê snapshot `system_qty`). Kho/SKU lạ, from==to ⇒ `422`.
- `POST /api/v1/warehouse-docs/{type}/{id}/confirm` ⇒ áp vào sổ cái; phiếu đã confirmed/huỷ ⇒ `422`.
- `POST /api/v1/warehouse-docs/{type}/{id}/cancel` ⇒ huỷ phiếu draft; đã confirmed ⇒ `422`.

**UI:** `resources/js/components/WarehouseDocsTab.tsx` — tab "Phiếu kho" ở trang Tồn kho (`/inventory?tab=docs`): `Segmented` chọn loại + bảng phiếu (mã/kho/[NCC+giá trị]/số dòng/trạng thái/tạo lúc + nút Xác nhận·Huỷ cho phiếu draft) + modal "Tạo phiếu" (kho = `Radio.Group` button — không Select; dòng hàng = `Form.List` với `<SkuPickerField>` (danh sách popover) + `InputNumber`) + modal xem chi tiết phiếu. `lib/inventory.tsx`: `useWarehouseDocs/useWarehouseDoc/useCreateWarehouseDoc/useConfirmWarehouseDoc/useCancelWarehouseDoc`.

## 7. Cách kiểm thử
`tests/Feature/Inventory/WarehouseDocumentsTest` (5 ca, `Bus::fake([PushStockForSku])`): nhập kho draft→confirm ⇒ `+on_hand`, giá vốn bình quân đúng, movement `goods_receipt`, confirm/cancel phiếu confirmed ⇒ `422`; huỷ phiếu draft; chuyển kho ⇒ `from -= qty` / `to += qty`, movement `transfer_out`/`transfer_in`, from==to ⇒ `422`; kiểm kê ⇒ `system_qty` snapshot + `diff`, confirm ⇒ `on_hand` đúng, movement `stocktake_adjust`; RBAC (viewer tạo ⇒ `403`) + tenant isolation (phiếu tenant khác ⇒ `404`, `{type}` lạ ⇒ `404`). `php artisan test` toàn bộ xanh (186+).

## 8. Triển khai
Migration mới ⇒ prod chạy `php artisan migrate --force`. Không config/env mới. Confirm phiếu phát `InventoryChanged` ⇒ Horizon queue `inventory-push` đẩy tồn lên sàn như thường lệ.
