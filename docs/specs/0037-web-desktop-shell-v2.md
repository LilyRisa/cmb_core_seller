# SPEC 0037: Giao diện v2 "Web Desktop" — vỏ shell theo tab + tùy chọn per-user

- **Trạng thái:** Implemented (2026-06-24)
- **Phase:** UX nền tảng *(song song mọi module — chỉ thay vỏ ngoài, không đụng nội dung trang)*
- **Module backend liên quan:** Tenancy (lưu user preference cấp người dùng)
- **Tác giả / Ngày:** Team · 2026-06-24
- **Liên quan:** ADR-0027 (swappable app shell), ADR-0006 (Ant Design 5), SPEC-0011 (Settings shell — nơi gắn mục "Giao diện"), `docs/01-architecture/modules.md`. Hiện thực thay thế vỏ `AppLayout` hiện có (`resources/js/components/AppLayout.tsx`).

## 1. Vấn đề & mục tiêu

Sidebar hiện tại liệt kê **phẳng 11 mục** (Đơn hàng, Trả hàng, Khách hàng, Tin nhắn, Marketplace, Kho, Mua hàng, Kế toán, Tài chính, Báo cáo, Cài đặt) trong một `AppLayout` cố định. Khi số module tăng, sidebar phẳng trở nên dài và khó định hướng.

Mục tiêu: cung cấp một **giao diện v2 dạng "Web Desktop"** (ẩn dụ màn hình nền của hệ điều hành) như một **vỏ thay thế tùy chọn**, người dùng tự bật trong Cài đặt:

- Các trang hiện có được **gom thành 9 "app"** (mỗi app = 1 module nghiệp vụ; Quảng cáo tách riêng Facebook và TikTok).
- Một tab **Desktop** ghim (không đóng được) là màn hình nền chứa lưới icon các app + tổng quan (Dashboard nhúng dưới lưới icon).
- Bấm icon → **mở app thành tab mới bên phải** (như trình duyệt); bấm app đã mở → **focus tab đang có**, không nhân đôi.
- Bên trong mỗi tab **giữ gần như nguyên** cấu trúc trang hiện tại (sub-menu của module + nội dung). Không viết lại trang nào.
- Tab **keep-alive** (giữ sống DOM khi không active) để giữ scroll/ô đang nhập.
- Lựa chọn v1/v2 + danh sách tab đang mở **lưu theo tài khoản** ở backend.

**Nguyên tắc chủ đạo:** v2 **không** thay đổi route, API, hay component trang nào. Đây là một lớp vỏ (shell) + màn Desktop + bộ quản lý tab + việc **nhóm lại** menu điều hướng. Mặc định người dùng vẫn ở v1; v2 là opt-in.

## 2. Trong / ngoài phạm vi

**Trong:**

- **FE shell mới** `DesktopShell` (thay vai trò `AppLayout` khi `ui_shell === 'v2'`):
  - Header giữ nguyên các phần tử hiện có của `AppLayout` (logo, chọn shop/tenant, chuông thông báo `🔔`, menu user, link Chrome ext / mobile, `OverQuotaBanner`, `AnnouncementPopup`, `HelpChatWidget`).
  - **Tab strip**: tab `Desktop` ghim đầu (không đóng) + các tab app (đóng được, mở thêm bên phải).
  - **Màn Desktop** (`DesktopHome`): lưới icon 9 app (chỉ hiện app người dùng có quyền — theo `useCan`) + `DashboardPage` nhúng bên dưới.
  - **Bộ quản lý tab** (Zustand store `desktopShellStore`): danh sách tab mở `{ appKey, lastPath, title }`, tab active, hành vi mở/focus/đóng; keep-alive bằng cách render mọi tab đã mở và ẩn tab không active (`display:none`) thay vì unmount.
  - **Khung trong tab** `AppFrame`: sub-menu (trái) + vùng nội dung (phải) — tái dùng đúng các route con hiện có của module.
