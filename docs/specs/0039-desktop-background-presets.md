# SPEC 0039: Hình nền Desktop v2 — preset do admin quản lý + người dùng chọn

- **Trạng thái:** Draft (2026-06-24)
- **Phase:** Mở rộng SPEC-0037 (Web Desktop)
- **Module backend liên quan:** Admin (preset CRUD + storage), Tenancy (preference người dùng)
- **Tác giả / Ngày:** Team · 2026-06-24
- **Liên quan:** SPEC-0037 (Web Desktop shell), SPEC-0037-admin-announcement-popups (mẫu admin CRUD + `MediaUploader::storePublic`), ADR-0027.

## 1. Vấn đề & mục tiêu

Màn Desktop v2 hiện dùng một gradient cứng. Cần cho phép **người dùng chọn hình nền** từ một **thư viện preset do admin quản lý**, và **căn giữa lưới icon** trên màn nền.

Quyết định phạm vi: người dùng **chỉ chọn preset** (không upload ảnh riêng — tránh chi phí lưu trữ/kiểm duyệt). Admin thêm/sửa/xoá preset (có upload ảnh). Lựa chọn nền lưu **theo người dùng** (mở rộng `user_preferences`, không theo tenant).

## 2. Trong / ngoài phạm vi

**Trong:**

- **BE — bảng preset (admin global, KHÔNG `tenant_id`):** `desktop_backgrounds` (`id`, `name`, `image_url`, `image_path`, `is_active` bool, `position` int, `created_by_user_id`, timestamps). Ảnh lưu qua `MediaUploader::storePublic($file, 'desktop-backgrounds')` (R2 prod / public dev) — đúng pattern `AdminAnnouncementController::media`. Model `DesktopBackground` (module Admin).
- **BE — admin CRUD** (guard `admin_web`, prefix `/api/v1/admin`): `GET desktop-backgrounds` (list tất cả), `POST desktop-backgrounds` (`{name, image_url, image_path, is_active, position}`), `PATCH desktop-backgrounds/{id}`, `DELETE desktop-backgrounds/{id}`, `POST desktop-backgrounds/media` (upload ⇒ `{url, path}`). Controller `AdminDesktopBackgroundController` (module Admin).
- **BE — đọc cho user** (sanctum): `GET /api/v1/desktop-backgrounds` ⇒ `{ data: [{id, name, image_url}] }` chỉ preset `is_active`, sắp theo `position`. Cùng controller (method `options`), route trong `app/routes/api.php` nhóm `auth:sanctum`.
- **BE — preference key mới `ui_desktop_bg`:** lưu **URL ảnh đã chọn** (string) hoặc `null` (= gradient mặc định). Thêm rule vào `UpdatePreferencesRequest` (`nullable string max:2048`) và vào `UserPreferenceService::shape()` (default `null`). Trả kèm trong `/me/preferences` và `/auth/me`.
- **FE user:** `lib/desktopBackgrounds.ts` (`useDesktopBackgrounds()` GET active). `SettingsAppearancePage` thêm mục **"Hình nền Desktop"**: thẻ "Mặc định (gradient)" + thư viện thumbnail preset; bấm chọn ⇒ `useUpdatePreferences().mutate({ ui_desktop_bg })`, đánh dấu cái đang chọn. `DesktopHome`: nếu `ui_desktop_bg` có URL ⇒ `background-image: url(...)` (cover/center), không thì gradient; **lưới icon căn giữa cả ngang & dọc** (Launchpad), tràn thì cuộn.
- **FE admin:** `admin/pages/AdminDesktopBackgroundsPage.tsx` (bảng + form thêm/sửa: tên, upload ảnh preview, `is_active`, `position`; xoá), `admin/lib/desktopBackgrounds.tsx` (hooks CRUD + `uploadDesktopBackgroundMedia`). Thêm route trong `AdminApp.tsx` + menu item trong `AdminLayout.tsx`.

**Ngoài:** user upload ảnh nền riêng; gán nền theo tenant; crop/resize trong admin; nhiều nền xoay vòng/slideshow; nền động/video.

## 3. Hành vi & quy tắc

- **Chọn nền:** user bấm 1 preset ⇒ lưu `ui_desktop_bg = image_url` ngay (optimistic qua cache `me`); bấm "Mặc định" ⇒ `null`. `DesktopHome` phản ứng tức thì (đọc từ `useUserPreferences`).
- **Preset bị xoá/tắt:** URL đã lưu của user có thể "chết" ⇒ ảnh nền không tải; chấp nhận (hiếm), nền chỉ là trang trí. Không cần dọn preference.
- **Quyền:** danh sách preset cho user chỉ gồm `is_active`. Admin CRUD chỉ admin (`admin_web`).
- **Căn giữa icon:** `.desk-home` flex center cả 2 trục; nếu số app vượt chiều cao ⇒ cuộn dọc (container `overflow:auto`).
- **Ảnh nền dễ đọc icon:** thêm lớp phủ tối nhẹ (overlay gradient mờ) trên ảnh để nhãn icon trắng vẫn rõ.

## 4. API & UI

**Endpoint** (cập nhật `docs/05-api/endpoints.md`):
- Admin: `GET/POST /api/v1/admin/desktop-backgrounds`, `PATCH/DELETE /api/v1/admin/desktop-backgrounds/{id}`, `POST /api/v1/admin/desktop-backgrounds/media`.
- User: `GET /api/v1/desktop-backgrounds` (sanctum) ⇒ preset active.
- `PUT /api/v1/me/preferences` nhận thêm `ui_desktop_bg` (string|null).

**BE files:** `app/Modules/Admin/Database/Migrations/xxxx_create_desktop_backgrounds_table.php`, `Models/DesktopBackground.php`, `Http/Controllers/AdminDesktopBackgroundController.php`, route Admin + api.php; sửa `UpdatePreferencesRequest.php`, `UserPreferenceService::shape()`.

**FE files:** user `lib/desktopBackgrounds.ts`, `pages/SettingsAppearancePage.tsx`, `components/desktop/DesktopHome.tsx`, css `.desk-home`; admin `pages/AdminDesktopBackgroundsPage.tsx`, `lib/desktopBackgrounds.tsx`, `AdminApp.tsx`, `AdminLayout.tsx`.

## 5. Cách kiểm thử

- **BE (PHPUnit):** admin tạo/sửa/xoá preset (admin guard; user thường ⇒ 401/403); `GET /desktop-backgrounds` chỉ trả active, đúng thứ tự `position`; `PUT /me/preferences {ui_desktop_bg}` lưu & đọc lại; `ui_desktop_bg` quá dài ⇒ 422; `me` trả `ui_desktop_bg`.
- **FE (kiểm tay):** admin thêm preset (upload ảnh hiện thumbnail) → bật active. User vào Cài đặt → Giao diện thấy preset → chọn → Desktop đổi nền ngay → reload vẫn giữ. Chọn "Mặc định" → về gradient. Icon căn giữa màn hình. Tắt preset ở admin → user không còn thấy trong thư viện.
- **Gate:** `pint --test`, `phpstan` (0 lỗi mới), feature tests xanh, `npm run typecheck/lint/build` sạch.
