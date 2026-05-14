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
- **Payment gateway credentials** *(Phase 6.4 — SPEC-0018)*: `SEPAY_*` (account_no/bank_code/webhook_api_key), `VNPAY_TMN_CODE`/`VNPAY_HASH_SECRET`, `MOMO_*` chỉ trong `.env` — KHÔNG trong DB, KHÔNG trong repo. Webhook payload lưu vào `payments.raw_payload` đã **redact PII** (SePay bỏ `subAccount`/`accountNumber`; cổng nào không trả PAN/CVV thì không lưu thêm gì) — PCI scope minimization.
- Xoay khoá (`APP_KEY`) có quy trình re-encrypt.

## 4. Webhook & API ngoài
- Verify chữ ký mọi webhook (per sàn/ĐVVC/cổng thanh toán) trước khi xử lý; sai ⇒ `401`, không lưu, không xử lý.
  - **Sàn TMĐT** (TikTok/Shopee/Lazada): HMAC theo spec từng sàn, ở `Channels\WebhookController`.
  - **Cổng thanh toán** *(Phase 6.4 — SPEC-0018)*: SePay verify `Authorization: Apikey <key>` (constant-time `hash_equals`); VNPay verify HMAC-SHA512 `vnp_SecureHash` theo chuẩn 2.1.0 (`http_build_query` PHP_QUERY_RFC1738). MoMo skeleton trả false (chưa implement).
- Chống replay: dedupe theo `(provider, external_id, event_type[, timestamp])` cho webhook sàn; **`payments` unique `(gateway, external_ref)`** cho cổng thanh toán ⇒ webhook chạy 2 lần = 1 payment row (idempotent).
- Gọi API ngoài: timeout hợp lý, retry có giới hạn, không follow redirect lạ, validate TLS.
- SSRF: mọi URL "động" (vd label_url, ảnh sản phẩm từ sàn) chỉ tải từ domain whitelist; không cho người dùng nhập URL tuỳ ý để server fetch.

## 5. Rate limiting & lạm dụng
- Throttle: login, API chung per user, endpoint nặng (export/bulk) thấp hơn, webhook per IP. Trả `429` + `Retry-After`.
- Giới hạn theo gói *(Phase 6.4 — SPEC-0018, đã implement)*: middleware `plan.limit:channel_accounts` chặn vượt số gian hàng theo gói (`402 PLAN_LIMIT_REACHED`); `plan.feature:<feature>` chặn module nâng cao theo gói (`402 PLAN_FEATURE_LOCKED`). **KHÔNG** giới hạn số đơn (đảm bảo "không mất đơn nào của khách"). Hết hạn ⇒ grace 7 ngày → rớt về `trial` (không khoá data).
- Upload file (ảnh SP, import Excel): giới hạn kích thước/loại MIME; quét cơ bản; lưu ngoài webroot (MinIO).
- `/api/v1/billing/checkout`: throttle 10/phút/user (chống spam tạo invoice). `/webhook/payments/*`: không giới hạn ở app (gateway tự throttle); verify chữ ký trước khi ghi.

## 6. Dữ liệu cá nhân của người mua (buyer PII) — TUÂN THỦ
> Các sàn (TikTok/Shopee/Lazada) yêu cầu app đối tác **không lưu trữ quá mức** và **xoá theo yêu cầu** thông tin cá nhân buyer. Vi phạm có thể bị thu hồi quyền API.

