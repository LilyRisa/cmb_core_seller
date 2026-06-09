# SPEC 2026-06-09 — TikTok Ads: giám sát + AI + sort/search + chuẩn hóa trạng thái

**Mục tiêu:** Bổ sung cho màn Quảng cáo TikTok (đã tách riêng) các năng lực ngang Facebook: **giám sát tự-động** (auto pause/tăng ngân sách), **phân tích AI** (per-campaign + dự báo), **sort cột + tìm kiếm**, **chuẩn hóa trạng thái** TikTok. Tách riêng UI nhưng **ghép đúng** vào hạ tầng dùng chung (AI credit, AdsReportService, AdMonitorEvaluator) — không lệch logic, không xung đột FB.

## Backend

### 1. TikTok write tối thiểu — `TikTokAdsConnector implements AdsWriteConnector`
Chỉ `updateEntity`; các method tạo/creative/pixel/page/targeting → `UnsupportedOperation`.
- **Status**: ACTIVE→`ENABLE`, PAUSED→`DISABLE`. campaign→`POST /campaign/status/update/` `{advertiser_id, campaign_ids:[id], operation_status}`; adset(adgroup)→`POST /adgroup/status/update/` `{advertiser_id, adgroup_ids:[id], operation_status}`.
- **Ngân sách (daily)**: adgroup→`POST /adgroup/budget/update/` `{advertiser_id, budgets:[{adgroup_id, budget, budget_mode:BUDGET_MODE_DAY}]}`. Campaign CBO budget: chưa hỗ trợ (cần full `/campaign/update/`) → log & bỏ qua (auto-pause vẫn chạy cho campaign).
- **advertiser_id**: interface `updateEntity(token, level, externalId, fields, currency)` KHÔNG mang account id. Truyền qua **khóa dành riêng `$fields['_advertiser_id']`** (FB bỏ qua; TikTok đọc, thiếu ⇒ ném lỗi rõ). Cả 2 caller (`AdEntityController`, `AdMonitorEvaluator`) đều có `$account` → thêm key này.
- `capabilities()`: `actions.status`/`actions.budget`=true; `ads.create`/creative/page/targeting=false. ⇒ `AdMonitorEvaluator` (`instanceof AdsWriteConnector`) chạy cho TikTok.
- Tiền VND: `FacebookMoney` coi VND zero-decimal (factor 1) ⇒ toán ngân sách đúng.

### 2. "Kết quả" đa-provider (shared, FB-neutral) — sửa coupling FacebookResultMap
`results`/cost-per-result đang tính từ `actions` qua `FacebookResultMap` (FB-only); TikTok `actions` rỗng ⇒ results=0. Gate **theo `actions` rỗng** (không hardcode provider):
- `AdsReportService::withResult`: `actions === []` ⇒ dùng `results` từ connector (TikTok=`conversion`), `result_type/label=null`.
- `AdMonitorEvaluator::resultValue`: `dto->actions === []` ⇒ trả `dto->results`.
FB không đổi (luôn có actions).

### 3. AI credit / đọc dữ liệu — TÁI DÙNG nguyên service (không sửa logic)
AI per-campaign (`CampaignInsightAnalysisService`/`GenerateCampaignAiInsight`) + dự báo (`AdsForecastService`/`GenerateAdForecast`) đã provider-agnostic, dùng `AiCreditMeter`/ví AiCredit, đọc insights qua connector. ⇒ Chỉ lộ UI cho TikTok; **credit đếm tự đúng** (1 lượt/response provider thành công). Route AI đã gate any-of `marketing_facebook|marketing_tiktok`.

## Frontend — `TikTokAdsDashboardPage` (read + giám sát + AI)
- **Sort cột**: `sorter` cho cột số (spend/impressions/clicks/ctr/cpc/results/cpm/reach) + tên/trạng thái.
- **Tìm kiếm + lọc**: ô search theo tên (`q` của `useAdReport`) + lọc trạng thái/mục tiêu.
- **Giám sát**: cột "Giám sát" + `MonitorConfigDrawer` (tái dùng) + chỉ báo monitor; `ReportTree` `canMonitor={true}`. (Cấu hình monitor = tạo `AdMonitor` row, KHÔNG ghi TikTok lúc cấu hình; auto-action chạy ở evaluator nền.)
- **AI**: `CampaignAiInsightDrawer` (per-campaign) + `ForecastTree` + nút "Tạo dự báo" (tái dùng).
- Inline-edit (pause/budget) trên bảng: **không bật** lần này (giám sát auto + sửa ở TikTok Ads Manager); tránh mở rộng quyền ghi từ UI.

### 4. Chuẩn hóa trạng thái (`format.ts`)
Map các `CAMPAIGN_STATUS_*`/`ADGROUP_STATUS_*`/`AD_STATUS_*` thường gặp (DISABLE→Tạm dừng, DELETE→Đã xoá, DELIVERY_OK→Đang chạy, NOT_START→Chưa chạy, AUDIT→Chờ duyệt, AUDIT_DENY/REJECT→Bị từ chối, BUDGET_EXCEED→Hết ngân sách…) + **humanizer fallback** (bỏ tiền tố `*_STATUS_`, map token cuối) để trạng thái lạ vẫn ra tiếng Việt thay vì raw. `statusVi` dùng map + fallback.

## Test
- Connector `updateEntity`: status campaign+adgroup (`/campaign|adgroup/status/update/`, operation_status đúng), budget adgroup (`/adgroup/budget/update/`); thiếu `_advertiser_id` ⇒ ném; capabilities.
- Results mapping: insights actions rỗng ⇒ `AdsReportService` dùng connector `results`; `AdMonitorEvaluator.resultValue` trả connector results (FB vẫn dùng FacebookResultMap).
- Monitor chạy cho TikTok: pause khi vượt ngưỡng (Http::fake report + status/update) tạo 1 action; không re-pause (đã có guard paused).

## Ngoài phạm vi
Tạo quảng cáo TikTok (create*), pixel/audience/targeting, campaign-CBO budget auto-increase, inline-edit từ UI TikTok.
