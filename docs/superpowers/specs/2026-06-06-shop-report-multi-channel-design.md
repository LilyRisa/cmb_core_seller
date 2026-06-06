# Báo cáo sàn (Shop Report) đa kênh — Lazada · Shopee · TikTok (read-only)

- **Trạng thái:** Draft → đang triển khai
- **Tác giả / Ngày:** lilyrisa · 2026-06-06
- **Module backend:** Reports (chủ sở hữu controller/route/service) · Integrations/Channels (connector mở rộng) · Billing (gating)
- **Liên quan:** báo cáo khả thi `docs/superpowers/research/2026-06-06-bao-cao-san-api-feasibility.md`; `extensibility-rules.md`; `marketing-features-must-be-plan-gated` (memory)

## 1. Mục tiêu

Một màn hình **"Báo cáo sàn"** gộp dữ liệu sức khỏe/hiệu suất/điểm phạt của các gian hàng đã kết nối (Lazada, Shopee, TikTok), **chỉ đọc**. Mỗi sàn hiển thị đúng dữ liệu API nó cho — không ép khuôn chung (xem feasibility report).

## 2. Phạm vi

- **Trong:** đồng bộ on-demand (live fetch) + hiển thị; 3 sàn; gating theo gói; xử lý lỗi/thiếu-quyền per-shop gracefully.
- **Ngoài:** lưu snapshot lịch sử (DB) + biểu đồ xu hướng + AI phân tích (spec sau); ghi/khắc phục vi phạm; webhook real-time (Shopee penalty push — tận dụng sau).

## 3. Kiến trúc

### 3.1 Interface năng lực segregated (mirror Messaging `ListsPostsConnector`)
`app/app/Integrations/Channels/Contracts/ShopReportConnector.php` — connector **tùy chọn** implement; core kiểm `instanceof` + `supports()` trước khi gọi.

```php
interface ShopReportConnector {
    public function fetchShopHealth(AuthContext $auth): ShopHealthDTO;        // cả 3 implement
    /** @return list<PenaltyPointDTO> */ public function fetchPenaltyPoints(AuthContext $auth): array; // Shopee; khác → UnsupportedOperation
    /** @return list<PunishmentDTO> */  public function fetchPunishments(AuthContext $auth): array;    // Shopee; khác → UnsupportedOperation
}
```

Capability mới trong `capabilities()` từng connector: `report.health`, `report.penalty`.

### 3.2 DTO chuẩn (`Integrations/Channels/DTO/`)
- `ShopHealthDTO { provider, kind ('health'|'performance'), overallRating:?int, overallLabel:?string, metrics: ShopMetricDTO[], raw }`
- `ShopMetricDTO { key, name, group ('fulfillment'|'listing'|'customer_service'|'rating'|'sales'|'other'), value:?float, unit ('percent'|'number'|'second'|'day'|'hour'|'minute'|'money'), target:?float, comparator:?string, passed:?bool }`
- `PenaltyPointDTO { points:int, violationType:?int, violationLabel:?string, issuedAt:?CarbonImmutable, referenceId:?string }`
- `PunishmentDTO { type:?int, typeLabel:?string, tier:?int, startAt:?CarbonImmutable, endAt:?CarbonImmutable, ongoing:bool }`

### 3.3 Ánh xạ từng sàn (tài liệu chính thức)
- **Lazada** `fetchShopHealth`: `GET /seller/performance/get` (`language=vi-VN`) → `indicators[]` → mỗi indicator thành `ShopMetricDTO` (`name`, `score`→value, `score_format`→unit, `target`, `target_format`→comparator, `target_respected`→passed). `kind='health'`, `overallRating=null` (Lazada không có rating tổng) — service tự tính summary "đạt X/Y". Penalty/Punishment → `UnsupportedOperation`.
- **Shopee** (`shopGet`, trả `response`):
  - `fetchShopHealth`: `GET /api/v2/account_health/get_shop_performance` → `overall_performance.rating` (1 Poor…4 Excellent) → `overallRating/overallLabel`; `metric_list[]` → `ShopMetricDTO` (map `metric_type`→group, `metric_id`→name, `current_period`→value, `unit`→unit, `target.value/comparator`, passed = so sánh value với target). `kind='health'`.
  - `fetchPenaltyPoints`: `GET /api/v2/account_health/get_penalty_point_history` (paginate) → `penalty_point_list[]` → `PenaltyPointDTO` (`latest_point_num`→points, `violation_type`+label, `issue_time`).
  - `fetchPunishments`: `GET /api/v2/account_health/get_punishment_history?punishment_status=1` → `PunishmentDTO`.
