# TẠM THỜI — gỡ sau khi TikTok duyệt app

> Các thay đổi dưới đây **chỉ phục vụ quá trình TikTok app review** và phải được gỡ/khôi phục
> sau khi app được duyệt. Mỗi chỗ trong code đều có marker **`TIKTOK-REVIEW-TEMP`** —
> tìm nhanh tất cả bằng:
>
> ```bash
> # từ thư mục repo
> grep -rn "TIKTOK-REVIEW-TEMP" app docs
> ```

Cập nhật: 2026-06-03.

---

## 1. Cổng đăng nhập demo (backdoor literal "password")

**Mục đích:** cho reviewer đăng nhập `owner@demo.local` bằng mật khẩu thật **hoặc** literal
`password` (credential đã gửi trong bản phê duyệt, không sửa được). Luôn bật, chặn hẹp chỉ
đúng email demo + đúng literal.

**Xoá hẳn (3 chỗ):**

| File | Việc cần làm |
|---|---|
| `app/app/Modules/Tenancy/Http/Controllers/AuthController.php` | Xoá method `demoReviewBypass()`; trong `login()` đưa về `if (! Auth::guard('web')->validate([...]))` (bỏ nhánh `\|\| $this->demoReviewBypass(...)`). |
| `app/config/auth.php` | Xoá khối `'demo_review' => [...]`. |
| `app/tests/Feature/Tenancy/DemoReviewLoginTest.php` | Xoá cả file. |
| `app/.env` | Xoá đoạn chú thích `DEMO_REVIEW_*` (không còn biến nào để xoá; chỉ là comment). |

**Tắt tạm không cần sửa code:** đặt `DEMO_REVIEW_EMAIL=` (rỗng) ở env prod (Portainer) — bypass sẽ không chạy.

> Lưu ý prod: tính năng dùng default trong `config/auth.php` (`owner@demo.local` / `password`),
> KHÔNG cần thêm gì vào `docker-compose.prod.yml`. Nhưng user `owner@demo.local` phải tồn tại
> trong DB prod (seed).

---

## 2. Admin hiển thị secret KHÔNG che (mask)

**Mục đích:** để đối chiếu/sửa nhanh app_key/app_secret (Lazada/TikTok…) khi cấu hình review.
Hiện mọi secret hiển thị clear ở trang admin system-settings.

**Khôi phục mask (backend + frontend):**

| File | Việc cần làm |
|---|---|
| `app/app/Modules/Settings/Http/Controllers/AdminSystemSettingController.php` | Trong `index()`, khôi phục: secret (`$meta['is_secret']`) trả `'****'` thay vì `$this->svc->get($key)`. |
| `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` | Khôi phục câu mô tả `is_secret` ("mask khi GET"). |
| `app/tests/Feature/Settings/SystemSettingApiTest.php` | Đổi `test_secret_value_shown_unmasked_on_index` lại thành assert `'****'`. |
| `app/resources/js/admin/components/SecretInput.tsx` | Khôi phục mask `••••••••` + nút "Hiện" gọi `revealSetting` (xem git history file này). |
| `app/resources/js/admin/components/SettingRow.tsx` | `isPersisted` cho secret quay lại dựa `value === '****'`; bỏ dòng "Giá trị trong .env" nếu không muốn lộ. |

> Cách nhanh: `git log -p` các file trên để lấy lại bản trước, hoặc revert đúng commit nhóm thay đổi này.

---

## Kiểm tra sau khi gỡ

```bash
cd app
grep -rn "TIKTOK-REVIEW-TEMP" .. || echo "Sạch — không còn marker"
vendor/bin/pint --test && vendor/bin/phpstan analyse && php artisan test
npm run typecheck && npm run lint
```