RULES:
1. **Chỉ lưu PII cần cho nghiệp vụ** (tên người nhận, SĐT, địa chỉ giao hàng). Không lưu thừa (vd thông tin thanh toán của buyer). **Sổ khách hàng** (`customers`, Phase 2 — xem SPEC-0002) **không** tạo PII mới — chỉ centralize SĐT/tên/địa chỉ đã có ở `orders` thành bảng tra cứu được; cùng các ràng buộc lưu/xoá. SĐT đầy đủ ở đơn TikTok mới (`AWAITING_SHIPMENT`, COD) là **chính đáng** — TikTok cung cấp để seller xác nhận đơn — và là input duy nhất cho cross-order matching.
2. **Mask khi hiển thị** theo quyền: SĐT hiển thị dạng `09xx xxx 123` cho role thấp; full chỉ cho role được phép (vd để gọi xác nhận đơn). Log không chứa SĐT/địa chỉ đầy đủ. Permission `customers.view_phone` (mặc định owner/admin/staff_order) gate việc trả `phone` đầy đủ ở response `CustomerResource`/`OrderResource.customer`.
3. **Cross-order matching theo SĐT (Customers module):** khoá khớp là `phone_hash = sha256(normalize(phone))`, **không** plaintext. Hash deterministic ⇒ index được; không reverse được ⇒ DB dump leak vẫn cần brute-force 10 chữ số (10^10). Unique `(tenant_id, phone_hash)` ⇒ **tuyệt đối không cross-tenant match** (mỗi tenant nhìn lịch sử của riêng mình; không có "blacklist toàn cầu"). Search box "tìm khách theo SĐT": SPA gửi raw → backend normalize + hash → query — log filter phải redact `q=` nếu match regex SĐT (xem `config/logging.php` Phase 2).
4. **Xoá / ẩn danh hoá khi**:
   - Nhận webhook `data_deletion` từ sàn ⇒ enqueue job ẩn danh hoá thông tin buyer của (các) đơn liên quan (giữ lại dữ liệu thống kê không định danh: số tiền, SKU, thời gian) **và purge ngay các phiếu in PDF của đơn đó** (xem điểm về phiếu in bên dưới). **Bổ sung (Phase 2):** với `customers` record liên quan — nếu khách chỉ có đơn ở shop bị `data_deletion` ⇒ clear `phone`/`name`/`email`/`addresses_meta`/`manual_note`, set `pii_anonymized_at`; giữ `phone_hash`/`lifetime_stats` (số tổng không định danh). Nếu khách còn đơn ở shop khác trong tenant ⇒ giữ hồ sơ, chỉ xoá địa chỉ chỉ thuộc shop kia. Chi tiết: `03-domain/customers-and-buyer-reputation.md` §7.
   - Nhà bán **ngắt kết nối** gian hàng ⇒ theo chính sách: ẩn danh hoá PII buyer của shop đó **sau 90 ngày** (cấu hình `customers.anonymize_after_days`, buffer cho khiếu nại/đối soát) — áp dụng cho cả `orders` và `customers` cùng cơ chế; purge phiếu in liên quan.
   - Hết thời hạn lưu trữ nội bộ (cấu hình; mặc định một khoảng đủ cho khiếu nại/đối soát) ⇒ job định kỳ ẩn danh hoá đơn cũ.
5. **Quyền truy cập PII** chỉ cấp cho role cần; mọi truy cập "xem đầy đủ SĐT" có thể bật ghi audit. `customer_notes` mà NV gõ vào có thể vô tình chứa SĐT/email — khi anonymize, regex redact các pattern PII trong `note` text.
6. **Không chia sẻ PII** ra ngoài hệ thống (không gửi sang dịch vụ thứ ba không cần thiết). Khi tải label PDF từ ĐVVC/sàn (chứa PII) ⇒ lưu MinIO theo tenant, chỉ tải qua signed URL hết hạn ngắn. **Cấm** export `customers` CSV với `phone` đầy đủ trừ khi role có `customers.view_phone` + xác nhận lần 2 (audit logged).
7. **Phiếu in của đơn (vận đơn / packing list / picking list PDF) — giữ tối đa 90 ngày** rồi job `PrunePrintDocuments` xoá file, chỉ giữ metadata không định danh (loại phiếu, thời điểm in, ai in, số đơn). "In lại" trong 90 ngày = trả lại đúng file đã sinh qua signed URL. Đây vừa là tiện ích vận hành vừa là biện pháp tối thiểu hoá PII. Chi tiết logic: `docs/03-domain/fulfillment-and-printing.md` §8.
8. **Mã hoá at-rest**: bật mã hoá đĩa cho DB & object storage ở prod; cột nhạy cảm (SĐT) cân nhắc mã hoá ứng dụng (đánh đổi: khó tìm kiếm — có thể lưu thêm cột hash để lookup).
9. **Tài liệu hoá** chính sách lưu trữ & xoá ở đây và công bố trong privacy policy của sản phẩm.

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
