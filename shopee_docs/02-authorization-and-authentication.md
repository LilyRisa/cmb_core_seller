# Shopee Open Platform — Authorization & Authentication

> Nguồn chính thức: https://open.shopee.com/developer-guide/20 · Last Updated (Shopee): 2026-05-13
> Thu thập bằng Playwright.

## ⭐ HOST theo môi trường + vùng (BẢNG CHÍNH THỨC — quan trọng nhất)

### Link ủy quyền (auth link — seller bấm vào, hiển thị trên trình duyệt)
| Environment | Region | Fixed authorization URL |
|---|---|---|
| **Production** | **Global (gồm Việt Nam, trừ Trung Quốc & Brazil)** | `https://open.shopee.com/auth` |
| Production | Mainland China | `https://open.shopee.cn/auth` |
| Production | Brazil | `https://open.shopee.com.br/auth` |
| **Sandbox** | **Global (gồm Việt Nam, trừ CN & BR)** | `https://open.sandbox.test-stable.shopee.com/auth` |
| Sandbox | Mainland China | `https://open.sandbox.test-stable.shopee.cn/auth` |
| Sandbox | Brazil | `https://open.sandbox.test-stable.shopee.com.br/auth` |

### Host gọi API (server-to-server, ký sign)
| Environment | API gateway host |
|---|---|
| **Production** | `https://partner.shopeemobile.com` |
| **Sandbox (Global/VN)** | `https://openplatform.sandbox.test-stable.shopee.sg` |
| Sandbox (CN) | `https://openplatform.sandbox.test-stable.shopee.cn` |

> 🔴 **`partner.test-stable.shopeemobile.com` (giá trị từ SDK cộng đồng) là SAI/cũ — không phải host sandbox.** Demo PHP/Go chính thức của Shopee dùng `https://openplatform.sandbox.test-stable.shopee.sg` cho sandbox. Đặt sai host này → `error_sign`.

## Hai cách tạo auth link
1. **Mới (khuyến nghị)** — không cần sign:
   ```
   https://open.shopee.com/auth?partner_id={pid}&auth_type=seller&redirect_uri={uri}&response_type=code[&state={csrf}]
   ```
   (Sandbox VN: `https://open.sandbox.test-stable.shopee.com/auth?...`)
2. **Legacy (`auth_partner`)** — CÓ sign+timestamp (đây là cách connector hiện tại của dự án đang dùng, vẫn hợp lệ):
   ```
   {API_HOST}/api/v2/shop/auth_partner?partner_id={pid}&timestamp={ts}&sign={sign}&redirect={uri}
   ```
   - Demo chính thức (PHP): `$host="https://openplatform.sandbox.test-stable.shopee.sg"; $sign=hash_hmac('sha256', partnerId.path.timestamp, partnerKey);`

**Tham số auth link (cách mới):**
| Param | Bắt buộc | Mô tả |
|---|---|---|
| `partner_id` | ✓ | partner_id của App |
| `auth_type` | ✓ | `seller` (shop/merchant) / `supplier` (SCS) / `user` (livestream) |
| `redirect_uri` | ✓ | URL nhận `code` sau khi seller đồng ý. **Domain phải khớp "Redirect URL Domain" khai trong Console** (xem dưới) |
| `response_type` | ✓ | cố định `code` |
| `state` | – | chuỗi random chống CSRF, trả lại nguyên văn ở redirect |

## ⚠️ Redirect URL Domain Validation (đúng lỗi bạn gặp trước đó)
Phải khai **Test Redirect URL Domain** (cho sandbox) và **Live Redirect URL Domain** (cho production) cho mỗi App trong Console. Nếu domain của `redirect_uri` (hoặc `redirect` ở link legacy) **không khớp** domain đã khai → lỗi:
> "The domain of redirect_uri is not consistent with the Redirect URL Domain declared in console"

(App chưa khai domain ⇒ Shopee tạm chưa enforce.)

