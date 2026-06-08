# SPEC 0037: Popup thông báo do admin tạo (Announcement popups)

- **Trạng thái:** Implemented
- **Phase:** Admin SaaS (mở rộng SPEC 0023)
- **Module backend liên quan:** Admin (chủ), dùng `Support/MediaUploader` (R2). KHÔNG tenant-scoped.
- **Tác giả / Ngày:** lilyrisa · 2026-06-09
- **Liên quan:** SPEC 0023 (Admin broadcast), SPEC 0036 (notifications), `07-infra/cloudflare-r2-uploads.md`

## 1. Vấn đề & mục tiêu
Super-admin cần thông báo toàn hệ thống dạng **popup giữa màn hình** (fix bug, tạm dừng dịch vụ, bảo trì…) cho mọi user khi họ vào app. Cần trình soạn thảo nâng cao (TipTap) chèn **ảnh/video upload lên R2**. Popup hiển thị **1 lần / 1 tab trình duyệt**: tab cũ chỉ hiện 1 lần; mở tab mới mới hiện lại.

## 2. Trong / ngoài phạm vi
- **Trong:** bảng `announcements` (admin global), CRUD admin + bật/tắt + lịch chiếu tuỳ chọn, editor TipTap (ảnh/video → R2), sanitize HTML server-side, endpoint user đọc popup đang active, popup giữa màn hình (z-index cao nhất, xác nhận để bỏ qua), nhớ-đã-xem theo **tab** (sessionStorage).
- **Ngoài:** nhắm đối tượng theo plan/tenant (v1: toàn hệ thống mọi user); A/B; analytics lượt xem; lên lịch định kỳ.

## 3. Luồng chính
1. Super-admin vào `/admin/announcements` → tạo popup: tiêu đề + nội dung (TipTap, chèn ảnh/video upload R2) + bật `is_active` + (tuỳ chọn) `starts_at`/`ends_at` + nhãn nút (mặc định "Đã hiểu").
2. User đăng nhập vào app → `AnnouncementPopup` (mount ở `AppLayout`) gọi `GET /api/v1/announcements/active`.
3. Lọc bỏ popup có id đã nằm trong `sessionStorage` (đã xem trong tab này) → hiện popup CÒN LẠI ở giữa màn hình (Modal z-index cao nhất, không đóng bằng mask/ESC, chỉ nút xác nhận).
4. User ấn xác nhận → thêm id vào `sessionStorage` seen-set → hiện popup kế (nếu nhiều) hoặc đóng.
5. Trong cùng tab: không hiện lại (seen-set còn trong sessionStorage). Mở tab mới: sessionStorage rỗng → hiện lại các popup active.

## 4. Hành vi & quy tắc
- **Active** = `is_active = true` AND (`starts_at` null hoặc ≤ now) AND (`ends_at` null hoặc ≥ now).
- **Phạm vi:** toàn hệ thống — mọi user đã đăng nhập + verified đều thấy (không tenant-scoped).
- **Nhiều popup active:** hiện tuần tự, mỗi popup nhớ-đã-xem riêng theo id.
- **Nhớ-đã-xem theo TAB:** `sessionStorage['cmb:announce:seen']` = mảng id. sessionStorage sống theo tab (đóng tab → mất) ⇒ đúng yêu cầu "1 tab 1 lần; tab mới hiện lại". KHÔNG dùng localStorage (sẽ nhớ vĩnh viễn).
- **Sanitize:** body lưu là HTML đã sanitize server-side (allowlist thẻ/thuộc tính; chặn `script`/`iframe`/`on*`/`javascript:`). Render user qua `dangerouslySetInnerHTML` (đã sạch). Phòng thủ nhiều lớp dù tác giả là super-admin tin cậy.
- **Phân quyền:** tạo/sửa/xoá = guard `admin_web` (super-admin). Đọc active = `auth:sanctum` + `verified`.

## 5. Dữ liệu
Bảng `announcements` (Admin, KHÔNG tenant-scoped):

| cột | kiểu | ghi chú |
|---|---|---|
| id | bigint | |
| title | string(255) | |
| body_html | longtext | HTML đã sanitize (chứa `<img>`/`<video>` trỏ R2) |
| is_active | bool | index |
| starts_at | timestamp null | mở cửa sổ chiếu |
| ends_at | timestamp null | đóng cửa sổ chiếu |
| dismiss_label | string(40) | mặc định "Đã hiểu" |
| created_by_user_id | fk (admin) | |
| meta | json null | |
| timestamps | | |

