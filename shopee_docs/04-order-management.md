# Shopee Open Platform — Order Management

> Nguồn chính thức: https://open.shopee.com/developer-guide/229 · Last Updated (Shopee): 2025-09-24

## 1. Thực thể (Entity)
- **Order**: tạo sau checkout; 1 order có thể nhiều item.
- **Package**: tạo sau order; là đơn vị **giao hàng**. 1 order có thể tách nhiều package; 1 package nhiều item.
- **Item**: sản phẩm trong order (kèm quantity...). Item nằm trong package để ship.

## 2. Trạng thái đơn (order_status) + Package Fulfillment Status
Order status (đối chiếu `ShopeeStatusMap` của dự án): `UNPAID → READY_TO_SHIP → PROCESSED → (RETRY_SHIP) → SHIPPED → TO_CONFIRM_RECEIVE → COMPLETED`; nhánh huỷ/trả: `IN_CANCEL`, `CANCELLED`, `TO_RETURN`.
Package fulfillment status: `LOGISTICS_READY → LOGISTICS_REQUEST_CREATED` (pickup/dropoff) hoặc `LOGISTICS_PICKUP_DONE` (non_integrated) → ... → SHIPPED.

## 3. API lấy đơn
- `v2.order.get_order_list` — danh sách đơn theo `order_status`, cửa sổ thời gian (`time_range_field`=create_time/update_time, `time_from`/`time_to`, **max 15 ngày**), `page_size`, `cursor`.
- `v2.order.get_order_detail` — chi tiết đơn (`order_sn_list` ≤50, `response_optional_fields`).

→ Connector dự án dùng đúng 2 API này (chia cửa sổ ≤15 ngày + cursor). ✓

## 4. Huỷ đơn
- `v2.order.cancel_order` — seller huỷ.
- `v2.order.handle_buyer_cancellation` — xử lý yêu cầu huỷ của buyer.

## 5. Tách đơn (split)
- `v2.order.split_order` — chỉ khi status = `READY_TO_SHIP`; body `{order_sn, package_list:[{item_list:[{item_id, model_id, order_item_id, promotion_group_id}]}]}`.
  - Cần quyền "split order" (xin Shopee business manager nếu lỗi "You don't have the permission to split order").
  - Tối đa 30 parcel (TW) / 5 parcel (vùng khác). Không tách được bundle/add-on (trừ seller chọn lọc).
- `v2.order.unsplit_order` — huỷ tách (khi chưa parcel nào ship).

## 6. Lấy package để ship
- `v2.order.search_package_list` — **API ưu tiên** để lấy package chưa SHIPPED (filter + sort). Lấy package `package_status = 2 (ToProcess)`.
- `v2.order.get_package_detail` — chi tiết package.

## 7. ⭐ Luồng ship hàng (Shipment API Call Flow)
1. `v2.order.search_package_list` → lấy package `ToProcess` (status=2).
2. `v2.logistics.get_shipping_parameter` (1 package) / `get_mass_shipping_parameter` (nhiều package cùng channel+kho) → biết cần `pickup` / `dropoff` / `non_integrated`.
3. `v2.logistics.ship_order` (1) / `mass_ship_order` (nhiều) — chọn 1 trong pickup/dropoff/non_integrated. Non_integrated phải tự up `tracking_number`.
   - pickup/dropoff: fulfillment status `LOGISTICS_READY → LOGISTICS_REQUEST_CREATED`.
   - non_integrated: → `LOGISTICS_PICKUP_DONE` ngay.
4. `v2.logistics.get_tracking_number` (1) / `get_mass_tracking_number` (nhiều) — lấy tracking (channel tích hợp).
5. In AWB (in được sau khi arrange shipment, **trước** khi fulfillment status = `LOGISTICS_PICKUP_DONE`):
   - **Self-print**: `v2.logistics.get_shipping_document_data_info`.
   - **Shopee-generated** (4 API tuần tự): `get_shipping_document_parameter` → `create_shipping_document` → `get_shipping_document_result` → `download_shipping_document`.
6. TW đặc thù: `get_shipping_parameter` trả `slug` → phải gửi `slug` khi `ship_order`. Channel 黑猫宅急便(30001) không cần in AWB.

→ Connector dự án (`arrangeShipment`: get_shipping_parameter → ship_order → get_tracking_number; `getShippingDocument`: create → poll get_result → download) **khớp luồng chính thức**. ✓ Có thể bổ sung `search_package_list` để lấy package chuẩn hơn (hiện connector arrange theo order_sn).

## 8. Liên quan dự án
- Tên API + tham số khớp connector hiện tại. Cân nhắc nâng cấp: dùng `search_package_list` (package_status=2) thay vì tự suy package, và push code **15** (Shipping Document Status) để khỏi poll `get_shipping_document_result`.
- Chi tiết field từng API: xem **API reference** (mục riêng trên Console, https://open.shopee.com/documents).
