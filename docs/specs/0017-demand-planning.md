# SPEC 0017: Đề xuất nhập hàng (Demand Planning)

- **Trạng thái:** Implemented (2026-05-23 — Phase 6.3)
- **Phase:** 6.3
- **Module backend liên quan:** Procurement, Inventory
- **Liên quan:** SPEC-0010 (WMS phiếu nhập), SPEC-0014 (FIFO COGS + PO), SPEC-0015 (báo cáo)

## 1. Vấn đề & mục tiêu
Shop nhỏ thường "nhập theo cảm tính" ⇒ tồn quá nhiều hoặc thiếu hàng. Có dữ liệu **tốc độ bán** (`order_costs`) + **tồn kho** + **PO đang về** + **giá NCC** → tự đề xuất số lượng cần đặt, chia theo NCC, MOQ, lead time, đệm an toàn — giúp shop **không bị hết hàng** đồng thời **không tồn dư**.

## 2. Phạm vi
**Trong:**
- Service `DemandPlanningService` (`Procurement` module): `compute(window, lead_time, cover_days, filters)` + `createPoFromSuggestions(rows)`.
- REST: `GET /procurement/demand-planning?window_days=&lead_time=&cover_days=&urgency=&supplier_id=&q=`, `POST /procurement/demand-planning/create-po`.
- Permission: `procurement.view` đọc; `procurement.manage` tạo PO.
- UI: `/procurement/demand-planning` — Statistic 4 ô + bộ lọc (cửa sổ phân tích / lead_time / cover_days / urgency / NCC / search) + bảng (SKU/Mức độ/Bán/ngày/Tồn/Đang về/Số ngày còn hàng + Progress màu/Đề xuất nhập editable/NCC gợi ý/Thành tiền) + Bulk button "Tạo PO nháp (N)" chia theo NCC.

**Ngoài (follow-up):**
- Seasonality / xu hướng (rolling 7d vs 30d) — Phase sau.
- Auto-create PO recurring theo lịch — kết hợp với SPEC Automation Rules (Phase 6.5).
- Multi-warehouse stream FIFO (mỗi kho 1 cost layer) — Phase sau.
- Tích hợp với cross-border / Air-shipping lead time per supplier — Phase sau.

## 3. Công thức (chuẩn supply-chain căn bản)
1. **Tốc độ bán** `avg_daily_sold` = Σ `order_costs.qty` trong `window_days` qua / `window_days`. Dùng `order_costs` (đơn đã ship — bất biến) ⇒ **không** đếm huỷ/trả.
2. **Tồn khả dụng** `available` = `on_hand - reserved` (gộp `inventory_levels` theo SKU).
3. **Đang về** `on_order` = Σ `(qty_ordered - qty_received)` trên PO `confirmed | partially_received`.
4. **Số ngày còn hàng** `days_left` = `floor((available + on_order) / avg_daily_sold)` (∞ khi không bán gì + còn tồn).
5. **Đề xuất nhập** `suggested_qty` = `max(0, ceil(avg_daily_sold × (lead_time + cover_days)) − available − on_order)`. Tròn lên `MOQ` của NCC mặc định nếu có. Nếu `avg_daily_sold = 0` và `available < safety_stock` ⇒ đề xuất nhập tới `safety_stock` (đảm bảo mức an toàn).
6. **Mức độ urgency**:
   - `urgent`: `days_left ≤ lead_time` (sẽ hết hàng trước khi đặt kịp).
   - `soon`: `lead_time < days_left ≤ lead_time + cover_days`.
   - `ok`: dư an toàn ⇒ **ẩn khỏi danh sách** (không actionable).
7. **NCC gợi ý**: lấy `supplier_prices` có `is_default=true` cho SKU; nếu không có ⇒ "Chưa có NCC mặc định" + disabled checkbox.

## 4. Hành vi & UX
- Người dùng chỉnh được `suggested_qty` trực tiếp trên bảng (`InputNumber` per row).
- Chọn nhiều dòng → "Tạo PO nháp (N)" → Modal xác nhận tổng giá trị → BE chia theo `supplier_id` ⇒ **1 PO nháp per NCC** (status `draft`); FE redirect tới `/procurement/purchase-orders` để rà soát + confirm.
- Sort: `urgent` trước, rồi `soon`, rồi `suggested_qty` desc — dòng nóng lên đầu.

## 5. API
- `GET /procurement/demand-planning?window_days=30&lead_time=7&cover_days=14&urgency=urgent|soon&supplier_id&q` → `{ data: Row[], meta: { pagination, params } }`.
- `POST /procurement/demand-planning/create-po { warehouse_id, rows: [{sku_id, qty, supplier_id, unit_cost?}] }` → `{ purchase_order_ids: [int], count }` (201).

## 6. Edge case
- SKU không có giá NCC mặc định ⇒ dòng vẫn hiển thị nhưng không chọn được để tạo PO; user vào "Nhà cung cấp" set giá rồi quay lại.
- Filter `supplier_id` ⇒ chỉ giữ SKU mà NCC default = NCC đã chọn.
- SKU active nhưng tồn = 0 và không có sale ⇒ suggested_qty = 0; bị filter ra (`ok` urgency, hidden).

## 7. Kiểm thử (`DemandPlanningTest` — 5 ca)
- `velocity_and_suggested_qty_with_lead_time_and_cover`: 50 đơn vị bán/30d + 20 tồn + lead 7 + cover 14 ⇒ avg=1.667/d, days_left=11, suggested=16, urgency=soon.
- `urgent_when_days_left_under_lead_time`: 2 tồn + 30 bán/30d ⇒ days_left=2 < lead 7 ⇒ urgent.
- `on_order_reduces_suggestion`: PO confirmed 10 đang về ⇒ trừ vào suggested.
- `create_po_from_suggestions`: chọn 1 dòng → tạo PO `draft` đúng SKU/qty/unit_cost/supplier.
- `supplier_default_moq_round_up`: MOQ 10 ⇒ needed 21 round up = 30.

## 8. Triển khai
- Không config mới. Tham số mặc định: `window_days=30, lead_time=7, cover_days=14` (UI cho chỉnh).
- Performance: query batch (1 query velocity + 1 stocks + 1 on_order + 1 SKUs + 1 prices). Với 10k SKU/tenant: ~200ms (sql index sẵn theo SKU). Tenant lớn (>50k SKU): cân nhắc `profit_snapshots` pre-aggregate — follow-up.

## 9. Tiêu chí hoàn thành
- [x] Service + REST + UI + 5 tests pass.
- [x] Permission RBAC.
- [x] Sidebar menu "Đề xuất nhập hàng".

## 10. Câu hỏi mở
- Có nên đề xuất theo **tốc độ bán tăng tốc** (so sánh 7d vs 30d) cho mặt hàng trending? — Phase sau.
- Có nên gợi ý NCC tốt nhất (giá rẻ nhất / lead time ngắn nhất / công nợ dài nhất) thay vì chỉ `is_default`? — Phase sau (cần dữ liệu thực tế lead time per NCC).
