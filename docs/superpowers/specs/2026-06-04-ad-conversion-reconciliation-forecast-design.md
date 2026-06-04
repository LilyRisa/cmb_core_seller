# Đối soát Ad ↔ đơn thủ công + AI dự báo chiến lược — Design

> Tiếp nối SPEC 2026-06-04 Facebook Ads (Phase 1 đã merge). ADR-0024 (Ads axis), ADR-0018 (AI).
> Ngày: 2026-06-04 · Trạng thái: approved.

## 1. Mục tiêu
Lấy **chỉ số chuyển đổi + hội thoại Messenger theo campaign** từ ad insights, **đối soát với đơn thủ công (`orders.source='manual'`) trong ngày**, rồi **AI dự báo chiến lược**. 

Ràng buộc chủ sản phẩm: **additive, không đụng luồng khác** (orders/messaging giữ nguyên); **tiết kiệm quota AI** (forecast chỉ chạy on-demand + cache, KHÔNG tự động).

## 2. Hai bước (ít rủi ro)
- **2a — Enrich + đối soát (no AI):** insights thêm `messaging_conversations`+`leads` (từ `actions`), service đối soát theo ngày vs đơn thủ công, bảng dashboard. Chạy với `ads_read` sẵn có.
- **2b — AI dự báo (on-demand, cached):** capability `analysis.generate`, `AdsForecastService`, bảng cache `ad_forecasts`, panel dashboard.

## 3. Components

**2a:**
- Migration: thêm cột `messaging_conversations` (uint), `leads` (uint) vào `ad_insight_snapshots`.
- `FacebookAdsConnector.fetchInsights`: xin thêm field `actions`; helper map `onsite_conversion.messaging_conversation_started_7d`→messaging_conversations, `lead`/`leadgen.*`→leads. `AdInsightDTO` + `SyncAdInsights` ghi 2 cột. (Insights `actions` cho số mess theo campaign ⇒ **không cần webhook referral/leadgen** — gọn, không đụng Messaging connector.)
- **Orders contract** `CMBcoreSeller\Modules\Orders\Contracts\ManualOrderDailyStats` (method `dailyManualStats(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): array<dateStr,{count:int,revenue:int}>`) + impl trong Orders + bind ở OrdersServiceProvider. Marketing phụ thuộc **contract**, không đụng nội bộ Orders (luật module).
- `AdReconciliationService` (Marketing): theo ngày (account-level) gộp ad metrics (spend/conversations/leads từ snapshots) + manual stats (qua contract) → `{date, spend, conversations, leads, manual_orders, manual_revenue, cost_per_conversation, cost_per_order, conv_to_order_pct}`.
- API `GET /api/v1/marketing/ad-accounts/{id}/reconciliation?days=14` (Gate `marketing.view`).
- FE: bảng đối soát theo ngày trong `/marketing`.

**2b (AI provider RIÊNG, tách hoàn toàn — chốt với chủ sản phẩm):**
- **KHÔNG** dùng `ai_providers`/`AiAssistantConnector` của messaging. Marketing có **provider AI riêng** lưu DB, super-admin quản qua **màn hình admin riêng**.
- Migration `marketing_ai_providers` (mirror `ai_providers` nhưng RIÊNG): `code` (pk), `display_name`, `adapter` (anthropic|openai_compatible|manual), `api_key` (encrypted), `base_url`, `default_model`, `is_active`, timestamps.
- Migration `ad_forecasts` (tenant_id, ad_account_id, payload json, provider_code, model, generated_at). 1 bản mới nhất/account.
- `MarketingAnalysisClient` (Marketing-owned, **không đụng Integrations/Ai**): đọc `marketing_ai_providers` active → gọi LLM theo adapter (anthropic Messages API / openai_compatible Chat Completions, JSON output) → trả array. Adapter `manual` hoặc chưa cấu hình → stub deterministic (dev/test, **0 quota**).
- `AdsForecastService`: gom chuỗi đối soát N ngày → `MarketingAnalysisClient->analyze()` → JSON `{forecast:{next_7d:{conversations,orders,spend,projected_cost_per_order}}, strategy:[{action,campaign?,rationale,confidence}]}` → lưu `ad_forecasts`.
- **Tiết kiệm quota:** `POST /ad-accounts/{id}/forecast` mới gọi AI; **cooldown** (config `marketing.forecast_cooldown_minutes`, mặc định 360) — trong cooldown trả **cache**, KHÔNG gọi AI. `GET /ad-accounts/{id}/forecast` trả cache. KHÔNG gọi AI trong poll/scheduler/load dashboard.
- **Admin CRUD** `marketing_ai_providers`: API `/api/v1/admin/marketing-ai-providers` (super-admin) + màn hình admin riêng (bundle admin.tsx).
- FE app: panel "Dự báo & Chiến lược" trên `/marketing` — cache + nút "Tạo dự báo" (disable trong cooldown) + `generated_at`.

**Tách bạch:** module Marketing tự sở hữu provider + client AI; không import `Integrations/Ai`, không sửa `ai_providers`/connector messaging ⇒ luồng AI messaging tuyệt đối không đổi.

## 4. Không đụng luồng khác
- Marketing additive; đọc Orders qua contract (Orders chỉ thêm 1 contract+impl, không sửa luồng tạo đơn). AI chỉ thêm method `analyze` (messaging generateReply không đổi). Insights enrich chỉ thêm cột + field — `fetchInsights` cũ vẫn chạy. Forecast off cho tới khi user bấm.

## 5. Testing
- Connector: `fetchInsights` parse `actions`→conversations/leads (Http::fake).
- Orders contract impl: đếm/đếm doanh thu manual theo ngày.
- `AdReconciliationService`: tính đúng chỉ số + ghép ngày.
- `AdsForecastService`: dùng Manual AI stub → JSON; **cooldown trả cache, không gọi AI** (assert AI không bị gọi lần 2).
- API reconciliation + forecast (RBAC, tenant scope, cooldown).
- FE typecheck/lint/build.

## 6. Build sequence
2a: migration cột → connector actions + DTO + SyncAdInsights → Orders contract+impl → AdReconciliationService → API → FE bảng → gate.
2b: migration ad_forecasts → Ai analyze capability (Manual trước cho test) → AdsForecastService + cooldown → API → FE panel → gate.

## 7. Giới hạn
Khớp đơn ở mức **ngày/tài khoản** (đơn thủ công không gắn campaign). Gắn đơn↔campaign chính xác = việc sau (cần thêm trường nguồn lúc tạo đơn).