Ảnh/video lưu R2 (disk `media`) tại `announcements/{ulid}.{ext}` (KHÔNG tenant prefix — nội dung hệ thống). Migration reversible.

## 6. API & UI
Admin (`/api/v1/admin`, guard `admin_web`):
- `GET    /announcements` — danh sách (mới nhất trước, phân trang).
- `POST   /announcements` — tạo (`title`, `body_html`, `is_active`, `starts_at?`, `ends_at?`, `dismiss_label?`). Sanitize body.
- `PATCH  /announcements/{id}` — sửa (kể cả bật/tắt).
- `DELETE /announcements/{id}` — xoá.
- `POST   /announcements/media` — multipart `file` (ảnh hoặc video) → upload R2 → `{ data:{ url } }`. Ảnh ≤ `media.images.max_kb`, video ≤ `media.video.max_kb` (mp4/webm).

User (`/api/v1`, `auth:sanctum` + `verified`):
- `GET /announcements/active` — `{ data:[{ id, title, body_html, dismiss_label }] }` (chỉ popup active).

Cập nhật `05-api/endpoints.md`.

UI admin (bundle `admin.tsx`): trang `/admin/announcements` — bảng + form TipTap (StarterKit + Underline + Link + Image + node Video tự định nghĩa) với nút chèn Ảnh/Video (upload R2), Switch bật/tắt, lịch chiếu tuỳ chọn. Thêm mục menu. Lib `admin/lib/announcements.tsx`.
UI user (bundle `app.tsx`): `AnnouncementPopup` mount ở `AppLayout` — Modal giữa màn hình, `zIndex` cao nhất, `maskClosable=false` + `closable=false`, footer 1 nút xác nhận. Render `body_html`. Nhớ-đã-xem qua sessionStorage.

TipTap chỉ thêm vào **bundle admin** (user popup chỉ render HTML — không cần TipTap).

## 7. Edge case & lỗi
- Không popup active → endpoint trả `[]`, FE không hiện gì.
- Upload sai MIME/size → 422.
- HTML có thẻ lạ/script → bị sanitize loại bỏ; body rỗng sau sanitize → vẫn lưu (popup chỉ có tiêu đề).
- sessionStorage không khả dụng (private mode) → bọc try/catch; tệ nhất popup hiện mỗi lần điều hướng cùng tab (chấp nhận; hiếm).
- Nhiều popup → hiện tuần tự; xác nhận từng cái.
- R2 chưa cấu hình (dev) → disk `public` (config/media.php) vẫn chạy.

## 8. Bảo mật & dữ liệu cá nhân
- Không PII. Body HTML sanitize allowlist (chặn XSS) — phòng thủ dù super-admin tin cậy.
- Media R2 public-read (như ảnh sản phẩm). Đường dẫn `announcements/*` tách khỏi `tenants/*`.

## 9. Kiểm thử
- Unit: `HtmlSanitizer` (giữ thẻ hợp lệ + `<img>/<video>`; loại `script`/`iframe`/`on*`/`javascript:`; unwrap thẻ lạ giữ nội dung).
- Feature: admin CRUD (tạo sanitize body, bật/tắt, xoá); upload media trả url; `GET /announcements/active` chỉ trả active + đúng cửa sổ thời gian; guard (user thường không vào được admin route).
- FE: (smoke) popup hiện khi có active & chưa seen; ẩn sau xác nhận.

## 10. Tiêu chí hoàn thành
- [ ] Migration + model `Announcement` + `HtmlSanitizer`.
- [ ] Admin CRUD + media upload (R2) + `MediaUploader::storePublic`.
- [ ] Endpoint user `announcements/active`.
- [ ] FE admin: trang + editor TipTap (ảnh/video) + menu + lib.
- [ ] FE user: `AnnouncementPopup` (sessionStorage per-tab) ở `AppLayout`.
- [ ] Tests BE xanh; pint/phpstan/eslint/tsc/build xanh.
- [ ] Cập nhật `05-api/endpoints.md`.

## 11. Câu hỏi mở
- Có cần nhắm theo plan/tenant về sau? (v1: toàn hệ thống).
- Có cần thống kê lượt xem/đóng popup? (v1: không).
