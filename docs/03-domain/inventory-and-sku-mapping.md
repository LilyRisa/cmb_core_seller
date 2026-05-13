# Tồn kho & Ghép SKU

**Status:** Stable · **Cập nhật:** 2026-05-11

> Nguyên tắc: **master SKU là một nguồn sự thật về tồn kho.** Listing trên sàn chỉ "phản chiếu" tồn của master SKU liên kết. Mọi thay đổi tồn ⇒ một dòng bất biến trong `inventory_movements`. Đẩy tồn lên sàn là hệ quả tự động, có debounce, có lock.

## 1. Khái niệm

- **Master SKU** (`skus`): đơn vị tồn nhỏ nhất của nhà bán, `sku_code` duy nhất theo tenant.
- **Tồn theo kho** (`inventory_levels`, khoá `(sku_id, warehouse_id)`):
  - `on_hand` — số lượng thực có trong kho.
  - `reserved` — đã giữ cho đơn chưa giao.
  - `safety_stock` — đệm an toàn (cấu hình theo SKU và/hoặc theo gian hàng).
  - `available` = `max(0, on_hand − reserved − safety_stock)` — số đẩy lên sàn. (Có thể cache vào `available_cached`, nhưng nguồn tính là 3 cột kia.)
- **Tổng available của SKU** = tổng `available` qua các kho được phép bán (cấu hình kho nào bán cho gian hàng nào; mặc định tất cả).
- **Channel Listing** (`channel_listings`): SP/biến thể trên một gian hàng (`external_sku_id`, `seller_sku`, `channel_stock` = tồn đang hiển thị trên sàn).
- **SKU Mapping** (`sku_mappings`): liên kết `channel_listing` ↔ `sku` với `quantity` và `type`:
  - `single`: 1 listing ↔ 1 master SKU, `quantity` thường = 1 (hoặc N nếu listing là "lốc N cái").
  - `bundle`/combo: 1 listing ↔ **nhiều** dòng mapping (mỗi dòng 1 master SKU × quantity). Tồn của listing = `min( floor(available(sku_i) / quantity_i) )`.
  - Một master SKU có thể được **nhiều** listing (cùng/khác sàn) trỏ tới ⇒ đồng bộ tồn chéo sàn.

## 2. Ghép SKU — quy trình & auto-match

- Khi đồng bộ listing từ sàn về (`fetchListings`), nếu listing chưa có mapping ⇒ vào danh sách "Chưa ghép SKU".
- **Auto-match:** nếu `channel_listing.seller_sku` trùng `skus.sku_code` (chuẩn hoá: trim, upper, bỏ khoảng trắng) ⇒ gợi ý mapping `single` × 1; người dùng xác nhận một phát (hoặc bật auto-confirm).
- **Thủ công:** màn "Liên kết SKU" cho chọn master SKU (tìm theo code/tên/barcode) cho từng listing; tạo combo bằng cách thêm nhiều dòng.
- Listing đã ghép mà người dùng đổi mapping ⇒ ghi audit + tính lại tồn cần đẩy.
- Listing **chưa ghép** ⇒ không tự đẩy tồn (không biết trừ từ đâu); đơn của listing chưa ghép ⇒ `order_item.sku_id = null`, đơn bật `has_issue` ("đơn có SKU chưa ghép") cho tới khi ghép.

## 3. Trừ/nhả tồn theo vòng đời đơn (RULES)

| Sự kiện | Tác động (cho từng `order_item` có `sku_id`, ở kho được chọn) | Movement type |
|---|---|---|
| Đơn vào `pending`/`processing` lần đầu (chưa reserve) | `reserved += quantity` | `order_reserve` |
| Đơn bị `cancelled` / `returned_refunded` **trước** khi `shipped` | `reserved −= quantity` | `order_release` |
| Đơn vào `shipped` | `reserved −= quantity`, `on_hand −= quantity` | `order_ship` |
| Đơn `returned_refunded` **sau** khi `shipped`, hàng về kho (tuỳ cấu hình) | `on_hand += quantity` | `return_in` |
| Quét đóng gói xác nhận (nếu cấu hình trừ ở bước này thay vì `shipped`) | như `order_ship` | `order_ship` |
| Nhập kho (PO) | `on_hand += quantity` (+ tạo `cost_layers`) | `goods_receipt` |
| Điều chuyển kho | `on_hand −= q` ở kho A, `+= q` ở kho B | `transfer_out`/`transfer_in` |
| Kiểm kê lệch | `on_hand` chỉnh về thực tế | `stocktake_adjust` |
| Điều chỉnh tay | theo người dùng | `manual_adjust` |

