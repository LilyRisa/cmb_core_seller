# Trạng thái đơn hàng chuẩn — State Machine

**Status:** Stable (mapping chi tiết hoàn thiện dần theo từng sàn) · **Cập nhật:** 2026-05-11

> Một nguồn sự thật cho trạng thái đơn. Mọi sàn map về tập trạng thái này. Mỗi đơn lưu `status` (mã chuẩn) + `raw_status` (chuỗi gốc từ sàn). Đổi trạng thái → ghi 1 dòng `order_status_history`.

## 1. Tập trạng thái chuẩn

| Mã (`status`) | Tên hiển thị | Ý nghĩa |
|---|---|---|
| `unpaid` | Chờ thanh toán | Đơn tạo nhưng chưa thanh toán (đơn online chưa trả tiền) |
| `pending` | Chờ xử lý | Đã thanh toán / COD đã xác nhận, chưa bắt đầu chuẩn bị hàng |
| `processing` | Đang xử lý | Đang soạn/đóng hàng |
| `ready_to_ship` | Chờ bàn giao | Đã tạo vận đơn (có tracking), chờ giao cho ĐVVC |
| `shipped` | Đang vận chuyển | Đã bàn giao ĐVVC / đang trên đường |
| `delivered` | Đã giao | Giao thành công cho người nhận |
| `completed` | Hoàn tất | Qua thời gian khiếu nại / đã đối soát xong |
| `delivery_failed` | Giao thất bại | ĐVVC giao không thành công, chờ xử lý lại |
| `returning` | Đang trả/hoàn | Có yêu cầu trả hàng/hoàn tiền đang xử lý |
| `returned_refunded` | Đã trả/hoàn | Đã hoàn tiền và/hoặc hàng đã về |
| `cancelled` | Đã huỷ | Đơn bị huỷ (trước khi giao) |

Phụ trợ (cờ riêng, không nằm trong chuỗi chính): `payment_status` (`unpaid`/`paid`/`refunded`/`partial_refund`), `is_split` (đơn nhiều kiện), `has_issue` (cờ cảnh báo).

## 2. Sơ đồ chuyển trạng thái (đường "hạnh phúc" + nhánh)

```
 unpaid ──pay──▶ pending ──start prep──▶ processing ──create AWB──▶ ready_to_ship
                                                                        │ handover
                                                                        ▼
                                                                     shipped ──┬──▶ delivered ──▶ completed
                                                                               │
                                                                               └──fail──▶ delivery_failed ──retry──▶ shipped
 (bất kỳ trạng thái trước shipped) ──cancel──▶ cancelled
 (delivered/shipped) ──buyer request──▶ returning ──▶ returned_refunded
```

## 3. Quy tắc chuyển trạng thái (RULES)

1. **Nguồn dữ liệu sàn = nguồn sự thật.** Nếu sàn báo trạng thái "lùi" so với hiện tại (do điều chỉnh) → vẫn ghi nhận, ghi `order_status_history` với `source=channel`, và bật `has_issue` + cảnh báo nếu bước lùi bất thường (vd `completed` → `processing`).
2. **Người dùng (đơn manual hoặc thao tác thủ công)** chỉ được chuyển theo các cạnh hợp lệ; chuyển không hợp lệ → từ chối + thông báo. Đơn từ sàn: người dùng **không** tự ý đổi trạng thái "lõi" (đẩy theo sàn), chỉ đổi được cờ phụ (tag, note) — trừ một số bước được sàn cho phép (vd "xác nhận đơn", "tạo vận đơn") thì gọi API sàn rồi mới đổi.
3. **Idempotent:** set trạng thái bằng đúng trạng thái hiện tại → no-op (không tạo dòng history thừa).
4. **Mỗi lần đổi** ghi: `from_status`, `to_status`, `raw_status`, `source` (`channel`/`polling`/`webhook`/`user`/`system`/`carrier`), `changed_at`, `payload`.
5. **Tác động tồn kho** (xem `inventory-and-sku-mapping.md`): vào `pending`/`processing` → `reserved += qty` (nếu chưa reserve); vào `shipped` → `on_hand -= qty`, `reserved -= qty`; sang `cancelled`/`returned_refunded` khi **chưa** `shipped` → nhả `reserved`; sang `returned_refunded` khi **đã** `shipped` → tùy cấu hình: nếu hàng về kho thì `on_hand += qty`. Mọi tác động đi kèm dòng `inventory_movements`.
6. **`completed`** chỉ đặt khi sàn báo hoàn tất (hoặc qua mốc thời gian) — không tự nhảy từ `delivered` nếu chưa có tín hiệu.

## 4. Bảng mapping theo sàn (config, không hard-code rải rác)

Mỗi connector có `XStatusMap` ánh xạ `raw_status` (+ một số trường phụ của đơn nếu cần) → mã chuẩn. **Đây là nơi duy nhất chứa chuỗi trạng thái của sàn đó.** Sườn ban đầu (sẽ rà soát lại với tài liệu API thực tế):

### TikTok Shop
| raw_status | → chuẩn |
|---|---|
| `UNPAID` | `unpaid` |
| `ON_HOLD` / `PARTIALLY_SHIPPING` (tuỳ ngữ cảnh) | `processing` |
| `AWAITING_SHIPMENT` | `pending` → `processing` (tùy đã có gói chưa) |
| `AWAITING_COLLECTION` | `ready_to_ship` |
| `IN_TRANSIT` | `shipped` |
| `DELIVERED` | `delivered` |
| `COMPLETED` | `completed` |
| `CANCELLED` | `cancelled` |

### Shopee  *(điền khi có API)*
`UNPAID`→unpaid · `READY_TO_SHIP`/`PROCESSED`→processing/ready_to_ship · `SHIPPED`→shipped · `TO_CONFIRM_RECEIVE`→delivered · `COMPLETED`→completed · `CANCELLED`/`IN_CANCEL`→cancelled · `TO_RETURN`→returning · ...

### Lazada  *(điền khi có API)*
`pending`→pending · `packed`→processing · `ready_to_ship`→ready_to_ship · `shipped`→shipped · `delivered`→delivered · `failed`→delivery_failed · `canceled`→cancelled · `returned`→returned_refunded · ...

### Manual
Do người dùng đẩy theo state machine; mặc định tạo ở `pending` (hoặc `processing` nếu chọn).

## 5. Hiển thị
- UI hiển thị **tên chuẩn** + tooltip `raw_status` gốc. Bộ lọc đơn lọc theo mã chuẩn (và có thể lọc thêm theo `raw_status` cho người dùng nâng cao).
- Đơn `has_issue` hiển thị cờ cảnh báo + lý do (lùi trạng thái bất thường, thiếu mapping SKU, lỗi đẩy tồn, lỗi tạo vận đơn...).
