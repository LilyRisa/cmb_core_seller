# SPEC 0014: Mua hàng (NCC + Đơn mua) + FIFO Cost Layers (chuẩn kế toán)

- **Trạng thái:** Implemented (2026-05-22 — Phase 6.1 lát 1+3)
- **Phase:** 6.1 (Tài chính + Mua hàng + Báo cáo + Billing — lát "kế toán + kho bãi")
- **Module backend liên quan:** Procurement (mới), Inventory, Orders
- **Liên quan:** SPEC-0003 (SKU/tồn), SPEC-0010 (WMS phiếu kho), SPEC-0012 (lợi nhuận ước tính), `docs/03-domain/inventory-and-sku-mapping.md` (FIFO follow-up — giờ implemented)

## 1. Vấn đề & mục tiêu
Phase 5 đã có phiếu nhập kho + giá vốn bình quân gia quyền nhưng:
1. **Chưa có NCC + bảng giá nhập + Purchase Order** (đặt hàng từ NCC, theo dõi tiến độ nhận từng đợt).
2. **Giá vốn bình quân** không phản ánh chính xác COGS từng đơn — kế toán chuẩn cần **FIFO** (mỗi lô nhập là 1 layer; bán đơn nào trừ layer cũ nhất, ghi COGS thực bất biến).

Mục tiêu: cho phép shop quản lý NCC + giá nhập, đặt PO → nhận nhiều đợt → kho cập nhật giá vốn FIFO chuẩn; báo cáo lợi nhuận có **COGS thực** không phải ước tính.

## 2. Trong / ngoài phạm vi
**Trong:**
- Module `Procurement`: `suppliers`, `supplier_prices`, `purchase_orders`, `purchase_order_items` + service `PurchaseOrderService` + REST CRUD; permission `procurement.view|manage|receive`.
- Liên kết PO ↔ `GoodsReceipt`: PO `confirmed`/`partially_received` → `receive(lines)` tạo `GoodsReceipt` nháp link `po_id`; listener `LinkGoodsReceiptToPO` (subscribe `GoodsReceiptConfirmed` event) cộng dồn `qty_received` → PO chuyển trạng thái.
- FIFO cost layers: bảng `cost_layers` + `order_costs` (bất biến) + service `FifoCostService` (record/consume/unconsume); hook ở `WarehouseDocumentService::confirmGoodsReceipt` (record layer) + `OrderInventoryService::ship` (consume + ghi `order_costs`) + cancel/return (`unconsume`).
- Tenant settings `cost_method` (`fifo` mặc định | `average`).
- `OrderProfitService` đọc COGS THỰC từ `order_costs` khi đã ship; fall back ước tính khi chưa ship; `OrderResource.profit.cost_source` ∈ `estimate|fifo|average|latest`.
- UI: `SuppliersPage`, `PurchaseOrdersPage` (+ Drawer chi tiết với Steps + Modal "Nhận hàng"); menu "Kho & Mua hàng".
- Tests: `SupplierApiTest`, `PurchaseOrderFlowTest`, `FifoCostTest`.

**Ngoài (follow-up các phase con sau):**
- Đề xuất nhập hàng (Demand Planning) — SPEC-0017 (Phase 6.3).
- FIFO multi-warehouse advanced (ưu tiên cùng kho, layer xuyên kho) — v1 gộp tồn tenant + ưu tiên cùng warehouse khi consume.
- Phê duyệt nhiều cấp cho PO, chữ ký, tích hợp ERP/kế toán external — Phase sau.

## 3. Luồng chính
1. **Quản lý NCC:** `POST /suppliers {name, phone, tax_code, address, payment_terms_days, ...}` → `code` auto `NCC-NNNN`. Bảng giá nhập per (NCC × SKU): `POST /suppliers/{id}/prices {sku_id, unit_cost, moq, valid_from?, is_default?}`. `is_default=true` ⇒ unset các bản default khác của cùng (supplier, sku).
2. **Tạo PO:** `POST /purchase-orders {supplier_id, warehouse_id, expected_at?, items: [{sku_id, qty_ordered, unit_cost?}]}` ⇒ `draft`. Dòng `unit_cost` để trống ⇒ confirm sẽ lấy `supplier_prices.is_default` của (supplier, sku).
3. **Chốt:** `POST /purchase-orders/{id}/confirm` (idempotent) ⇒ `confirmed`, chốt `unit_cost` + `total_cost = Σ qty_ordered × unit_cost`. Sau chốt KHÔNG sửa header/lines (kế toán immutable).
4. **Huỷ:** `POST /purchase-orders/{id}/cancel` — CHỈ ở `draft`; PO đã confirm phải tạo phiếu điều chỉnh kế toán riêng.
5. **Nhận hàng (đợt):** `POST /purchase-orders/{id}/receive {lines: [{sku_id, qty}]}` ⇒ tạo `GoodsReceipt` nháp link `po_id` (mã `PNK-YYYYMMDD-NNNN`); FE redirect tới WMS để confirm. Mỗi line phải `qty ≤ qty_ordered - qty_received` còn lại.
6. **Confirm `GoodsReceipt`** (qua `WarehouseDocumentService`) ⇒ áp tồn + cập nhật giá vốn bình quân (cũ) + **insert cost layer** FIFO (mới) + emit `GoodsReceiptConfirmed`. Listener cộng dồn `qty_received` ở PO items; đủ tất cả ⇒ PO `received`, chưa đủ ⇒ `partially_received`.
7. **Bán đơn → ship:** `OrderInventoryService::ship` gọi `FifoCostService::consumeForShip` → rút FIFO oldest first (`SELECT FOR UPDATE`), ghi `order_costs` (bất biến, unique `order_item_id`). Tồn FIFO không đủ ⇒ synthetic layer dùng `Sku.effectiveCost()` (đánh dấu `synthetic=true` trong `layers_used`).
8. **Cancel/Return đơn:** `FifoCostService::unconsume(order_item_id)` đảo `qty_remaining` theo `layers_used` rồi xoá `order_costs` row.

