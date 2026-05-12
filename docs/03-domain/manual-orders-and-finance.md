# Đơn thủ công & Tài chính/Đối soát

**Status:** Draft · **Cập nhật:** 2026-05-11

## Phần 1 — Đơn thủ công (manual orders)

> "manual" được đối xử như một **kênh** (`source = 'manual'`, có `ManualConnector` rỗng) để mọi code về đơn dùng chung một đường.

- **Tạo đơn:** màn "Tạo đơn" trên SPA (`CreateOrderPage` + `components/OrderItemsEditor.tsx`): chọn nguồn phụ (`manual` / `website` / `facebook` / `zalo` / `hotline` — chỉ là nhãn), nhập khách hàng (tên, SĐT, địa chỉ tỉnh/huyện/xã VN). Phần **Hàng hoá** dạng BigSeller: bấm "Tìm & thêm sản phẩm" ⇒ panel có ô tìm kiếm đổ xuống **danh sách SKU** (ảnh · tên · mã · tồn khả dụng · giá bán tham khảo), và **một mục cố định ở đầu "Tạo sản phẩm nhanh"**. Chọn ⇒ dòng hàng hiện ở bảng bên dưới; mỗi dòng sửa được **số lượng / đơn giá / chiết khấu** (đơn giá mặc định từ `ref_sale_price` của SKU).
  - **Dòng SKU hệ thống:** có `sku_id`; `name`/`seller_sku`/`image` tự lấy từ SKU (FE chỉ cần gửi `sku_id` + qty + giá). Reserve tồn như thường.
  - **Dòng "sản phẩm nhanh" (ad-hoc):** không liên kết SKU nào — nhập **tên** (bắt buộc) + **ảnh** (upload qua `POST /api/v1/media/image`, lưu URL vào `order_items.image`) + giá bán + số lượng. **Không** theo dõi tồn kho, **không** bị gắn cờ `has_issue='SKU chưa ghép'` (xem `OrderInventoryService::apply` — một dòng manual không có `sku_id`/`seller_sku`/`external_sku_id` được coi là ad-hoc có chủ đích, không phải SKU chưa ghép). Backend: `ManualOrderService::normalizeItems` cho phép dòng không `sku_id` khi có `name`.
  - **Hướng phát triển sau** (lưu ý): có thể thêm nút "Lưu thành SKU" để biến dòng ad-hoc thành master SKU thật (tạo `skus` + map ngược `order_items.sku_id`); ảnh ad-hoc hiện chưa có job dọn rác (object R2 không bị xoá khi xoá đơn — giống ảnh SKU); chưa hỗ trợ nhiều ảnh/biến thể cho dòng ad-hoc; combo (1 dòng = nhiều SKU) vẫn dùng màn "Liên kết SKU" / API `sku-mappings`.
- **Lưu đơn:** tạo `orders(source=manual, channel_account_id=null, external_order_id=null, order_number=tự sinh)` + `order_items` (có `sku_id` cho dòng SKU; `sku_id=null` cho dòng ad-hoc); trạng thái mặc định `pending` (hoặc `processing` nếu chọn) ⇒ **reserve tồn ngay** cho các dòng có SKU (movements `order_reserve`).
- **Vòng đời:** đi theo cùng state machine; người dùng tự đẩy `processing → ready_to_ship → shipped → delivered → completed`; huỷ ⇒ nhả tồn.
- **Giao hàng:** dùng **luồng B** (ĐVVC riêng qua `CarrierConnector`) để tạo vận đơn + lấy label; hoặc đánh dấu "tự giao" (không tracking) cho đơn giao tay.
- **In:** picking/packing list như đơn sàn; vận đơn nếu dùng ĐVVC.
- **Sửa/huỷ:** sửa được khi chưa `shipped` (sửa item ⇒ tính lại reserve); huỷ bất kỳ lúc nào trước `shipped`.
- **Trùng đơn:** cảnh báo nếu cùng SĐT + cùng SKU trong khoảng thời gian ngắn (chống tạo trùng).
- **Phân quyền:** `staff_order` tạo/sửa được; `viewer` không.

## Phần 2 — Tài chính & Đối soát (Phase 6)

> Mục tiêu: biết **lãi/lỗ thực** của từng đơn / SP / gian hàng, và đối chiếu **tiền sàn thực trả** với tính toán.

### 2.1 Kéo đối soát từ sàn
- Connector `fetchSettlements(auth, DateRange)` (và/hoặc webhook `settlement_available`) → tạo `settlements` (kỳ, tổng payout) + `settlement_lines` (mỗi dòng: `order_id?`, `fee_type`, `amount`, `raw`).
- `fee_type` chuẩn hoá: `commission` (hoa hồng sàn), `transaction_fee` (phí thanh toán), `shipping_fee_charged`/`shipping_fee_subsidy`, `affiliate_commission`, `platform_voucher` (voucher do sàn tài trợ), `seller_voucher`, `adjustment`, `tax`, `other`. Mỗi connector map mã phí riêng của sàn → tập này (nơi duy nhất chứa mã phí của sàn đó).

### 2.2 Giá vốn (COGS)
- Lấy từ `cost_layers` theo FIFO khi `order_ship` (xem `inventory-and-sku-mapping.md` §5) → `order_costs.cost_of_goods`.

### 2.3 Tính lợi nhuận đơn
```
profit(order) = grand_total(thu của khách)            -- doanh thu ghi nhận
              - cost_of_goods                          -- giá vốn (FIFO)
              - Σ settlement_lines.fee (commission, transaction_fee, ...) cho đơn
              - shipping_fee thực tế nhà bán chịu
              - seller_discount (giảm giá nhà bán tự chịu, nếu chưa nằm trong grand_total)
              - other_fee (chi phí phân bổ khác, nếu có)
```
Lưu vào `order_costs.computed_profit`; tổng hợp lên `profit_snapshots` theo chiều (đơn / SKU / gian hàng / ngày / tháng) để báo cáo nhanh (không tính lại realtime trên triệu dòng).

### 2.4 Đối chiếu
- So `Σ settlement_lines.amount + grand_total adjustments` với `settlements.total_payout` của kỳ ⇒ phát hiện chênh lệch chưa giải thích ⇒ cờ cảnh báo.
- So phí ship ước tính (lúc tạo vận đơn) với phí thực tế (settlement / hoá đơn ĐVVC).
- Đơn chưa có dòng settlement sau X ngày kể từ `completed` ⇒ cảnh báo "chưa được sàn đối soát".

### 2.5 Báo cáo (module Reports)
- Doanh thu / lợi nhuận theo thời gian, theo sàn/gian hàng, theo SP/SKU; top SP lãi/lỗ; tỉ lệ huỷ/hoàn; biểu đồ dòng tiền (payout theo kỳ). Export Excel/CSV. Đọc từ `profit_snapshots` / read replica.

### 2.6 (Rất sau) Hoá đơn điện tử
- `EInvoiceConnector` (VNPT/Viettel/MISA...) — phát hành HĐĐT cho đơn khi nhà bán yêu cầu. Cùng pattern connector/registry. Không trong phạm vi gần.
