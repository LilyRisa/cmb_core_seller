# Shopee Open Platform — Stock & Price Management

> Nguồn chính thức: https://open.shopee.com/developer-guide/223 · Last Updated (Shopee): 2024-03-18

## 1. Lấy giá / tồn kho sản phẩm
- Không có variant: `v2.product.get_item_base_info`.
- Có variant: `v2.product.get_model_list`.
- `price_info`: `{current_price, original_price, inflated_price_of_*, currency}`. (ID/CO/PL: inflated = giá có thuế; vùng khác: inflated = giá thường.) Khuyến mãi → `current_price` = giá KM; không thì = `original_price`.
- `stock_info_v2`: `{summary_info:{total_reserved_stock, total_available_stock}, seller_stock:[{location_id, stock}], shopee_stock:[...]}`. Sản phẩm có thể có cả seller_stock + shopee_stock / nhiều location.

→ Connector dự án `fetchListings` đọc `stock_info_v2.summary_info.total_available_stock` + `price_info[0].current_price`. ✓ khớp.

## 2. Cập nhật tồn kho — `v2.product.update_stock`
- Chỉ cập nhật **`seller_stock`** (KHÔNG sửa được `shopee_stock`).
- 1 call chỉ 1 `item_id` (nhiều item → gọi nhiều lần). Có variant → up nhiều model trong 1 call.
- Kiểm `stock_limit` qua `v2.product.get_item_limit`.

**Body — không variant:**
```json
{ "item_id": 1000, "stock_list": [{ "seller_stock": [{ "stock": 100 }] }] }
```
**Body — có variant:**
```json
{ "item_id": 2000, "stock_list": [{ "model_id": 3456, "seller_stock": [{ "stock": 100 }] }] }
```

→ Connector dự án gửi `{item_id, stock_list:[{model_id, seller_stock:[{stock}]}]}`. ✓ Với hàng **không variant**, tài liệu **bỏ `model_id`** (connector hiện gửi `model_id:0`) — nên cân nhắc bỏ `model_id` khi no-variant để khớp tuyệt đối (refinement nhỏ).

## 3. Cập nhật giá — `v2.product.update_price`
- Body: `{item_id, price_list:[{model_id?, original_price}]}`. 1 call 1 item_id.
- Dự án **không** implement update price (capability `listings.updatePrice=false`) — đúng chủ ý.

## ⭐ Lưu ý vùng (gồm Việt Nam)
- **Chênh lệch giá giữa các variant**: giá cao nhất / giá thấp nhất **không vượt bội số theo vùng**:
  | Vùng | Bội số |
  |---|---|
  | BR | 4 |
  | **SG / VN / TW / TH / PH / MX** | **5** |
  | ID / MY | 7 |
  | CL / CO | 9 |
  | CNSC | 7 |
- Sản phẩm đang trong khuyến mãi: **không sửa được `original_price`** (xem FAQ 140).

## 4. Global product (chỉ cross-border CNSC/KRSC)
`v2.global_product.update_stock` / `update_price` — không áp dụng cho seller VN local.
