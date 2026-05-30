# Troubleshooting — Sự cố & Cách xử lý

> Mỗi mục: triệu chứng → nguyên nhân → cách xử lý. Mã lỗi tham chiếu envelope `{ "error": { "code", "message", "trace_id" } }`.

---

## A. Đăng nhập / Tài khoản

### Không đăng nhập được
- **Nguyên nhân**: sai email/mật khẩu (`INVALID_CREDENTIALS`); hoặc tài khoản/tenant bị tạm ngưng (`USER_SUSPENDED`/`TENANT_SUSPENDED`).
- **Xử lý**: kiểm tra lại thông tin; dùng "Quên mật khẩu" để đặt lại; nếu bị tạm ngưng, liên hệ super-admin/CSKH.

### Đăng nhập được nhưng mọi thứ bị chặn
- **Nguyên nhân**: email chưa xác thực (`EMAIL_NOT_VERIFIED`).
- **Xử lý**: mở email bấm link xác thực; không thấy thì kiểm tra Spam/Quảng cáo và bấm "Gửi lại email" (chờ 60s). Link hết hạn sau 60 phút → gửi lại link mới.

### Đổi email thất bại
- **Nguyên nhân**: thiếu mật khẩu hiện tại (`INVALID_PASSWORD`).
- **Xử lý**: nhập đúng mật khẩu hiện tại ở `/settings/profile`.

### Đặt lại mật khẩu báo token sai/hết hạn
- **Nguyên nhân**: `INVALID_RESET_TOKEN` (link cũ/đã dùng).
- **Xử lý**: yêu cầu link mới ở `/forgot-password`.

---

## B. Phân quyền / Truy cập

### Thiếu nút thao tác (Tạo đơn, In, Đối soát…)
- **Nguyên nhân**: vai trò của bạn không có quyền đó.
- **Xử lý**: nhờ Owner/Admin cấp quyền/đổi vai trò ở `/settings/members`.

### Lỗi `TENANT_REQUIRED` / `TENANT_FORBIDDEN`
- **Nguyên nhân**: chưa chọn workspace, hoặc bạn không thuộc workspace đó.
- **Xử lý**: chọn đúng workspace ở bộ chọn header; nếu vừa được mời, đăng nhập lại.

### Lỗi 402 `PLAN_FEATURE_LOCKED` / `PLAN_LIMIT_REACHED`
- **Nguyên nhân**: tính năng/hạn mức nằm ở gói cao hơn (vd kế toán, đối soát, hoặc đã đủ số gian hàng).
- **Xử lý**: nâng gói ở `/settings/plan`, hoặc gỡ bớt gian hàng nếu vượt hạn.

### Lỗi `PLAN_QUOTA_EXCEEDED` (chặn mọi thao tác ghi)
- **Nguyên nhân**: over-quota lock — vượt hạn gian hàng quá 2 ngày sau khi hạ gói.
- **Xử lý**: nâng gói hoặc xoá bớt gian hàng (route xoá gian hàng + billing + auth vẫn mở để bạn thoát).

---

## C. Kết nối gian hàng (Channels)

### Kết nối TikTok thất bại
- **Nguyên nhân**: thiếu scope/quyền khi cấp phép; hoặc shop chưa đủ điều kiện API.
- **Xử lý**: làm lại OAuth, đảm bảo cấp đủ quyền; đọc hướng dẫn lỗi hiển thị ngay trên màn hình kết nối.

### Kết nối/đồng bộ Lazada lỗi IP
- **Nguyên nhân**: Lazada yêu cầu whitelist IP máy chủ.
- **Xử lý**: mở modal "IP máy chủ" ở `/channels`, copy IP, thêm vào whitelist trong cổng nhà bán Lazada; cũng cần "subscribe" các message/event tương ứng.

### Đơn không về sau khi kết nối
- **Nguyên nhân**: webhook chưa kích hoạt / chữ ký sai (bị bỏ, trả 401) / đơn cũ ngoài 90 ngày backfill.
- **Xử lý**: bấm "Đồng bộ lại" / "kéo đơn chưa xử lý" trong gian hàng; kiểm tra `/sync-logs` (tab Webhook), redrive webhook lỗi.

### `UNKNOWN_PROVIDER` khi kết nối/ webhook
- **Nguyên nhân**: provider chưa bật trong cấu hình môi trường (vd Lazada/Shopee chưa được bật `INTEGRATIONS_CHANNELS`).
- **Xử lý**: liên hệ vận hành để bật provider; Shopee đang chờ duyệt API.

