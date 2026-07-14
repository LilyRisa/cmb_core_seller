# Sửa bộ đếm AI + Trang chi tiết tenant chuyên sâu cho Admin — Design

> Ngày: 2026-07-15 · Trạng thái: approved. Làm trên `main`.
> Phần A là bug fix (đã xác minh nguyên nhân qua code). Phần B là feature mới: thay Drawer chi tiết
> tenant hiện tại (`AdminTenantDrawer.tsx`) bằng 1 trang riêng, giữ nguyên mọi hành động đã có + thêm
> các phần mới theo yêu cầu.

## A. Sửa bộ đếm lượt AI (2 bug đã xác minh)

**Bug 1 — race condition mất lượt đếm (lost-update, không khoá row).**
`AiCreditService::consume()/record()/countUsage()` (`app/app/Modules/Billing/Services/AiCreditService.php:99-166`)
đọc-sửa-ghi `ai_credit_wallets`/`ai_usage_counters` không có `DB::transaction()+lockForUpdate()` (khác
`VoucherService` đã làm đúng). 2 lượt AI chạy đồng thời cho cùng tenant (vd job auto-reply + user bấm
"gợi ý AI" cùng lúc) → lượt sau ghi đè lượt trước, mất 1 lượt đếm. `countUsage()` còn tệ hơn:
`firstOrCreate` không khoá, 2 request cùng tạo row mới đụng unique index → `QueryException` bị nuốt bởi
`catch (\Throwable)` ở dòng 163 → **mất hẳn 1 lượt**, không chỉ ghi sai.

**Fix:** bọc `wallet()`+`consume()`/`record()` trong `DB::transaction()` với
`AiCreditWallet::lockForUpdate()`; `countUsage()` cũng bọc transaction + lock row (hoặc dùng
`upsert()`/`ON CONFLICT` nguyên tử thay `firstOrCreate`+`increment` 2 bước).

**Bug 2 — `ShopHealthAnalysisService` không ghi vào `ai_usage_counters`.**
`ShopHealthAnalysisService::analyze()` (`app/app/Modules/Channels/Services/ShopHealthAnalysisService.php:35`)
gọi `consume()` thay vì `record()` — trừ đúng ví nhưng bỏ qua bước đếm theo tính năng, khiến báo cáo
lượt dùng theo feature bị thiếu mọi lần dùng "phân tích sức khoẻ gian hàng".

**Fix:** đổi thành `record($tenantId, 1, 'shop_health')`.

## B. Trang chi tiết tenant cho Admin (`/admin/tenants/:id`)

**Kiến trúc:** thêm 1 route React Router mới trỏ `AdminTenantDetailPage` (file mới), tái dùng gần như
toàn bộ hook/API đã có trong `AdminTenantDrawer.tsx` (đổi từ Drawer sang bố cục trang + Tabs to hơn).
`AdminTenantsPage` (danh sách) đổi hành vi click-row từ "mở Drawer" sang "điều hướng
`/admin/tenants/{id}`". **Không xoá** Drawer nếu còn chỗ dùng khác — kiểm tra khi triển khai, nếu chỉ
dùng ở đây thì xoá luôn (đừng để code chết).

Backend: `AdminTenantController::show()` (`app/app/Modules/Admin/Http/Controllers/AdminTenantController.php:75-149`)
mở rộng payload — thêm các khối mới bên dưới, KHÔNG đổi field cũ (mọi consumer hiện tại vẫn chạy được
qua chuyển đổi).

### B1. Hạn mức AI (mới hoàn toàn)

- **Hiện tại:** tái dùng `AiCreditService::summary($tenantId)` đã có sẵn (enabled/unlimited/monthly_allowance/
  period_used/purchased_balance/available) — thêm vào response `show()`.