## 4. Hành vi & quy tắc
- **Idempotent** tất cả: `recordReceiptLayer` qua unique `(tenant, source_type, source_id, sku_id)`; `consumeForShip` qua unique `order_item_id` (replay ship = no-op); `applyReceiptConfirmed` lock PO `FOR UPDATE`.
- **Atomic** consume: `SELECT FOR UPDATE` trên layers `ORDER BY received_at ASC` cùng SKU; ưu tiên cùng warehouse khi có.
- **Permission**:
  - `Owner/Admin`: full.
  - `Accountant`: `procurement.view` (chỉ đọc — xem NCC + PO + giá vốn để đối soát).
  - `StaffWarehouse`: `procurement.view` + `procurement.receive` (nhận hàng theo PO; KHÔNG sửa giá / huỷ PO).
- **Money** = bigint VND đồng (không float). **Cost layers**: `qty_remaining` ≥ 0 luôn (đảm bảo bằng synthetic layer khi tồn FIFO chưa đủ).
- **Cấm xoá PO/NCC** có ràng buộc kế toán: xoá NCC bị chặn nếu còn PO `draft/confirmed/partially_received`.

## 5. Dữ liệu
**Migrations** (`Procurement/Database/Migrations/2026_05_22_*`):
- `suppliers` (soft-delete; unique `(tenant_id, code)`).
- `supplier_prices` (unique `(supplier_id, sku_id, valid_from)`).
- `purchase_orders` (unique `(tenant_id, code)`; status `draft|confirmed|partially_received|received|cancelled`).
- `purchase_order_items` (unique `(purchase_order_id, sku_id)`).
- Thêm cột `purchase_order_id?` + `supplier_id?` vào `goods_receipts` (nullable, index).

**Migrations** (`Inventory/Database/Migrations/2026_05_22_100003_*`):
- `cost_layers` (FIFO key `received_at`; unique `(tenant_id, source_type, source_id, sku_id)`).
- `order_costs` (bất biến — không updated_at; unique `order_item_id`).

## 6. API & UI
- `GET/POST /suppliers`, `GET/PATCH/DELETE /suppliers/{id}`, `POST/DELETE /suppliers/{id}/prices`.
- `GET/POST /purchase-orders`, `GET/PATCH /purchase-orders/{id}`, `POST /purchase-orders/{id}/{confirm,cancel,receive}`.
- `OrderResource.profit.cost_source` (`estimate|fifo|average|latest`) — FE hiển thị "Phí thực" vs "Ước tính".
- FE: `/procurement/suppliers` (Table + Drawer thêm/sửa + tab "Bảng giá nhập"), `/procurement/purchase-orders` (Table + Drawer chi tiết với `Steps` + `Progress` tiến độ + tab "Phiếu nhập liên kết" + Modal "Nhận hàng").

## 7. Edge case
- PO confirm với line `unit_cost = 0` và không có giá NCC mặc định ⇒ `unit_cost` giữ 0 (cảnh báo hiển thị; kế toán nhập tay khi sửa GoodsReceipt).
- Receive vượt `qty_remaining` ⇒ `422 lines`.
- Cancel PO đã confirm ⇒ `422 purchase_order`.
- Cost layer hết tồn mà còn ship ⇒ synthetic layer (đảm bảo COGS không null); audit log trong `layers_used`.
- Return đơn nhiều lần ⇒ `unconsume` idempotent (chỉ đảo layer nếu `order_costs` row còn tồn tại).

## 8. Kiểm thử
- `SupplierApiTest` (3 ca): CRUD + RBAC (Viewer 403, Accountant view-only) + tenant isolation; `is_default` swap.
- `PurchaseOrderFlowTest` (3 ca): create→confirm→receive đợt 1 (50%)→partially_received→receive đợt 2→received; cancel rule (chỉ draft); StaffWarehouse receive được, không sửa giá.
- `FifoCostTest` (2 ca): 2 layer 1000+1500 → ship 5 (5×1000=5000) → ship 10 (5×1000+5×1500=12500); `OrderResource.profit.cost_source` đổi `estimate → fifo` khi ship.

## 9. Tiêu chí hoàn thành
- [x] Module Procurement migrations + models + service + REST API + UI.
- [x] Cost layers + order_costs + FifoCostService + hook vào `confirmGoodsReceipt` + `OrderInventoryService::ship`.
- [x] OrderProfitService dùng exact COGS từ `order_costs` khi có.
- [x] 11 tests pass (cộng dồn 215/215 cùng full suite).
- [x] Cập nhật `docs/specs/README.md`, `docs/02-data-model/overview.md`, `docs/05-api/endpoints.md`.

## 10. Câu hỏi mở
- Có cần phê duyệt nhiều cấp cho PO giá trị lớn? — Phase sau (rules engine).
- Có cần phân bổ FIFO theo kho (mỗi kho 1 stream) thay vì gộp tenant? — đã ưu tiên cùng kho khi consume; tách hẳn = follow-up khi shop có ≥ 2 kho thực sự.
