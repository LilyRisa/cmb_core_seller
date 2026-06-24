# ADR-0027: Vỏ ứng dụng (app shell) có thể thay thế — Web Desktop v2

- **Trạng thái:** Accepted
- **Ngày:** 2026-06-24
- **Người quyết định:** Team
- **Liên quan:** SPEC-0037 (Web Desktop shell), ADR-0006 (Ant Design 5), SPEC-0011 (Settings shell)

## Bối cảnh

User SPA hiện cố định một vỏ `AppLayout` (sidebar phẳng + header). Ta muốn cung cấp giao diện thay thế **v2 "Web Desktop"** (tab + màn Desktop ẩn dụ hệ điều hành) mà **không** viết lại các trang nghiệp vụ, và để người dùng tự chọn v1/v2. Cần một ranh giới kiến trúc rõ giữa "vỏ điều hướng" và "nội dung trang".

## Quyết định

- **Tách vỏ khỏi nội dung.** Trang/route nghiệp vụ (component dưới `pages/*`, `features/*`) là nguồn thật duy nhất, không biết mình đang nằm trong vỏ nào. "Vỏ" (`AppLayout` v1 / `DesktopShell` v2) chỉ lo: header, điều hướng, và việc render `Outlet` route. Route React Router (`/orders`, `/inventory`, …) **không đổi** giữa hai vỏ — URL vẫn là nguồn thật, deep-link/refresh chạy ở cả hai.
- **Chọn vỏ theo preference cấp người dùng.** Sau khi `auth/me` resolve, đọc `preferences.ui_shell` (`v1` mặc định, `v2` opt-in) để chọn vỏ. Đổi vỏ ⇒ lưu preference + reload (không hot-swap vỏ động).
- **`appCatalog` khai báo.** v2 gom các route hiện có thành 8 "app" qua một mảng khai báo (key, nhãn, icon, quyền, sub-menu→path). Thêm/bớt app = sửa catalog, không đụng shell — đồng tinh thần Connector/Registry: lõi không hardcode rời rạc.
- **User preference lưu cấp người dùng, không theo tenant.** Bảng `user_preferences` (`user_id`, `key`, `value` JSON) **không mang `tenant_id`** và **không** dùng `BelongsToTenant` — đây là ngoại lệ có chủ đích so với invariant "mọi bảng nghiệp vụ có tenant_id", vì sở thích giao diện thuộc về con người chứ không thuộc gian hàng. Truy vấn luôn theo `user_id` của người đăng nhập.

## Hệ quả

- **Tích cực:** thêm giao diện hoàn toàn mới mà không hồi quy trang nào; người dùng tự chọn, rollback an toàn (đổi về v1); mở rộng app trong v2 chỉ qua catalog; ranh giới vỏ/nội dung giúp tương lai thêm vỏ khác (vd mobile shell) dễ hơn.
- **Đánh đổi:** phải bảo trì **hai vỏ** song song (header dùng chung component để giảm trùng lặp); v2 keep-alive nhiều tab tốn RAM (chấp nhận, có thể giới hạn sau); thêm một bảng/endpoint preference mới.
- **Ràng buộc:** mọi trang phải tự đứng được sau bất kỳ vỏ nào (không phụ thuộc cấu trúc sidebar v1). Việc gating quyền giữ nguyên qua `useCan`, áp ở cả Desktop launcher lẫn route.