- **Chọn kho để trừ:** mặc định kho mặc định của tenant; có thể cấu hình theo gian hàng / theo quy tắc; nếu một kho không đủ → cho phép trừ nhiều kho (tuỳ chọn) hoặc báo thiếu.
- **Chống oversell:** thao tác đọc-sửa tồn nằm trong transaction + `SELECT ... FOR UPDATE` trên `inventory_levels`, hoặc distributed lock Redis theo `sku_id`. Nếu `available` không đủ khi reserve (do bán vượt trên sàn) ⇒ vẫn reserve (đơn đã có thật) nhưng `on_hand`/`available` có thể âm tạm thời ⇒ bật cảnh báo "âm kho", và `available` đẩy lên sàn = `0`.
- **Combo:** reserve/ship một order_item là combo ⇒ tác động lên **tất cả** master SKU thành phần × quantity.
- Mỗi thay đổi ghi `inventory_movements` với `balance_after` (số dư sau thay đổi) để truy vết & đối chiếu.

## 4. Đẩy tồn lên sàn (PushStockToChannel)

```
[có thay đổi tồn của sku X] ─event InventoryChanged─▶ enqueue PushStockForSku(sku=X) với debounce key="push-stock:{tenant}:{sku}", delay ~5–15s
   job PushStockForSku:
     - tính available tổng của X (qua các kho được bán)
     - với mỗi channel_listing liên kết X (qua sku_mappings):
         tính channel_stock mong muốn:
           single  ⇒ floor(available_X / quantity)
           bundle  ⇒ min over các thành phần( floor(available_i / quantity_i) )
         nếu khác channel_listings.channel_stock hiện tại ⇒ enqueue PushStockToListing(listing) (queue: inventory-push, throttle per provider+shop)
   job PushStockToListing:
     - connector.updateStock(auth, external_sku_id, desired)
     - thành công ⇒ cập nhật channel_listings.channel_stock, last_pushed_at, sync_status=ok
     - thất bại ⇒ retry/backoff; quá hạn ⇒ sync_status=error + cảnh báo (giữ giá trị mong muốn để thử lại)
```

- **Debounce/coalesce** để không spam API khi nhiều đơn về liên tiếp.
- **Safety stock** trừ trước khi đẩy. Có thể đặt "đẩy 0 khi available ≤ ngưỡng" để tránh oversell mép.
- **Khoá đẩy** (tuỳ chọn per listing): người dùng có thể "ghim" tồn một listing (không tự đẩy) trong trường hợp đặc biệt.
- **Đồng bộ ngược (đối chiếu, không ghi đè):** job định kỳ đọc `channel_stock` thực trên sàn, so với mong muốn; lệch ⇒ cảnh báo + đẩy lại (không tự sửa master). Bật/tắt theo cấu hình.

## 5. Giá vốn (cho Finance, Phase 6) — *Implemented Phase 6.1 (SPEC-0014)*
- Mỗi lần `goods_receipt` được **confirm** tạo một `cost_layers` mới (`qty_received`, `unit_cost`, `received_at` = thời điểm confirm; idempotent qua `(source_type='goods_receipt', source_id=receipt.id, sku_id)`).
- Khi `order_ship` ⇒ `FifoCostService::consumeForShip()` rút layer theo **FIFO** (`SELECT … FOR UPDATE ORDER BY received_at ASC`, giảm `qty_remaining`, set `exhausted_at` khi hết); ghi 1 row `order_costs` **bất biến** (1-1 với `order_item`) gồm `cogs_total`, `cogs_unit_avg`, `layers_used` jsonb (chuỗi `[{layer_id, qty, unit_cost, synthetic?}]`). Nếu tồn FIFO không đủ ⇒ tạo synthetic layer dùng `Sku::effectiveCost()` (`average|latest`) + đánh dấu `synthetic=true` trong `layers_used` (không phá thanh tra).
- `OrderProfitService` đọc COGS từ `order_costs` cho đơn shipped (`cost_source: fifo`); đơn chưa ship dùng ước tính `Sku::effectiveCost()` (`cost_source: estimate`).
- Bình quân gia quyền (`average`) + `latest_receipt_cost` (theo cột `skus.cost_method`) giữ song song — dùng cho UI hiển thị "giá vốn hiệu lực" + làm fallback synthetic. Tenant chuyển sang FIFO không cần migration data: layer mới sinh từ thời điểm bật cho mỗi receipt mới; order_costs chỉ ghi khi ship.

## 6. Quan sát & cảnh báo
- Trang Tồn kho: lọc theo kho/SKU/sản phẩm, xem on_hand/reserved/available, lịch sử movements của từng SKU.
- Cảnh báo: sắp hết hàng (≤ ngưỡng), hết hàng, âm kho, tồn lâu không bán (slow-moving), listing có `sync_status=error`, listing chưa ghép SKU.