---

## D. Đơn hàng & Tồn

### "Chuẩn bị hàng" bị chặn
- **Nguyên nhân**: có SKU âm kho (`∑on_hand − ∑reserved < 0`).
- **Xử lý**: nhập thêm tồn hoặc điều chỉnh; kiểm tra ghép SKU đúng chưa.

### Đơn báo "có vấn đề" (has_issue)
- **Nguyên nhân**: SKU chưa ghép, âm kho, hoặc sàn báo lùi trạng thái bất thường.
- **Xử lý**: vào tab "Có vấn đề"; ghép SKU ở tab Liên kết SKU; kiểm tra tồn; với lùi trạng thái bất thường, đối chiếu với sàn.

### Tồn trên sàn không khớp
- **Nguyên nhân**: listing chưa ghép (không đẩy tồn); listing bị "ghim"; hoặc đang oversold (đẩy 0).
- **Xử lý**: ghép SKU; bỏ ghim nếu cần; nhập thêm tồn để hết âm. Reverse-sync định kỳ sẽ cảnh báo và đẩy lại (không ghi đè SKU gốc).

### Không xoá được SKU
- **Nguyên nhân**: SKU còn tồn (`409`).
- **Xử lý**: đưa tồn về 0 (xuất/điều chỉnh) rồi xoá.

### `SKU_CODE_TAKEN` / `DUPLICATE_SKU`
- **Nguyên nhân**: trùng `sku_code` trong tenant.
- **Xử lý**: dùng mã khác hoặc sửa SKU hiện có.

---

## E. In ấn & Giao hàng

### Tem không tải / không in được
- **Nguyên nhân**: chưa "Chuẩn bị hàng" (chưa có tem từ sàn/ĐVVC); hoặc lấy tem thất bại.
- **Xử lý**: "Chuẩn bị hàng" trước; nếu lỗi lấy tem, dùng "Nhận phiếu giao hàng lại" (refetch slip).

### Tem in lại bị popup xác nhận
- **Nguyên nhân**: in từ lần thứ 2 trở đi (bảo vệ chống in trùng).
- **Xử lý**: xác nhận nếu thực sự cần in lại; hệ thống trả về cùng file gốc.

### Quét đóng gói báo "không tìm thấy" / "đã quét"
- **Nguyên nhân**: mã thuộc tenant khác; hoặc đã quét rồi (quét trùng = no-op).
- **Xử lý**: đảm bảo đúng workspace; mã đã quét thì bỏ qua.

### Thêm ĐVVC (GHN) thất bại
- **Nguyên nhân**: token API sai; chưa chọn shop GHN / địa chỉ gửi.
- **Xử lý**: nhập đúng token, chọn shop + địa chỉ tầng tỉnh/huyện/xã, bấm "Xác minh".

---

## F. Tin nhắn (Messaging)

### `OUTBOUND_WINDOW_CLOSED` khi gửi Facebook
- **Nguyên nhân**: quá 24h kể từ tin khách cuối; cần message tag.
- **Xử lý**: chọn loại message tag phù hợp (vd HUMAN_AGENT) trước khi gửi; lý tưởng là chờ khách nhắn lại.

### Nhắn riêng bình luận báo `(#10900) Activity already replied to`
- **Nguyên nhân**: Facebook chỉ cho nhắn riêng 1 lần/comment (đã nhắn rồi).
- **Xử lý**: hệ thống đã xử lý idempotent (không lỗi đỏ). Để nhắn tiếp, chờ khách trả lời trong Messenger rồi nhắn qua hội thoại tin nhắn.

### Modal nhắn riêng báo "đã gửi X/Y phần"
- **Nguyên nhân**: Facebook chỉ chắc chắn nhận phần đầu; phần sau (đính kèm) gửi qua PSID + message tag, có thể bị từ chối nếu khách chưa mở hội thoại.
- **Xử lý**: gửi nốt phần còn lại khi khách trả lời; hoặc gộp nội dung ngắn gọn.

### Nút Thích bình luận lỗi quyền (`ENGAGEMENT_PERMISSION`)
- **Nguyên nhân**: Page thiếu `pages_manage_engagement`.
- **Xử lý**: kết nối lại Page và cấp đủ quyền.

