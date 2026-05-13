# SPEC 0015: Báo cáo bán hàng & lợi nhuận thực

- **Trạng thái:** Implemented (2026-05-22 — Phase 6.1 lát 4)
- **Phase:** 6.1
- **Module backend liên quan:** Reports (mới — service-only, không có schema), Orders, Inventory
- **Liên quan:** SPEC-0012 (lợi nhuận ước tính), SPEC-0014 (FIFO COGS), SPEC-0016 (đối soát sàn — Phase 6.2)

## 1. Vấn đề & mục tiêu
SPEC-0012 đã có "lợi nhuận ước tính" trên từng đơn nhưng:
- Không có **báo cáo tổng hợp** (theo ngày/tuần/tháng, theo sàn, theo SP).
- Không có **export Excel/CSV** cho kế toán làm việc offline.

Mục tiêu: dashboard báo cáo chuyên nghiệp (Statistic + bảng + biểu đồ) — doanh thu, lợi nhuận thực (dùng `order_costs` từ SPEC-0014), top sản phẩm; CSV/Excel export với BOM UTF-8 cho Excel mở thẳng tiếng Việt.

## 2. Phạm vi
**Trong:**
- Module `Reports` (service-only, không bảng cache — query trực tiếp `orders` + `order_costs` + `order_items`).
- 3 báo cáo: **Doanh thu** (`/reports/revenue`), **Lợi nhuận thực** (`/reports/profit` — chỉ đơn đã ship, có `order_costs`), **Top sản phẩm** (`/reports/top-products`).
- Export CSV với UTF-8 BOM (`\xEF\xBB\xBF`) — Excel mở thẳng tiếng Việt không lỗi font.
- UI: `/reports` với 3 tab + bộ lọc thời gian thân thiện (chip 7d/30d/Tháng/Quý/Năm + RangePicker) + Radio chip Sàn TMĐT + Radio granularity (ngày/tuần/tháng).
- Permission: `reports.view` đọc; `reports.export` cho CSV stream.

**Ngoài (follow-up):**
- Biểu đồ tương tác sâu (drill-down per SKU/Shop) — Phase 7.
- XLSX format (chỉ CSV ở v1; XLSX qua composer `phpoffice/phpspreadsheet` = optional install).
- Báo cáo theo nhân viên / khách hàng / kho — Phase sau.
- Snapshot pre-aggregate nightly (`profit_snapshots`) cho tenant lớn — Phase sau (khi volume cần).

## 3. Endpoint
- `GET /api/v1/reports/revenue?from&to&granularity=day|week|month&source&channel_account_id` → `{ totals, series, by_source }`.
- `GET /api/v1/reports/profit?from&to&granularity` → `{ totals: { orders, revenue, cogs, gross_profit, margin_pct }, series }` (chỉ đơn đã ship).
- `GET /api/v1/reports/top-products?from&to&limit=20&sort_by=revenue|profit|qty` → `{ items: [{ sku, qty, revenue, cogs, gross_profit, margin_pct }] }`.
- `GET /api/v1/reports/export?type=revenue|profit|top-products&format=csv&...filters` → stream `text/csv; charset=UTF-8`, filename `bao-cao-*-YYYYMMDD-YYYYMMDD.csv`.

## 4. Tính toán
- `revenue`: `Σ orders.grand_total` trong `[from, to]` (loại `cancelled`); chia bucket theo `truncDateSql(placed_at, granularity)` — postgres `DATE_TRUNC`, sqlite `strftime`.
- `profit`: `Σ order_costs.cogs_total` ↔ `Σ (qty × order_items.unit_price)`; chỉ ghi nhận khi đơn đã ship (có `order_costs` row). Margin% = `(revenue - cogs) / revenue * 100`.
- `top-products`: aggregate trên `order_items` (mọi đơn không huỷ) + left join `order_costs` (đã ship — có cogs); sort theo `revenue|profit|qty`.

## 5. UI
`/reports` với:
- `Statistic` 4 ô tổng quan + ô màu xanh/đỏ theo dấu (lợi nhuận âm = đỏ).
- Bảng diễn biến theo bucket thời gian.
- Bảng "theo sàn" với `Progress` tỉ trọng.
- Top SP: bảng có cột `SkuLine` (ảnh + code + name) + `MoneyText` + Margin%.
- Nút **"Xuất CSV"** — gọi `/reports/export?type=tab` trực tiếp (mở tab mới); UTF-8 BOM cho Excel.

## 6. Kiểm thử (`ReportsApiTest` — 3 ca)
- Seed 2 đơn ship 5 + 5 đơn vị (giá 150k) → `revenue.totals.orders=2`, `revenue=750000`; `profit.orders=2`; `top-products` 1 SKU, qty=5, revenue=750000.
- CSV export: response chứa BOM `\xEF\xBB\xBF` + header `Ngày,"Số đơn",…`.
- RBAC: Viewer 403; Accountant `view + export` ✓.

## 7. Tiêu chí hoàn thành
- [x] 3 endpoint + CSV export + UI tabs.
- [x] Tests 3/3 pass; full suite 215/215.
- [x] Cập nhật docs.

## 8. Câu hỏi mở
- Có nên cache `profit_snapshots` cho tenant lớn (≥ 100k đơn/tháng)? — Để Phase sau khi đo benchmark.
- Báo cáo "phí thực" (settlement) đã tích hợp ở SPEC-0016 (Phase 6.2) — UI profit tab giờ hiển thị `fee_source: settlement` khi có.
