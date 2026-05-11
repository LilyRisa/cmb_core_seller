# ADR-0010: RBAC bằng Role enum + permission map tự viết (không dùng spatie/laravel-permission ở giai đoạn này)

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team (Phase 0)

## Bối cảnh

`01-architecture/multi-tenancy-and-rbac.md` §3 nêu hai lựa chọn cho phân quyền: dùng `spatie/laravel-permission` ở "team/tenant mode", hoặc bảng/enum tự viết — và yêu cầu chốt ở Phase 0 (ghi ADR nếu chọn lib).

Nhu cầu Phase 0: 6 vai trò cố định (`owner`, `admin`, `staff_order`, `staff_warehouse`, `accountant`, `viewer`), mỗi vai trò một tập permission string cố định; phân quyền theo **tenant** (một user có vai trò khác nhau ở các tenant khác nhau); chưa cần vai trò tuỳ biến do người dùng tạo, chưa cần gán permission lẻ cho từng user.

`spatie/laravel-permission` mạnh nhưng: thêm 3–4 bảng (`roles`, `permissions`, `model_has_roles`, `role_has_permissions`, pivot teams), cần cấu hình team-id resolver khớp với `CurrentTenant`, và phần lớn sức mạnh (CRUD role/permission động) chưa dùng tới. Trùng lặp một phần với cơ chế tenant-scope sẵn có.

## Quyết định

- Giai đoạn này: **RBAC = enum `CMBcoreSeller\Modules\Tenancy\Enums\Role`** với phương thức `permissions()` trả tập permission string cho mỗi vai trò, và `can(string $permission)`; lưu vai trò ở pivot `tenant_user.role`. Quyền chi tiết kiểm qua `Gate::before` trong `TenancyServiceProvider` (đọc vai trò của `CurrentTenant`), nên `$user->can('orders.update')` / `@can` / Policy hoạt động bình thường. Hành động nhạy cảm ghi `audit_logs`.
- `tenant_user.channel_account_scope` (jsonb) đã có sẵn để giới hạn user xem được những gian hàng nào (sub-account) — thực thi khi module Channels có dữ liệu thật (Phase 1).
- **Không** cài `spatie/laravel-permission` lúc này. Khi nào cần vai trò tuỳ biến do owner/admin tạo + gán permission động → mở ADR mới đánh giá lại (lib hoặc bảng `roles`/`permissions` tự viết tích hợp tenant-scope), và migrate enum hiện tại thành seed của bảng.

## Hệ quả

- Tích cực: ít bảng, ít phụ thuộc, dễ hiểu; permission map là code (review qua PR, test bằng unit test — xem `tests/Unit/RolePermissionsTest.php`); khớp tự nhiên với tenant-scope.
- Đánh đổi: chưa hỗ trợ vai trò/permission tuỳ biến runtime; đổi tập permission của một vai trò = sửa code + deploy (chấp nhận được ở giai đoạn này).
- Việc theo sau: khi thực thi `channel_account_scope`, bổ sung kiểm tra ở query/policy của module Channels; cân nhắc ADR mới nếu khách hàng yêu cầu vai trò tuỳ biến.
