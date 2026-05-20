# Shopee Open Platform — Push Mechanism (Webhook)

> Nguồn chính thức: https://open.shopee.com/developer-guide/18 · Last Updated (Shopee): 2023-01-16
> "Push Mechanism" trên Console = webhook.

## Cách hoạt động
1. Bạn subscribe push type + khai callback URL (Console → Push Mechanism → chọn App → Set Push, hoặc API `v2.push.set_push_config`).
2. Sự kiện xảy ra → Shopee gửi **HTTP POST** tới callback URL.
3. Push chỉ báo "data đã đổi" → nên **gọi API tương ứng để lấy chi tiết** (vd order_status push → gọi get_order_detail).
4. Verify callback: Shopee gửi 1 POST test tới URL khi bấm Verify; fail thì hiện banner đỏ lý do.

## ⭐ BẢNG MÃ PUSH CHÍNH THỨC (code → ý nghĩa)
| Code | Push | Nhóm | Ghi chú |
|---|---|---|---|
| **1** | **Shop Authorization Push** | Shopee | Seller **CẤP** quyền → trả list shop_id/merchant_id |
| **2** | **Shop Authorization Canceled Push** | Shopee | Seller **HỦY** quyền (deauthorized) → list shop/merchant id bị huỷ |
| **3** | **Order Status Update Push** | Order | Mọi cập nhật trạng thái đơn (gồm huỷ trước khi ship) |
| **4** | **Order TrackingNo Push** | Order | Tracking number cập nhật |
| **5** | Shopee Updates | Shopee | Tin cập nhật quan trọng từ Shopee |
| **6** | Banned Item Push | Product | Sản phẩm bị cấm + lý do |
| **7** | Item Promotion Push | Marketing | Tồn kho bị ảnh hưởng bởi campaign |
| **8** | Reserved Stock Change Push | Product | Tồn reserved cho campaign |
| **9** | Promotion Update Push | Marketing | |
| **10** | Webchat Push | Chat | Tin nhắn mới từ buyer |
| **11** | Video Upload Push | Product | Kết quả transcode video |
| **12** | **Open API Authorization Expiry Push** | Shopee | Báo trước **7 ngày** auth sắp hết hạn (auth chỉ 1 năm) → nhắc seller re-auth |
| **13** | Brand Register Result Push | Product | |
| **15** | **Shipping Document Status Push** | Order | Document "READY"/"FAILED" → khỏi poll get_shipping_document_result |

## Push theo loại App
- **Order Management** app subscribe: Code 1, 2, 12, 5 (Shopee) + 3, 4, 15 (Order).
- ERP System: tất cả trừ Webchat (10). Original: tất cả trừ Brand (13).

## 🔴 SỬA CONFIG DỰ ÁN — `integrations.shopee.webhook_event_types` đang SAI
Map hiện tại (đoán, đánh dấu "verify sandbox") bị lệch so với tài liệu chính thức:
| Code | Đang map (SAI) | Đúng theo docs |
|---|---|---|
| 1 | `shop_deauthorized` ❌ | **authorization GRANTED** (không phải deauth) → nên `unknown`/ignore hoặc xử lý cấp quyền |
| 2 | (chưa map) | **`shop_deauthorized`** ✅ (đây mới là huỷ quyền) |
| 3 | `order_status_update` ✅ | `order_status_update` ✅ |
| 4 | `product_update` ❌ | **`order_status_update`** (tracking no → re-fetch đơn) |
| 6 | `order_status_update` ❌ | **`product_update`** (banned item) |
| 12 | (chưa map) | auth expiry → nên báo reconnect (7 ngày trước hết hạn) |
| 15 | (chưa map) | shipping document ready/failed |

**→ Cần cập nhật `config/integrations.php` shopee `webhook_event_types`:**
```php
'webhook_event_types' => [
    1  => 'unknown',              // shop authorization GRANTED (không phải deauth)
    2  => 'shop_deauthorized',    // authorization CANCELED
    3  => 'order_status_update',
    4  => 'order_status_update',  // tracking number updated → re-fetch
    6  => 'product_update',       // banned item
    12 => 'shop_deauthorized',    // (hoặc 1 type 'reconnect_required' nếu thêm) — auth sắp hết hạn
    15 => 'unknown',              // shipping document status (xử lý ở luồng in nếu cần)
],
```
> Lưu ý: `ShopeeWebhookVerifier::parse` đọc `data` JSON-string → `ordersn`/`status` (đúng). Chỉ map code là sai.

## Chữ ký push (verify webhook)
- Tài liệu Push này (trang 18) tập trung vào **danh mục/mã code + cách subscribe**, không nêu chi tiết thuật toán chữ ký.
- Cách verify (từ research trước + SDK): header `Authorization` = `HMAC-SHA256(partner_key, push_url + "|" + raw_body)` hex. Shopee môi trường test cấp **"Test Push Partner Key" RIÊNG** (khác partner_key API) — dự án đã hỗ trợ qua `SHOPEE_PUSH_PARTNER_KEY` / `marketplace.shopee.push_partner_key`.
- ⚠️ Chi tiết chữ ký push **nên verify lại** ở trang API reference / khi test sandbox (trang này không khẳng định công thức ký).
