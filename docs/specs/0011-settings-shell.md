# SPEC 0011: Trung tâm Cài đặt — vỏ (shell) + Hồ sơ cá nhân + Thông tin gian hàng

- **Trạng thái:** Implemented (2026-05-19 — slice đầu của umbrella SPEC-0007; nhân viên username-only / vai trò chi tiết / cài đặt đơn hàng / mẫu in / gói = các spec con tiếp theo)
- **Phase:** 3+ *(nền cho mọi phần Cài đặt; làm cùng dịp Phase 5)*
- **Module backend liên quan:** Tenancy
- **Tác giả / Ngày:** Team · 2026-05-19
- **Liên quan:** SPEC-0007 (kế hoạch tổng Cài đặt — spec này hiện thực §2 IA + §3.1 + §3.2), `docs/01-architecture/multi-tenancy-and-rbac.md`. Đã có sẵn: trang Thành viên (`SettingsMembersPage`) và ĐVVC (`CarrierAccountsPage`) — spec này gom chúng vào vỏ Cài đặt.

## 1. Vấn đề & mục tiêu
Khu "Cài đặt" trước đây chỉ là vài trang rời + mục sidebar trỏ thẳng `/settings/members`. Cần một **vỏ Cài đặt** thống nhất: layout có **menu trái phân nhóm rõ ràng**, các trang con nằm dưới `/settings/*`, dễ thao tác, **hạn chế dùng `<Select>`** (ưu tiên `Radio.Group`/`Segmented`/menu/nút). Trong slice này làm: vỏ + **Hồ sơ cá nhân** + **Thông tin gian hàng**; gắn sẵn Thành viên & ĐVVC vào vỏ; các nhóm còn lại hiện placeholder "Sắp có".

## 2. Trong / ngoài phạm vi
**Trong:**
- FE: `SettingsLayout` (PageHeader "Cài đặt" + menu trái `Menu mode="inline"` phân nhóm: *Tài khoản* [Hồ sơ cá nhân / Thông tin gian hàng / Gói & nâng cấp], *Nhân sự & phân quyền* [Nhân viên & vai trò], *Kết nối* [ĐVVC / Gian hàng & module phụ trợ], *Vận hành* [Cài đặt đơn hàng / Mẫu in / Nhật ký thao tác] — nhóm chưa làm → `ComingSoon`). Routes lồng `/settings/profile|workspace|members|carriers`, `/settings` → redirect `profile`. Sidebar "Cài đặt" → `/settings`.
- FE pages: `SettingsProfilePage` (sửa tên/email + đổi mật khẩu — `Input`/`Input.Password`, không Select); `SettingsWorkspacePage` (sửa tên gian hàng + slug — owner/admin; vai trò khác = chỉ-xem `Descriptions`).
- BE: `PATCH /api/v1/auth/profile` (name/email/password — đổi email/password yêu cầu `current_password`); `PATCH /api/v1/tenant` (name/slug/settings — owner/admin; settings merge thay vì ghi đè; ghi `audit_logs`).
- FE lib: `useUpdateProfile()` (auth.tsx), `useUpdateTenant()` (tenant.tsx).
**Ngoài (các spec con tiếp theo của SPEC-0007):** ảnh đại diện / logo gian hàng (upload R2); múi giờ, địa chỉ kho mặc định; nhân viên username-only + login `username@<slug>`; vai trò tự tạo + catalog quyền; trung tâm kết nối hợp nhất (gian hàng + ĐVVC + module phụ trợ); cài đặt đơn hàng (order flags / pickup windows); mẫu in tuỳ biến (`print_templates`); gói/hạn mức/thanh toán (Billing — Phase 6); nhật ký thao tác đầy đủ.

## 3. Hành vi & quy tắc
- **Hồ sơ:** đổi `name` tự do; đổi `email` (unique toàn hệ thống) hoặc `password` (min 8, confirmed) ⇒ phải nhập `current_password` đúng (sai ⇒ `422 INVALID_PASSWORD`). FE chỉ gửi trường thực sự đổi; sau khi lưu, refresh `me`.
- **Gian hàng:** chỉ `tenant.settings` (owner/admin — qua `*`) mới sửa được. `slug` chỉ `[a-z0-9-]`, ≤60, unique; FE cảnh báo "slug là mã định danh khi nhân viên đăng nhập (`tên-đăng-nhập@<slug>`) — đổi slug ảnh hưởng chuỗi đăng nhập của nhân viên" (mở đường cho SPEC nhân viên username-only). `settings` patch là **merge** (không wipe key khác). Mọi lần sửa ghi `audit_logs` (`tenant.updated`).
- **Permission mới dùng tới:** `tenant.settings` (owner/admin) — chưa cần thêm vào `Role` enum vì owner/admin đã có `*`; khi làm vai trò custom (SPEC-0007 §5.2) thì đưa vào catalog quyền.
- **UI/UX:** menu trái rõ ràng, mỗi trang một Card có tiêu đề; form gọn (max-width ~460px); không dùng `<Select>` ở các trang này (chỉ `Input`/`Input.Password`/`Radio`/`Descriptions`).

## 4. API & UI
**Endpoint** (cập nhật `docs/05-api/endpoints.md`):
- `PATCH /api/v1/auth/profile` (sanctum) `{ name?, email?, current_password?, password?, password_confirmation? }` ⇒ `{ data: AuthUser }`. Email/password đổi mà `current_password` sai ⇒ `422 INVALID_PASSWORD`; email trùng / mật khẩu <8 ⇒ `422 VALIDATION_FAILED`.
- `PATCH /api/v1/tenant` (sanctum+tenant, `tenant.settings` ⇒ owner/admin) `{ name?, slug?, settings? }` ⇒ `{ data:{id,name,slug,status,settings,current_role} }`. Vai trò khác ⇒ `403`; slug sai định dạng / trùng ⇒ `422`. Ghi `audit_logs`.

**UI:** `resources/js/components/SettingsLayout.tsx` (vỏ + menu trái), `pages/SettingsProfilePage.tsx`, `pages/SettingsWorkspacePage.tsx`. Routes lồng trong `app.tsx` dưới `/settings`. Sidebar `AppLayout`: "Cài đặt" → `/settings`.

## 5. Cách kiểm thử
v1: kiểm tay (form lưu, sai mật khẩu hiện lỗi, vai trò viewer chỉ-xem gian hàng). Test feature cho `PATCH /auth/profile` & `PATCH /tenant` (đổi tên thành công, sai current_password ⇒ 422, viewer sửa tenant ⇒ 403, slug trùng ⇒ 422) — bổ sung khi mở rộng. `php artisan test` toàn bộ vẫn xanh; `tsc`/`eslint`/`vite build` sạch.