## Sau khi ủy quyền
Trình duyệt redirect về `redirect_uri` kèm:
- Shop account: `?code=xxxx&shop_id=xxxx`
- Main account: `?code=xxxx&main_account_id=xxxx`

| Param | Mô tả |
|---|---|
| `code` | dùng để lấy access_token; **chỉ dùng 1 lần, hết hạn sau 10 phút** |
| `shop_id` | id shop vừa ủy quyền (shop account) |
| `main_account_id` | id main account (main account; cross-border) |

- **Auth validity tối đa 365 ngày**, seller chọn (7/30/90/180/365 ngày hoặc custom).
- Sub-account KHÔNG đăng nhập trang ủy quyền được.
- SIP: CB SIP primary shop ủy quyền ⇒ các SIP linked shop tự nhận ủy quyền (quyền API hạn chế).

## GetAccessToken (đổi code → token)
| Env | Path |
|---|---|
| Production | `https://partner.shopeemobile.com/api/v2/auth/token/get` |
| Sandbox (VN) | `https://openplatform.sandbox.test-stable.shopee.sg/api/v2/auth/token/get` |

- Method **POST**, sign theo **Public API** base string (`partner_id + path + timestamp`).
- Common params (query): `partner_id`, `timestamp`, `sign`.
- Body JSON: `{ "code": ..., "shop_id": <1 shop> | "main_account_id": ..., "partner_id": ... }`.
- Response: `access_token` (4h, `expire_in` giây), `refresh_token` (30 ngày), `shop_id_list`/`merchant_id_list` (nếu main account), `error`, `request_id`, `message`.

## RefreshAccessToken
- `/api/v2/auth/access_token/get`, body `{refresh_token, partner_id, shop_id|merchant_id}`.
- `access_token` 4h; sau khi sinh token mới, token cũ còn hiệu lực thêm 5 phút.
- `refresh_token` 30 ngày; phải refresh **trong** thời hạn ủy quyền. Re-auth làm mới cả refresh_token + access_token.
- Lưu access_token/refresh_token **riêng theo từng** shop_id/merchant_id/user_id/supplier_id.

## ⭐ Thuật toán SIGN (chính thức — khớp 100% với `ShopeeSigner` của dự án)
Nối **api path (không host)** + common params theo thứ tự, rồi HMAC-SHA256 với `partner_key`, **hex chữ thường**:
- **Public API**: `partner_id + api_path + timestamp`
- **Shop API**: `partner_id + api_path + timestamp + access_token + shop_id`
- **Merchant API**: `partner_id + api_path + timestamp + access_token + merchant_id`

`timestamp` (giây) chỉ hợp lệ trong **5 phút**.

Demo PHP chính thức (sandbox):
```php
$host = "https://openplatform.sandbox.test-stable.shopee.sg";
$path = "/api/v2/shop/auth_partner";
$timest = time();
$baseString = sprintf("%s%s%s", $partnerId, $path, $timest);   // public
$sign = hash_hmac('sha256', $baseString, $partnerKey);
$url = "$host$path?partner_id=$partnerId&timestamp=$timest&sign=$sign&redirect=$redirectUrl";
```

## Áp dụng cho dự án (sửa lỗi `error_sign`)
1. **Sandbox VN**: `SHOPEE_API_BASE_URL=https://openplatform.sandbox.test-stable.shopee.sg` (KHÔNG phải `partner.test-stable.shopeemobile.com`).
2. Dùng **test partner_id + test partner_key** từ Console sandbox (Test Account-Sandbox v2) — không dùng creds app production.
3. Khai **Test Redirect URL Domain** = domain app (`app.cmbcore.com`) trong Console.
4. Production: `SHOPEE_API_BASE_URL=https://partner.shopeemobile.com` + creds production (app đã duyệt).
5. `ShopeeSigner` (public/shop base string) đã ĐÚNG theo tài liệu — không phải sửa thuật toán ký.