- **Lịch sử dùng theo tháng:** dữ liệu đã có qua `ai_usage_counters.period_ym` — thêm 1 method mới
  `AiUsageReportService::breakdownForTenant(int $tenantId): array` (song song `breakdownForUser` đã có,
  nhưng `groupBy tenant_id` thay `user_id`), trả `by_month` (+ `by_feature` tái dùng luôn, hữu ích).
- **Cộng/trừ hạn mức tay:** `AiCreditService::grantPurchase()` đã có (cộng, chặn trần 5000). Thêm
  method đối xứng `deduct(int $tenantId, int $amount): int` (trừ `purchased_balance`, sàn 0, trả số
  thực trừ được — giống pattern `grantPurchase` trả số thực cộng).
  - Endpoint mới `POST /api/v1/admin/tenants/{tid}/ai-credit/adjust` body
    `{amount: int (âm=trừ, dương=cộng), reason: string (≥10 ký tự, theo `requireReason` pattern có sẵn)}`.
  - Ghi `AuditLog action='admin.ai_credit.adjust'`, `changes: {amount, reason, balance_before, balance_after}`
    — đúng pattern mọi action khác trong `AdminTenantService`.
- **Lịch sử admin đã cộng/trừ:** chính là các `AuditLog` action `admin.ai_credit.adjust` của tenant đó —
  không cần bảng riêng, lọc theo action trong danh sách audit log (xem B5).

### B2. Số lượng SKU (mới hoàn toàn)

Không có cột/API đếm SKU theo tenant ở đâu trong hệ thống (không có plan limit SKU). Thêm query đơn
giản trong `AdminTenantController::show()`: `Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $id)->count()`.
Chỉ cần **số đếm**, không cần danh sách chi tiết SKU (user hỏi "số lượng sku" — không yêu cầu duyệt
từng SKU, khác với yêu cầu rõ ràng "danh sách" cho kênh/page ở mục B3).

### B3. Danh sách kênh/page (đã có, chỉ hiển thị rõ hơn)

`channel_accounts` đã trả về đầy đủ trong `show()` (dòng 103-110) — giữ nguyên, hiển thị dạng bảng có
tìm kiếm/lọc trên trang mới thay vì list rút gọn trong Drawer. Đây chính là "số lượng page" + "danh
sách" người dùng yêu cầu.

### B4. Đơn hàng theo ngày (mới hoàn toàn)

Không có sẵn cho admin (`ManualOrderDailyStatsService` chỉ tính đơn `source=manual` cho mục đích đối
soát ads, không phải admin). Thêm method mới `AdminTenantService::dailyOrderCounts(int $tenantId, int $days = 30): array`
— query `orders` (mọi `source`, không chỉ manual) group theo ngày (`DATE(placed_at)` hoặc `DATE(created_at)`
nếu `placed_at` null), trả `[{date, count, grand_total_sum}]` 30 ngày gần nhất. Endpoint mới
`GET /api/v1/admin/tenants/{tid}/orders/daily-stats?days=30`.

### B5. Lịch xử lý đơn (mới, dùng lại `OrderStatusHistory` đã có)

`OrderStatusHistory` (`app/app/Modules/Orders/Models/OrderStatusHistory.php`) đã ghi đủ
`order_id, from_status, to_status, raw_status, source, changed_at, payload` mỗi lần đổi trạng thái đơn —
chỉ thiếu 1 view admin tổng hợp theo tenant. Endpoint mới
`GET /api/v1/admin/tenants/{tid}/order-status-history?page=1` — paginate 50/trang, sắp `changed_at DESC`,
kèm `order_number` (join `orders`) để admin biết đơn nào.

### B6. Audit log đầy đủ (mở rộng cái đã có)