- **Định nghĩa 9 app** (`appCatalog.ts`) — mỗi app: `key`, nhãn VN, icon `@ant-design/icons`, quyền yêu cầu, danh sách mục sub-menu (path → nhãn, **dùng lại đúng path + nhãn hiện có** trong sidebar `AppLayout`). Catalog phản chiếu các nhóm sidebar v1 hiện tại:

  | App `key` | Nhãn | Sub-menu (path + nhãn hiện có) |
  |---|---|---|
  | `sales` | Bán hàng | Đơn hàng `/orders` · Hoàn & Hủy `/returns` · Khách hàng `/customers` |
  | `messaging` | Tin nhắn | Hộp thư `/messaging` · Kết nối kênh `/messaging/channels` · Mẫu tin `/messaging/templates` · Tin tiện ích `/messaging/utility-templates` · Tự động trả lời `/messaging/auto-rules` · Kịch bản tự động `/messaging/flows` · AI training `/messaging/knowledge` |
  | `listing` | Đăng bán sàn | Sao chép sản phẩm `/marketplace/products` · Chờ đẩy lên sàn `/marketplace/to-push` · Đã có trên sàn `/marketplace/on-channel` · Chiến dịch giảm giá `/marketplace/promotions` · Gian hàng `/channels` |
  | `warehouse` | Kho | Tồn kho `/inventory` · Sản phẩm & SKU `/products` · Đề xuất nhập hàng `/procurement/demand-planning` · Nhà cung cấp `/procurement/suppliers` · Đơn mua hàng `/procurement/purchase-orders` |
  | `ads_facebook` | Quảng cáo Facebook | Tổng quan `/marketing` · Tạo quảng cáo `/marketing/ads/new` · QC bằng AI `/marketing/ads/ai` |
  | `ads_tiktok` | Quảng cáo TikTok | Tổng quan `/marketing/tiktok` |
  | `reports` | Báo cáo | Báo cáo tổng thể `/reports/overview` · Báo cáo bán hàng `/reports` · Báo cáo sàn `/shop-report` · Đối soát sàn `/finance/settlements` |
  | `accounting` | Kế toán | Tổng quan kế toán `/accounting/dashboard` · Sổ sách (Sổ nhật ký/Hệ thống TK/Cân đối/Kỳ KT) · Công nợ & Tiền (Phải thu/Phải trả/Quỹ & NH) · Báo cáo tài chính & Thuế `/accounting/reports` |
  | `settings` | Cài đặt hệ thống | Toàn bộ `/settings/*` · Nhật ký đồng bộ `/sync-logs` · Trung tâm trợ giúp `/support` |

  **Bảng điều khiển (`/`)** không là một app riêng: nội dung `DashboardPage` được nhúng ngay trong màn **Desktop home** (dưới lưới icon app), nên màn nền vừa là launcher vừa là tổng quan.

- **Mục "Giao diện" trong Cài đặt** (gắn vào `SettingsLayout`, nhóm *Tài khoản*): trang `SettingsAppearancePage` với `Radio.Group` — **Cổ điển (v1)** / **Web Desktop (v2)** (theo luật UI: ưu tiên Radio, không `<Select>`). Đổi → lưu backend → reload shell.
- **BE — user preference cấp người dùng** (module Tenancy):
  - Bảng `user_preferences` (`id`, `user_id` FK, `key`, `value` JSON, `unique(user_id, key)`, timestamps). **Cấp user, không có `tenant_id`** (preference theo người, không theo gian hàng) — ngoại lệ có chủ đích so với quy ước "mọi bảng có tenant_id"; nêu rõ trong ADR-0027.
  - Keys dùng ở slice này: `ui_shell` (`"v1"`|`"v2"`, mặc định `"v1"`), `ui_open_tabs` (JSON: mảng `{appKey,lastPath}`), `ui_active_tab` (string appKey).
  - Endpoint: `GET /api/v1/me/preferences` ⇒ `{ data: { ui_shell, ui_open_tabs, ui_active_tab } }`; `PUT /api/v1/me/preferences` `{ ui_shell?, ui_open_tabs?, ui_active_tab? }` ⇒ `{ data: {...} }`. Controller mỏng → `UserPreferenceService` → Resource (conventions §HTTP).
  - `GET /api/v1/auth/me` bổ sung `preferences` để FE biết ngay shell nào khi khởi động (tránh nháy v1→v2).
