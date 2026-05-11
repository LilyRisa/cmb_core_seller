# Bảo mật & Dữ liệu cá nhân

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Xác thực & phiên
- Sanctum SPA (cookie, cùng domain) + CSRF cookie. Cookie `HttpOnly`, `Secure`, `SameSite=Lax`.
- Mật khẩu hash bằng bcrypt/argon2 (mặc định Laravel). Chính sách độ mạnh mật khẩu; chống brute-force (throttle login theo IP + theo tài khoản, khoá tạm sau N lần sai).
- Quên mật khẩu qua email token hết hạn ngắn. (Phase sau) 2FA bắt buộc cho `owner`/`admin`.
- Session hết hạn hợp lý; "đăng xuất mọi thiết bị".

## 2. Phân quyền & cách ly tenant (xem `01-architecture/multi-tenancy-and-rbac.md`)
- `tenant_id` mọi bảng nghiệp vụ + global scope + Policy kiểm `tenant_id` ở mọi action. Truy vấn bỏ scope phải tường minh & được review.
- File MinIO theo prefix `tenants/{tenant_id}/...`; kiểm quyền trước khi cấp signed URL.
- Permission string theo role; `owner`/`admin` quản lý thành viên; `staff_*` bị giới hạn (vd `staff_order` không xem lợi nhuận/billing).
- Mọi action nhạy cảm ghi `audit_logs` (ai, tenant, action, đối tượng, before/after, IP, time).

## 3. Bí mật & token tích hợp
- Token sàn (`access_token`, `refresh_token`), credential ĐVVC, cấu hình kênh thông báo ⇒ **mã hoá ở tầng ứng dụng** (Laravel `encrypted`/`encrypted:array` cast), `APP_KEY` quản lý qua secret manager. **Không bao giờ log token/secret.**
- `app_key/secret` của các sàn ở biến môi trường / secret manager, không trong DB, không trong repo.
- Xoay khoá (`APP_KEY`) có quy trình re-encrypt.

## 4. Webhook & API ngoài
- Verify chữ ký mọi webhook (per sàn/ĐVVC) trước khi xử lý; sai ⇒ `401`, không lưu, không xử lý.
- Chống replay: dedupe theo `(provider, external_id, event_type[, timestamp])`; bỏ event quá cũ.
- Gọi API ngoài: timeout hợp lý, retry có giới hạn, không follow redirect lạ, validate TLS.
- SSRF: mọi URL "động" (vd label_url, ảnh sản phẩm từ sàn) chỉ tải từ domain whitelist; không cho người dùng nhập URL tuỳ ý để server fetch.

## 5. Rate limiting & lạm dụng
- Throttle: login, API chung per user, endpoint nặng (export/bulk) thấp hơn, webhook per IP. Trả `429` + `Retry-After`.
- Giới hạn theo gói (`Subscription`/`UsageCounter`) cho số gian hàng, số đơn đồng bộ/tháng, số job in...
- Upload file (ảnh SP, import Excel): giới hạn kích thước/loại MIME; quét cơ bản; lưu ngoài webroot (MinIO).

## 6. Dữ liệu cá nhân của người mua (buyer PII) — TUÂN THỦ
> Các sàn (TikTok/Shopee/Lazada) yêu cầu app đối tác **không lưu trữ quá mức** và **xoá theo yêu cầu** thông tin cá nhân buyer. Vi phạm có thể bị thu hồi quyền API.

RULES:
1. **Chỉ lưu PII cần cho nghiệp vụ** (tên người nhận, SĐT, địa chỉ giao hàng). Không lưu thừa (vd thông tin thanh toán của buyer).
2. **Mask khi hiển thị** theo quyền: SĐT hiển thị dạng `09xx xxx 123` cho role thấp; full chỉ cho role được phép (vd để gọi xác nhận đơn). Log không chứa SĐT/địa chỉ đầy đủ.
3. **Xoá / ẩn danh hoá khi**:
   - Nhận webhook `data_deletion` từ sàn ⇒ enqueue job ẩn danh hoá thông tin buyer của (các) đơn liên quan (giữ lại dữ liệu thống kê không định danh: số tiền, SKU, thời gian).
   - Nhà bán **ngắt kết nối** gian hàng ⇒ theo chính sách: ẩn danh hoá PII buyer của shop đó sau khoảng thời gian quy định (giữ đơn ở dạng vô danh để báo cáo).
   - Hết thời hạn lưu trữ nội bộ (cấu hình; mặc định một khoảng đủ cho khiếu nại/đối soát) ⇒ job định kỳ ẩn danh hoá đơn cũ.
4. **Quyền truy cập PII** chỉ cấp cho role cần; mọi truy cập "xem đầy đủ SĐT" có thể bật ghi audit.
5. **Không chia sẻ PII** ra ngoài hệ thống (không gửi sang dịch vụ thứ ba không cần thiết). Khi tải label PDF từ ĐVVC (chứa PII) ⇒ lưu MinIO theo tenant, signed URL hết hạn ngắn.
6. **Mã hoá at-rest**: bật mã hoá đĩa cho DB & object storage ở prod; cột nhạy cảm (SĐT) cân nhắc mã hoá ứng dụng (đánh đổi: khó tìm kiếm — có thể lưu thêm cột hash để lookup).
7. **Tài liệu hoá** chính sách lưu trữ & xoá ở đây và công bố trong privacy policy của sản phẩm.

## 7. An toàn vận hành
- HTTPS bắt buộc; HSTS. Cập nhật bản vá hệ điều hành/dependency định kỳ; `composer audit` / `npm audit` trong CI.
- Phân tách quyền hạ tầng (least privilege) cho DB/MinIO/secret manager.
- Backup mã hoá (xem `07-infra/observability-and-backup.md`).
- Quy trình xử lý sự cố bảo mật: phát hiện → cô lập → đánh giá phạm vi → thông báo (nếu cần theo quy định) → khắc phục → post-mortem.

## 8. Việc Phase 0
- [ ] `encrypted` cast cho token/credential; `APP_KEY` qua secret.
- [ ] Throttle login + API; webhook verify middleware khung.
- [ ] Audit log khung + helper `audit()`.
- [ ] Masking helper cho SĐT; quy ước không log PII.
- [ ] Xử lý webhook `data_deletion`/`shop_deauthorized` (khung job ẩn danh hoá) — hoàn thiện ở Phase 1 khi có TikTok.
- [ ] Viết privacy policy nháp + chính sách lưu trữ vào file này.
