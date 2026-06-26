# Tài liệu API CMBcoreSeller

API cho phép tích hợp hệ thống ngoài (ERP, Zapier, phần mềm tự xây...) thao tác gian hàng **như trên web**.

## 1. Xác thực bằng API key

1. Chủ gian hàng (owner) vào **Cài đặt → API & Tích hợp → Tạo API key** (chỉ owner mới tạo/xem/xóa được).
2. Đặt tên + thời hạn, nhấn **Tạo**. Token hiện **1 lần duy nhất** — sao chép & lưu nơi an toàn.
3. Mọi request gửi header:

```
Authorization: Bearer <API_KEY>
```

> API key đã **gắn cứng gian hàng** của bạn — KHÔNG cần gửi `X-Tenant-Id`. Key có toàn quyền như tài khoản owner, hãy giữ bí mật như mật khẩu. Có thể đặt thời hạn và **thu hồi (xóa)** bất cứ lúc nào.

## 2. Quy ước chung

- **Base URL:** `https://<tên-miền>/api/v1`
- **Định dạng:** JSON. Thành công: `{ "data": ..., "meta": ... }`. Lỗi: `{ "error": { "code", "message", "trace_id", "details" } }`.
- **Tiền tệ:** số nguyên VND (không số thập phân).
- **Thời gian:** ISO-8601 UTC (vd `2026-06-26T03:37:21Z`).
- **Trạng thái đơn:** trả `code` (mã chuẩn) + `status_label` + `raw_status`.
- **Rate limit:** 120 request/phút. Quá ngưỡng → HTTP 429.
- **Phân trang:** `?page=`, `?per_page=` → `meta.pagination { page, per_page, total, total_pages }`.

## 3. Endpoint chính

### Đơn hàng

```bash
# Danh sách đơn (lọc: status, source, q, placed_from, placed_to, page, per_page)
curl -H "Authorization: Bearer <API_KEY>" \
  "https://<domain>/api/v1/orders?status=processing&per_page=50"

# Chi tiết 1 đơn (kèm items)
curl -H "Authorization: Bearer <API_KEY>" \
  "https://<domain>/api/v1/orders/{id}?include=items"

# Tạo đơn thủ công
curl -X POST -H "Authorization: Bearer <API_KEY>" -H "Content-Type: application/json" \
  -d '{"buyer":{"name":"Nguyễn A","phone":"0912345678"},"items":[{"sku_id":1,"quantity":2,"unit_price":150000}],"shipping_fee":20000}' \
  "https://<domain>/api/v1/orders"

# Chuẩn bị hàng (tạo vận đơn / lấy phiếu giao hàng)
curl -X POST -H "Authorization: Bearer <API_KEY>" \
  "https://<domain>/api/v1/orders/{id}/ship"
```

### Sản phẩm & tồn kho

```bash
# Sản phẩm / SKU
curl -H "Authorization: Bearer <API_KEY>" "https://<domain>/api/v1/products"
curl -H "Authorization: Bearer <API_KEY>" "https://<domain>/api/v1/skus"

# Tồn kho theo SKU
curl -H "Authorization: Bearer <API_KEY>" "https://<domain>/api/v1/inventory"
```

### Vận đơn (fulfillment)

```bash
# Danh sách vận đơn
curl -H "Authorization: Bearer <API_KEY>" "https://<domain>/api/v1/shipments"
```

## 4. Mã lỗi thường gặp

| HTTP | Ý nghĩa |
|---|---|
| 401 | Thiếu / sai / hết hạn API key (token đã thu hồi). |
| 403 | Không đủ quyền (hiếm — API key có toàn quyền owner). |
| 404 | Không tìm thấy tài nguyên trong gian hàng. |
| 422 | Dữ liệu gửi lên không hợp lệ (xem `error.details`). |
| 429 | Vượt rate limit — thử lại sau. |

> Danh sách endpoint đầy đủ liên tục cập nhật. Liên hệ hỗ trợ nếu cần endpoint chưa có trong tài liệu này.