- **FE lib** `useUserPreferences()` (`lib/preferences.tsx`): đọc từ `me`, mutate qua `PUT`; **debounce** ghi `ui_open_tabs`/`ui_active_tab` (~800ms) để không spam API khi mở/đóng tab liên tục.

**Ngoài (YAGNI / spec sau nếu cần):**

- Cửa sổ nổi di chuyển/resize, snap, minimize/maximize kiểu OS thật.
- Mở **nhiều bản** cùng một app trong nhiều tab.
- Lịch sử back/forward **riêng từng tab**.
- Widget trên Desktop (đồng hồ, shortcut tùy biến, ghim app yêu thích).
- Dark mode / đổi theme màu (tách spec riêng nếu làm).
- Áp dụng cho **Admin SPA** (`admin.tsx`) — slice này chỉ cho user SPA.

## 3. Hành vi & quy tắc

- **Chọn shell khi khởi động:** sau `me` resolve, nếu `preferences.ui_shell === 'v2'` → render `DesktopShell`, ngược lại render `AppLayout` (v1) như cũ. Mặc định thiếu preference = `v1` (không xáo trộn người dùng hiện tại).
- **URL vẫn là nguồn thật:** route React Router giữ nguyên (`/orders`, `/inventory`, …). Mở v2 với một path → app chứa path đó tự mở thành tab active; tab Desktop luôn hiện. Refresh / deep-link / mở link từ thông báo vẫn hoạt động ở cả hai shell.
- **Mở app:** bấm icon Desktop hoặc điều hướng tới path thuộc app chưa mở → tạo tab mới ở cuối, active nó, mở sub-route mặc định (mục đầu của app) hoặc path được yêu cầu.
- **Focus thay vì nhân đôi:** mở lại một app đã có tab → **chuyển sang tab đó** (không tạo tab mới); nếu kèm path cụ thể thì cập nhật `lastPath` của tab đó và điều hướng.
- **Đóng tab:** tab app đóng được; Desktop **không** đóng. Đóng tab đang active → active tab liền kề (ưu tiên bên trái, nếu hết về Desktop).
- **Keep-alive:** mọi tab đã mở vẫn ở trong DOM; tab không active ẩn bằng `display:none`. Chuyển tab giữ nguyên scroll, ô đang nhập, state component. (Đánh đổi: nhiều tab nặng cùng lúc tốn RAM — chấp nhận, không giới hạn cứng số tab ở slice này.)
- **Quyền:** app người dùng không có quyền (theo `useCan` như sidebar v1) → ẩn icon khỏi Desktop và không cho mở tab. Khớp đúng gating module hiện tại.
- **Đổi shop (tenant):** giữ hành vi hiện tại; đổi tenant làm mới dữ liệu các tab (qua React Query) — không cần đóng tab.
- **Đổi v1↔v2:** lưu `ui_shell` rồi reload trang để áp shell mới (đơn giản, tránh chuyển shell động dễ lỗi state).
- **Lưu phiên tab:** `ui_open_tabs` + `ui_active_tab` lưu debounce; lần đăng nhập sau khôi phục đúng các tab đang mở. Path không hợp lệ / app mất quyền khi khôi phục → bỏ qua tab đó lặng lẽ.

## 4. API & UI

**Endpoint** (cập nhật `docs/05-api/endpoints.md`):

- `GET /api/v1/me/preferences` (sanctum) ⇒ `{ data: { ui_shell, ui_open_tabs, ui_active_tab } }`.
- `PUT /api/v1/me/preferences` (sanctum) `{ ui_shell?: "v1"|"v2", ui_open_tabs?: [{appKey,lastPath}], ui_active_tab?: string }` ⇒ `{ data: {...} }`. Giá trị `ui_shell` ngoài tập cho phép ⇒ `422 VALIDATION_FAILED`. Merge theo key (không wipe key khác).
- `GET /api/v1/auth/me`: thêm trường `preferences` (đọc-thuận tiện; nguồn ghi vẫn là `/me/preferences`).

