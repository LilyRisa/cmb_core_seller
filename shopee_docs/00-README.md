# Shopee Open Platform API — Tài liệu chính thức (đã lọc cho thị trường Việt Nam)

> Thu thập từ `https://open.shopee.com/developer-guide` bằng **Playwright** (trang chặn crawler thường). Đã **lọc** ra phần liên quan connector đa sàn (OAuth, đơn, webhook, fulfillment, tồn kho) cho **seller Việt Nam (local, non-CN)**. Mỗi file ghi rõ URL nguồn + ngày Shopee cập nhật.

## Các file đã tạo
| File | Nội dung | Nguồn |
|---|---|---|
| `01-sandbox-testing.md` | Môi trường sandbox: host, test account, link ủy quyền sandbox, luồng test đơn | /developer-guide/644 |
| `02-authorization-and-authentication.md` | OAuth, bảng host theo môi trường+vùng, **thuật toán sign**, token API, redirect domain validation | /developer-guide/20 |
| `03-push-mechanism-webhook.md` | Webhook: **bảng mã push (code 1–15)**, subscribe, theo loại app | /developer-guide/18 |
| `04-order-management.md` | Entity order/package/item, trạng thái, API đơn + luồng ship + AWB | /developer-guide/229 |
| `05-stock-and-price.md` | get/update stock & price, `stock_info_v2`, bội số giá variant theo vùng | /developer-guide/223 |

## ⭐ KẾT LUẬN QUAN TRỌNG NHẤT (giải `error_sign` của bạn)

**Host sandbox bạn đang đặt (`partner.test-stable.shopeemobile.com`) là SAI** — đó là giá trị từ SDK cộng đồng, không phải host Shopee chính thức. Theo demo PHP/Go **chính thức** của Shopee, host đúng:

| Môi trường | Vùng VN | API host (gọi server, ký sign) | Auth link (seller bấm) |
|---|---|---|---|
| **Sandbox** | VN (Global) | `https://openplatform.sandbox.test-stable.shopee.sg` | `https://open.sandbox.test-stable.shopee.com/auth` |
| **Production** | VN (Global) | `https://partner.shopeemobile.com` | `https://open.shopee.com/auth` |

Ngoài ra **sandbox cần bộ TEST partner_id + TEST partner_key + Sandbox Shop Account RIÊNG** (tạo ở Console → Test Account-Sandbox v2), KHÔNG dùng creds app production.

→ **Cách sửa nhanh để test sandbox:** `SHOPEE_API_BASE_URL=https://openplatform.sandbox.test-stable.shopee.sg` + nhập **test partner_id/key**. Hoặc test production: `SHOPEE_API_BASE_URL=https://partner.shopeemobile.com` + creds production (app vẫn ở test status vẫn ký + ủy quyền shop của bạn được).

## Đặc thù Việt Nam (xác nhận từ docs)
- Region: **Global (không phải CN, không phải BR)** → dùng host `.sg` (sandbox) / `partner.shopeemobile.com` (prod), auth `open.shopee.com/auth`.
- Bội số giá giữa các variant: **VN = 5** (giá cao nhất / thấp nhất ≤ 5).
- Sandbox: tạo **local test store** đúng thị trường (VN). Logistics sandbox thường chỉ pickup/dropoff.

## Thuật toán SIGN (khớp 100% code dự án — KHÔNG phải bug)
- Public API: `partner_id + api_path + timestamp`
- Shop API: `partner_id + api_path + timestamp + access_token + shop_id`
- HMAC-SHA256 với `partner_key`, hex chữ thường. `timestamp` (giây) hợp lệ 5 phút.
- `ShopeeSigner` của dự án đã đúng. Lỗi `error_sign` của bạn là do **sai host + sai bộ creds môi trường**, không phải thuật toán.

## 🔧 Bug/điểm cần sửa trong connector dự án (phát hiện khi đối chiếu docs)
1. **Sandbox host sai** trong config/compose: `partner.test-stable.shopeemobile.com` → đổi thành `https://openplatform.sandbox.test-stable.shopee.sg` (xem `02-...md`).
2. **`webhook_event_types` map sai** (xem `03-...md`): code `1` là ủy quyền GRANTED (không phải deauth); `2` mới là deauth; `4` là order tracking (không phải product); `6` là banned item (product). Cần cập nhật map + thêm `12` (auth sắp hết hạn), `15` (shipping doc ready).
3. **updateStock no-variant**: bỏ `model_id` khi hàng không variant (hiện gửi `model_id:0`).
4. (Tuỳ chọn) dùng `v2.order.search_package_list` (package_status=2) để lấy package ship chuẩn hơn (hiện arrange theo order_sn).

→ Các điểm 1–2 ảnh hưởng vận hành thật; nên sửa trước khi bật prod. Đây là việc CODE — tách commit riêng, có test, không gộp với việc thu thập tài liệu này.

## Mục lục đầy đủ (các phần KHÁC chưa thu thập — báo nếu cần)
Getting Started: Introduction · Developer account registration · App management · API calls · **Push Mechanism**(✓) · **Authorization**(✓) · **Sandbox Testing**(✓) · V2 Service Partner Program · V2.0 API Call Flow · V2.0 Data Definition · Requesting Access to Sensitive Data · API Best Practices.
Guides: Creating Product · Variant management · Product base info · **Stock & Price**(✓) · **Order Management**(✓) · First Mile Binding · Return Refund Management · SIP best practices · Ads · Xpress · Livestream · (Brazil-specific — bỏ qua cho VN).
> Nói tên phần là mình mở Playwright lấy tiếp + tạo file.