`show()` hiện lọc `action like 'admin.%'` + giới hạn 20 dòng (dòng 87-90). Trang mới cần xem **toàn bộ**
lịch sử (không chỉ hành động admin — cả log khác nếu `AuditLog` có ghi hành động tenant-user, cần kiểm
tra lúc code) + phân trang thay vì cắt cứng 20. Đổi endpoint `show()` bỏ giới hạn 20 + thêm endpoint
riêng `GET /api/v1/admin/tenants/{tid}/audit-logs?page=1` phân trang đầy đủ (giữ `show()` chỉ trả 10
dòng mới nhất làm preview, trang chi tiết tự fetch thêm khi cần — tránh payload `show()` phình to).

### B7. Lịch sử đăng nhập nhân viên tenant (mới hoàn toàn)

**Chưa tồn tại ở bất kỳ đâu trong hệ thống.** Cần:
- Migration bảng `user_login_events`: `id, user_id (FK users), ip_address (string nullable),
  user_agent (string nullable), logged_in_at (timestamp), created_at`. Không cần `tenant_id` trực tiếp
  (login không gắn tenant cụ thể — user có thể thuộc nhiều tenant qua `tenant_users`); trang admin join
  qua `tenant_users` để lọc login của user thuộc tenant đang xem.
- Listener mới `LogUserLogin` lắng nghe `Illuminate\Auth\Events\Login` (Laravel tự bắn sự kiện này ở
  mọi `Auth::guard('web')->login()`, xác nhận đúng chỗ gọi trong `AuthController::startSession()` —
  không cần sửa AuthController). Đăng ký qua `Event::listen(Login::class, LogUserLogin::class)` trong
  `TenancyServiceProvider::boot()`.
  - Áp dụng CHỈ guard `web` (tenant users) — không log login của `admin_web` guard (super-admin đã có
    audit riêng, không lẫn).
- Endpoint mới `GET /api/v1/admin/tenants/{tid}/login-history?page=1` — join `tenant_users` (lọc
  `tenant_id=$tid`) → `user_login_events`, phân trang, kèm `user_name/email`.

## Testing

- Unit/feature: `AiCreditService` — 2 lượt `record()` đồng thời (giả lập bằng gọi liên tiếp trong 1
  transaction test hoặc kiểm tra lock được acquire) không mất lượt đếm; `ShopHealthAnalysisService`
  dùng feature xong → `ai_usage_counters` có row `feature='shop_health'`.
  - Đơn giản hoá test đồng thời thật (2 process) là không thực tế trong PHPUnit — verify bằng cách
    kiểm tra `lockForUpdate()` THỰC SỰ được gọi (mock/spy DB query log) thay vì dựng race thật.
- Feature: `deduct()` trừ đúng, không âm dưới 0, ghi đúng audit log; endpoint admin adjust yêu cầu
  `reason` ≥10 ký tự giống mọi action khác.
- Feature: mỗi endpoint mới (SKU count, daily order stats, order-status-history, audit-logs phân trang,
  login-history) — trả đúng dữ liệu tenant đang xem, KHÔNG lẫn tenant khác (test kiểu
  `test_does_not_leak_other_tenant_*` đã là convention có sẵn trong `AdminTenantController` test suite).
- Feature: `LogUserLogin` — login qua guard `web` tạo đúng 1 row `user_login_events`; login `admin_web`
  KHÔNG tạo row.
- FE: `npm run typecheck && npm run lint && npm run build`.

## Giới hạn / phạm vi

- Không thêm phân quyền admin theo role (hệ thống admin hiện phẳng, 1 tầng — theo đúng convention hiện
  tại: mọi action nhạy cảm gate bằng `reason` bắt buộc + audit log, không phải Gate ability mới).
- `deduct()` chỉ trừ `purchased_balance` (credit mua thêm), KHÔNG trừ được `period_used`/hạn mức tặng
  kèm gói (theo đúng ngữ nghĩa `grantPurchase()` đối xứng — hạn mức tặng theo gói tự reset hàng tháng,
  admin không cần/không nên chỉnh tay).
- Không xây login-history cho phía admin (`admin_web` guard) — admin đã có audit log riêng.