### `ATTACHMENT_INVALID` khi gửi media
- **Nguyên nhân**: vượt dung lượng (ảnh 25MB / video 100MB / file 25MB) hoặc sai MIME.
- **Xử lý**: nén/đổi định dạng cho hợp lệ.

### AI gợi ý không phản hồi / `AI_PROVIDER_NOT_AVAILABLE`
- **Nguyên nhân**: chưa chọn provider (hoặc provider lỗi/circuit-breaker mở); hoặc gói chưa có `messaging_ai`.
- **Xử lý**: chọn provider ở `/settings/messaging`; chờ và thử lại (rate-limit 20/phút); nâng gói nếu thiếu tính năng.

---

## G. Hoàn/Hủy, Mua hàng, Đối soát, Kế toán

### Không thấy/không kéo được đối soát (`FINANCE_NOT_ENABLED` / 422)
- **Nguyên nhân**: gói chưa có `finance_settlements`, hoặc feature-flag đối soát của provider đang tắt.
- **Xử lý**: nâng gói; liên hệ vận hành để bật tính năng đối soát cho sàn.

### Tạo bút toán tay báo lỗi
- **Nguyên nhân**: `ACCOUNTING_UNBALANCED` (Nợ ≠ Có), tài khoản không hạch toán được, hoặc `PERIOD_CLOSED`.
- **Xử lý**: cân Nợ = Có (≥2 dòng), chọn TK hạch toán được, chọn kỳ đang mở.

### Không đóng được kỳ / không mở lại kỳ
- **Nguyên nhân**: còn bút toán `pending` kỳ trước; hoặc kỳ kế đã đóng (không cho mở lại cascade); kỳ `locked` không mở.
- **Xử lý**: post hết bút toán treo; chỉ mở lại khi kỳ kế còn mở.

### PO nhận hàng nhưng tồn không tăng
- **Nguyên nhân**: phiếu nhận hàng chưa "xác nhận".
- **Xử lý**: xác nhận goods receipt để ghi vào sổ (tồn += và tạo lớp giá vốn).

---

## H. Thanh toán & Gói

### Đã chuyển khoản SePay nhưng gói chưa kích hoạt
- **Nguyên nhân**: sai nội dung chuyển khoản (memo) ⇒ không khớp hoá đơn; hoặc chuyển thiếu tiền (payment thành công nhưng hoá đơn vẫn `pending`).
- **Xử lý**: chuyển đúng nội dung; bù phần thiếu; theo dõi `Trạng thái thanh toán` (poll 5s). Chuyển dư được ghi nhận, không tự hoàn.

### `DOWNGRADE_NOT_ALLOWED` / `ALREADY_ON_PLAN` khi nâng gói
- **Nguyên nhân**: đang cố hạ xuống gói thấp hơn; hoặc đã ở gói đó cùng chu kỳ.
- **Xử lý**: chỉ nâng lên gói cao hơn; muốn hạ thì huỷ để chạy hết kỳ rồi về trial.

### `CANNOT_CANCEL_TRIAL` / `NO_ACTIVE_SUBSCRIPTION`
- **Nguyên nhân**: đang ở trial (không cần huỷ) hoặc không có gói trả phí đang chạy.
- **Xử lý**: bỏ qua — trial tự hết hạn; nâng gói nếu cần tính năng.

---

## I. Đồng bộ & Hệ thống

### Webhook/sync lỗi trong `/sync-logs`
- **Nguyên nhân**: lỗi tạm thời từ sàn / token hết hạn / rate-limit.
- **Xử lý**: bấm "redrive"/"retry"; nếu token hết hạn, kết nối lại gian hàng.

### Trang kế toán/đối soát trống hoặc ẩn
- **Nguyên nhân**: gói chưa bật tính năng (suy biến nhẹ nhàng), hoặc kế toán chưa khởi tạo.
- **Xử lý**: nâng gói; bấm "Khởi tạo" ở banner kế toán.

### Lỗi 5xx kèm `trace_id`
- **Nguyên nhân**: lỗi máy chủ.
- **Xử lý**: gửi `trace_id` cho support để tra cứu nhanh (đối chiếu Sentry). Không có stacktrace ở môi trường production.

> Mã lỗi đầy đủ: xem [api-reference.md](api-reference.md). Quy tắc nghiệp vụ: [business-rules.md](business-rules.md).
