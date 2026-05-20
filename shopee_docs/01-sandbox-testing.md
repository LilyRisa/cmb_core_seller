# Shopee Open Platform — Sandbox Testing (V2)

> Nguồn chính thức: https://open.shopee.com/developer-guide/644 · Last Updated (Shopee): 2025-09-16
> Thu thập bằng Playwright (trang chặn crawler thường).

## ⭐ KẾT LUẬN QUAN TRỌNG NHẤT (giải thích lỗi `error_sign`)

**Host API của môi trường Sandbox KHÔNG phải `partner.test-stable.shopeemobile.com`** (giá trị này lấy từ SDK cộng đồng — sai/cũ). Host sandbox chính thức:

| Vùng | Sandbox API host |
|---|---|
| Chung (SG/SEA — gồm **Việt Nam**) | `https://openplatform.sandbox.test-stable.shopee.sg/` |
| Trung Quốc (CN) | `https://openplatform.sandbox.test-stable.shopee.cn/` |

→ **Việt Nam (non-CN) dùng host `.sg`**: `https://openplatform.sandbox.test-stable.shopee.sg/`.

Ngoài ra Sandbox cần **bộ riêng**, KHÔNG dùng partner_id/key của app production trên `open.shopee.com`:
- **Test `partner_id`** riêng (cấp khi tạo app + sandbox account).
- **Sandbox Shop Account** riêng (KHÔNG đăng nhập bằng tài khoản live — nếu dùng live sẽ lỗi "Account/Password Verification Failed").

## 1. Sandbox là gì
Môi trường test do Open Platform cấp, có sẵn test account + test data. Phủ các nghiệp vụ lõi: quản lý sản phẩm, đơn hàng, logistics/giao hàng. Có 2 loại: **Sandbox v1** và **Sandbox v2** (mới). Sau khi đăng nhập Open Console → kiểm tra mục **Test Account** để biết là sandbox mới (Test Account-Sandbox v2).

**Phạm vi hỗ trợ Sandbox V2:**
- **Console**: tạo test shop account; tạo test order; đẩy (push) test data.
- **Seller Center**: tạo & quản lý global SKU / shop SKU; xử lý Order; Ship Order (in phiếu KHÔNG hỗ trợ ở Seller Center — phải dùng Open API để in).
- **Open API**: Product / Global Product / Media Space / Order / Logistics / First Mile / Shop / Merchant — **tất cả API**. Gọi qua API Test Tools trong Console, hoặc tự gọi tới host:
  - `https://openplatform.sandbox.test-stable.shopee.sg/` (chung)
  - `https://openplatform.sandbox.test-stable.shopee.cn/` (CN)
- **Push**: nhận một số push test data.

## 2. Tạo test account
- Console → **Test Account-Sandbox v2** → tạo test account (chọn loại **shop** hoặc **merchant**).
- **Local vs cross-border test store**: khác nhau về category, kênh vận chuyển, logic đặc thù → nên tạo test store đúng thị trường dịch vụ (VN ⇒ tạo store VN).
- **Merchant** (CNSC — China Seller Center): tạo main test account + bind merchant/store (chỉ cho cross-border TQ).

## 3. Ủy quyền (authorize) test account cho test partner_id

**Link ủy quyền Sandbox (cố định) — KHÁC link production:**
```
https://open.sandbox.test-stable.shopee.com/auth?auth_type=seller&partner_id=***&redirect_uri=<ENCODED_REDIRECT>&response_type=code
```
(Merchant/CN: `https://open.sandbox.test-stable.shopee.cn/auth?...`)

- Đăng nhập bằng **Sandbox Shop Account** (KHÔNG dùng live account — sẽ lỗi "Account/Password Verification Failed").
- Trang login sandbox: `https://account.sandbox.test-stable.shopee.com`.
- Merchant: chọn "main account"; mã xác thực (OTP) = **`123456`**.
- Sau khi Authorize → nhảy tới trang success (kèm `code` ở redirect).

## 4. Quy trình test (shop account)
1. Console → Test Account-Sandbox → "Login Seller Center" của test store.
2. Tạo test product (Seller Center hoặc Open API).
3. Console → Test Order → **Create Test Order** (chọn Shop → Select Item → Shipping Option → Create).
4. Xem đơn ở Seller Center → My Orders. **Lưu ý: sau khi tạo đơn ở Console, chờ ~5 phút mới làm bước tiếp.**
5. **Ship**: Arrange shipment → chọn pickup/dropoff → tracking number tự sinh → trạng thái đơn = **PROCESSED**. (Thao tác ở tab "To Ship"; tab "All" có thể không thao tác được.)
6. **In phiếu**: Seller Center KHÔNG in được — phải dùng **API** (in được sau khi ship thành công và trước khi đơn sang `SHIPPED`, không giới hạn số lần).
7. **Chuyển trạng thái đơn** (Console → Test Order):
   - Click "Pickup" → đơn tự sang **SHIPPED** (cần đã Arrange Shipment ở Seller Center hoặc gọi `/api/v2/logistics/ship_order`; chỉ Pickup được khi đơn ở PROCESSED).
   - Click "Deliver" → đơn sang **TO_CONFIRM_RECEIVE** (cần đơn đang ở SHIPPED).

## 5. Áp dụng cho dự án (CMBcoreSeller)
- Để test sandbox: phải lấy **test partner_id + test partner_key** từ Console sandbox (Test Account-Sandbox v2), KHÔNG dùng creds app production.
- Đặt `SHOPEE_API_BASE_URL=https://openplatform.sandbox.test-stable.shopee.sg` (VN), `SHOPEE_PARTNER_ID/KEY` = bộ test.
- Link ủy quyền connector tự dựng trỏ host `base_url` → cần là host sandbox `.sg` ở trên (KHÔNG phải `partner.test-stable.shopeemobile.com`).
- Khi lên production: `SHOPEE_API_BASE_URL=https://partner.shopeemobile.com` + creds production (app đã được duyệt).