**BE files:**
- `app/app/Modules/Tenancy/Database/Migrations/xxxx_create_user_preferences_table.php`
- `app/app/Modules/Tenancy/Models/UserPreference.php` (cast `value` → array)
- `app/app/Modules/Tenancy/Services/UserPreferenceService.php` (get/put theo `user_id`+`key`, `updateOrCreate`)
- `app/app/Modules/Tenancy/Http/Controllers/UserPreferenceController.php`, `Requests/UpdatePreferencesRequest.php`, `Resources/UserPreferenceResource.php`
- Route trong `app/Modules/Tenancy/Http/routes.php` (hoặc `routes/api.php` nhóm sanctum).

**FE files:**
- `resources/js/components/desktop/DesktopShell.tsx` (vỏ v2)
- `resources/js/components/desktop/TabStrip.tsx`, `DesktopHome.tsx`, `AppFrame.tsx`
- `resources/js/lib/desktop/desktopShellStore.ts` (Zustand: tabs, active, open/focus/close)
- `resources/js/lib/desktop/appCatalog.ts` (định nghĩa 9 app + sub-menu, tái dùng path/nhãn hiện có)
- `resources/js/lib/preferences.tsx` (`useUserPreferences`, mutate debounce)
- `resources/js/pages/SettingsAppearancePage.tsx` (Radio v1/v2) + route + mục menu trong `SettingsLayout`
- `resources/js/app.tsx`: chọn `DesktopShell` vs `AppLayout` theo `preferences.ui_shell` sau khi `me` resolve.

## 5. Cách kiểm thử

- **BE (PHPUnit):** `GET/PUT /me/preferences` (lưu & đọc lại đúng, `ui_shell` sai ⇒ 422, merge không wipe key khác, preference cô lập theo từng user). `me` trả `preferences`.
- **FE (kiểm tay — dự án chưa có JS test runner):**
  - Bật v2 trong Cài đặt → reload → thấy Desktop + 8 icon (đúng theo quyền).
  - Bấm app → mở tab; bấm lại app đang mở → focus, không nhân đôi.
  - Mở 2–3 tab, nhập dở 1 form, chuyển tab rồi quay lại → giữ nguyên (keep-alive).
  - Đóng tab active → active tab liền kề; Desktop không đóng được.
  - Refresh / dán deep-link `/orders` ở v2 → mở đúng tab Bán hàng.
  - Tài khoản nhân viên thiếu quyền Kế toán → không thấy icon Kế toán.
  - Tắt v2 (về v1) → reload → đúng `AppLayout` cũ, không hồi quy.
- **Gate chất lượng:** `php artisan test` xanh; `pint --test`, `phpstan`, `npm run lint && typecheck && build` sạch. Không trang nội dung nào bị sửa hành vi.

## 6. Rủi ro & ghi chú

- **Keep-alive nhiều tab:** nhiều trang nặng (vd Tin nhắn realtime + Báo cáo) sống đồng thời có thể tốn RAM/socket. Slice này chấp nhận; nếu cần, spec sau thêm giới hạn số tab hoặc "ngủ đông" tab cũ.
- **`user_preferences` không có `tenant_id`:** preference theo người dùng, không theo gian hàng — ngoại lệ có chủ đích với quy ước `BelongsToTenant`; ghi rõ trong ADR-0027. Không áp global scope tenant cho bảng này.
- **Tránh nháy shell:** vì shell chọn theo `me`, phải đợi `me` resolve trước khi render shell (đã có gate `RequireAuth`); thêm `preferences` vào `me` để quyết một lần.
- **Mở rộng:** `appCatalog` là một mảng khai báo → thêm/bớt app tương lai chỉ sửa catalog, không đụng shell.
