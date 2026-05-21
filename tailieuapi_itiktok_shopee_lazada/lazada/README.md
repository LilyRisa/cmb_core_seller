# Lazada Open Platform — API Reference (Bộ tài liệu đầy đủ)

Tài liệu API của **Lazada Open Platform** được cào tự động bằng trình duyệt Playwright (Chromium) từ trang
[`https://open.lazada.com/apps/doc/api`](https://open.lazada.com/apps/doc/api?path=%2Fauth%2Ftoken%2Fcreate).
Trang gốc chặn việc fetch HTTP thông thường / AI fetch, nên toàn bộ dữ liệu được lấy bằng cách render bằng trình duyệt thật,
bung toàn bộ cây danh mục bên trái rồi truy cập từng endpoint.

- **Tổng số endpoint:** 383 / 383 (0 lỗi)
- **Số nhóm danh mục (top-level):** 34
- **Ngày cào:** 2026-05-21
- **Mỗi file** tương ứng một API endpoint, chứa: mô tả, Service Endpoints, Common Parameters, Parameters,
  Response Parameters, Error Codes và code mẫu (JAVA / PHP / .NET / RUBY / PYTHON / cURL) + response mẫu.

## Cách dùng
- Xem danh sách đầy đủ theo nhóm: [`INDEX.md`](./INDEX.md)
- Mỗi endpoint có metadata ở đầu file: `Source` (URL gốc), `API path`, `Category`.
- `_manifest.json` / `_paths.json`: dữ liệu máy đọc (danh sách endpoint + nhóm danh mục).

## Lưu ý về xác thực & ký request (signing)
Mọi API của Lazada dùng các **Common Parameters** chung: `app_key`, `timestamp`, `sign_method`, `sign`
(và `access_token` cho hầu hết endpoint sau khi đã ủy quyền). Tham số `sign` là chữ ký HMAC của request —
xem chi tiết trong endpoint `/auth/token/create` và tài liệu signing được liên kết trong các file.
Endpoint lấy token: [`/auth/token/create`](./apps_doc_api_path_2Fauth_2Ftoken_2Fcreate.md).

## Các nhóm API (theo số lượng endpoint)

| Nhóm danh mục | Số endpoint |
| --- | --- |
| FBL API | 49 |
| Lazada Logistics API | 29 |
| Product API | 28 |
| Sponsored Solutions API | 28 |
| LazPay API | 25 |
| Logistics Station API | 18 |
| Seller API | 17 |
| LazLike API | 13 |
| Choice Customized API | 12 |
| Free Shipping API | 11 |
| Cross Boarder Product API | 11 |
| Membership API | 10 |
| Logistics API | 9 |
| FirstMile Bigbag (only for CN) | 9 |
| Seller Voucher API | 9 |
| Flexicombo API | 9 |
| Fulfillment API | 9 |
| RedMart API | 8 |
| E-Tickets API | 8 |
| Order API | 8 |
| Return and Refund API | 8 |
| Lazada DG API | 7 |
| Content API | 7 |
| Instant Messaging API | 7 |
| Early Bird Price API | 7 |
| Media Center API | 6 |
| Lazada Wallet Corporate Top-up API | 5 |
| System API | 4 |
| Finance API | 4 |
| Product Review API | 3 |
| Service Market API | 2 |
| Store Flash Sale API | 1 |
| Store Decoration API | 1 |
| LazLive API | 1 |
| **Tổng** | **383** |

> Tài liệu này được sinh tự động phục vụ tham khảo offline. Nguồn chính thức luôn là Lazada Open Platform.
