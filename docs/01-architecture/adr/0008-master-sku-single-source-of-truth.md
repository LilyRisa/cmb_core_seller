# ADR-0008: Tồn kho — master SKU là một nguồn sự thật duy nhất; ghép SKU listing↔master hỗ trợ combo (1→N); mọi thay đổi tồn có dòng sổ cái

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team

## Bối cảnh

Cùng một sản phẩm thật bán trên nhiều sàn (mỗi sàn một listing/seller_sku khác nhau), có cả combo (1 listing = nhiều SKU master). Nếu mỗi sàn giữ tồn riêng ⇒ oversell, lệch số. Cần một con số tồn duy nhất và truy vết được mọi biến động.

## Quyết định

- **Master SKU + kho (warehouse) là nguồn sự thật duy nhất** về tồn. `inventory_levels(sku_id, warehouse_id, on_hand, reserved, available, safety_stock)`. `available = on_hand − reserved − safety_stock` (không xuống dưới 0).
- **Ghép SKU**: `sku_mappings(channel_listing_id, sku_id, quantity, type[single|bundle])` — một listing của sàn map sang một hoặc nhiều SKU master (combo 1→N với hệ số). Auto-match khi `seller_sku == sku_code`; còn lại ghép tay.
- **Đẩy tồn lên sàn** (`PushStockToChannel`): debounce/coalesce theo key `push-stock:{tenant}:{sku}` + distributed lock + safety stock; oversell ⇒ cảnh báo + đẩy 0 lên sàn. Đồng bộ ngược (đọc tồn sàn để đối chiếu) là tuỳ chọn cấu hình; `ReconcileInventory` hằng giờ cảnh báo lệch.
- **Mọi thay đổi số lượng tồn ⇒ một dòng `inventory_movements`** (qty_change, type, ref_type/ref_id, balance_after, note, created_by) — không bao giờ "ghi đè im lặng". Bán/tạo đơn tay ⇒ reserve; huỷ/hết hạn ⇒ release; ship ⇒ trừ on_hand (theo bảng tác động ở doc inventory).
- Đơn từ mọi nguồn (kể cả `manual`) trừ chung một kho.

## Hệ quả

- Tích cực: không oversell chéo sàn; con số tồn nhất quán; mọi biến động truy vết được (sổ cái) — nền cho giá vốn FIFO/kiểm kê sau.
- Đánh đổi: cần lock + debounce đúng để không đẩy tồn loạn/đua nhau; combo làm tính toán phức tạp hơn (phải tác động đủ thành phần); cần job đối chiếu định kỳ vì sàn có độ trễ.
- Liên quan: `03-domain/inventory-and-sku-mapping.md`, `02-data-model/overview.md` (module Inventory), `07-infra/queues-and-scheduler.md`.