- **TikTok** (`get`, trả `data`, shopScoped): `fetchShopHealth`: `GET /analytics/{ver=202509}/shop/performance?start_date_ge&end_date_lt&granularity=ALL&currency=LOCAL` → `data.performance.intervals[0].sales` (gmv/orders/units) → `ShopMetricDTO` group `sales`; thêm `GET /customer_service/{ver=202407}/performance` (response %, satisfaction %, response time) group `customer_service`. `kind='performance'`, `overallRating=null`. Penalty/Punishment → `UnsupportedOperation` (TikTok health là UI-only). Version qua `config integrations.tiktok.version.analytics|customer_service`.

### 3.4 Module Reports — service + endpoint
- `ShopHealthService::reportForTenant(int $tenantId): array` — duyệt `ChannelAccount` active (provider ∈ lazada/shopee/tiktok), resolve connector qua `ChannelRegistry`; nếu `instanceof ShopReportConnector` & `supports('report.health')` → gọi `fetchShopHealth` (+ penalty/punishment nếu `supports('report.penalty')`). Bọc try/catch **per-shop**: lỗi/UnsupportedOperation/thiếu quyền → set `available=false` + `error`/`note`, không làm hỏng cả báo cáo.
- Controller `ShopHealthController@index` (module Reports) → Resource → envelope `{ data: [...] }`.
- Route: `GET /api/v1/reports/shop-health`, middleware `['api','auth:sanctum','verified','tenant','plan.feature:shop_health_reports']`.

### 3.5 Plan gating
Feature key mới **`shop_health_reports`** thêm vào `$allOff` (BillingPlanSeeder) ⇒ tự bật **Pro**, tắt Trial/Starter (giống `marketing_facebook`). Khai đồng bộ 4 nơi: seeder, `PlanFeatures` (lib/billing.tsx), `KNOWN_FEATURES` (AdminPlansPage), `FEATURE_ROWS` (PlansPage, nhãn "Báo cáo sàn (sức khỏe/điểm phạt)").

### 3.6 Frontend
- `lib/shopHealth.ts`: `useShopHealth()` (TanStack Query, `GET /reports/shop-health`).
- `pages/ShopHealthPage.tsx`: mỗi gian hàng = 1 Card (logo sàn + tên shop): rating tổng (Shopee), bảng chỉ số (đạt/không — Tag xanh/đỏ), khối điểm phạt "sao quả tạ" (Shopee), khối hiệu suất (TikTok). Sàn không có dữ liệu → empty state + link Seller Center. Read-only. Icon `@ant-design/icons` (không emoji); bộ lọc nền tảng bằng `Segmented`.
- Route `app.tsx`: `reports/shop-health`; menu `AppLayout` nhóm "Báo cáo & Kế toán" + thêm vào `BASE_KEYS`.

## 4. Lỗi & edge
- Token hết hạn → connector lỗi → per-shop `available=false`, gợi ý kết nối lại.
- Shopee thiếu quyền module 103 (`error_api_permission`) → `available=false`, note "Chưa được Shopee cấp quyền Account Health".
- Shopee đang "pending API" (CLAUDE.md): chỉ xuất hiện nếu shop Shopee đã kết nối; không thì bỏ qua.
- Live fetch tốn thời gian → fetch song song theo shop ở service (hoặc tuần tự với timeout ngắn). Có thể cache 5' (sau).

## 5. Test
- Http::fake cho từng client (Lazada `/seller/performance/get`, Shopee `account_health/*`, TikTok `analytics/.../shop/performance`) → assert mapping DTO + endpoint trả đúng cấu trúc + gating 402 khi gói thiếu feature.

## 6. Docs
- Cập nhật `docs/05-api/endpoints.md` (endpoint mới). Liên kết feasibility report.
