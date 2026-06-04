# Facebook Ads near-real-time + AI đánh giá & tối ưu — Design

> Mới · liên quan ADR-0017 (Connector/Registry), ADR-0018 (AI provider-agnostic), extensibility-rules
> Ngày: 2026-06-04 · Trạng thái: design approved (hướng), chờ review spec
> Đề xuất **ADR mới** cho trục Integration `Ads`.

## 1. Mục tiêu

Lấy dữ liệu quảng cáo Facebook **near-real-time**, hiển thị dashboard, để **AI đánh giá hiệu suất + đề xuất tối ưu**, và cho phép **áp dụng 1-click có duyệt** (chỉnh budget, bật/tắt, đổi bid) trực tiếp lên Facebook.

Quyết định phạm vi (chốt với chủ sản phẩm 2026-06-04):
- Mức AI: **advisory + 1-click apply** (người duyệt rồi áp dụng) — KHÔNG auto-optimize toàn phần.
- Hành động 1-click v1: **budget (campaign/ad set), pause/resume (ad/ad set), bid/bid strategy (nâng cao), advisory (audience/creative/lịch — chỉ gợi ý)**.
- Nhịp dữ liệu: **near-real-time, poll ~15' + refresh tay**.
- Đa tenant SaaS: mỗi seller nối ad account riêng.

## 2. ⭐ Ràng buộc từ Facebook Marketing API (đọc kỹ — định hình thiết kế)

| Sự thật | Hệ quả thiết kế |
|---|---|
| **Không có streaming/webhook hiệu suất.** Insights refresh **~15'**; re-attribution tới **28 ngày** (số gần đây dao động). | "Real-time" = **polling ~15'**. Đánh dấu cửa sổ 28 ngày là "đang hoàn tất" để không hiểu nhầm số dao động. KHÔNG hứa số tức thời. |
| Insights là **edge bất đồng bộ**; query nặng dùng **async job** (tới ~1h gồm retry). | Sync nhỏ (active entities, window ngắn) gọi đồng bộ; query rộng/lịch sử → **async job** + poll trạng thái. |
| **Rate limit theo BUC**; header `x-fb-ads-insights-throttle` (`app_id_util_pct`, `acc_id_util_pct`, `ads_api_access_tier`). | Đọc header → **adaptive pacing**: util cao thì giãn nhịp/đẩy async; backoff. |
| Scope **`ads_read`** (đọc), **`ads_management`** (ghi: budget/pause/bid). Token = **user/system-user token** (khác page token Messenger). | OAuth + token RIÊNG, bảng riêng. |
| Production đa tenant cần **App Review (Advanced Access `ads_management`) + Standard Marketing API Access Tier + Business Verification**. Dev tier rất giới hạn. | Checklist ops bắt buộc trước khi bật prod (xem §10). |
| Cấu trúc: Business Manager → **Ad Account `act_<id>`** → Campaign → Ad Set → Ad. | Data model cây entity 4 cấp. |

Nguồn: [Insights API](https://developers.facebook.com/docs/marketing-api/insights/) · [Limits/best-practices](https://developers.facebook.com/docs/marketing-api/insights/best-practices/) · [Rate limiting](https://developers.facebook.com/docs/marketing-api/overview/rate-limiting/) · [Campaign structure](https://developers.facebook.com/docs/marketing-api/campaign-structure/).

## 3. Kiến trúc (Phương án A — đã chọn)

Trục Integration **`Ads`** mới (Connector+Registry, core không biết tên provider) + module **`Marketing`** + tái dùng lớp **`Ai`**. Cho phép thêm Google/TikTok Ads sau bằng 1 connector.

```
FE features/marketing ──HTTP──> Modules/Marketing (Controllers/Services/Jobs/Models)
                                   │ qua Contracts
                                   ├─ Integrations/Ads (AdsRegistry → FacebookAdsConnector → Graph Marketing API)
                                   └─ Integrations/Ai  (AiAssistantRegistry → analyze())  [Phase 2/3]
scheduler: ads-insights-poll (15') ──> SyncAdInsights jobs
```

## 4. Phasing (mỗi phase 1 plan→code; spec này bao cả 3, chi tiết Phase 1)

- **Phase 1 — Ingestion + Dashboard (đọc):** OAuth ads (`ads_read`), kéo cây entity + insights near-real-time, lưu snapshot, dashboard hiển thị + refresh tay.
- **Phase 2 — AI đánh giá (advisory):** `AdsEvaluationService` gom metric → AI (`analysis.generate`) → `AdRecommendation` (findings + đề xuất, chưa ghi).
- **Phase 3 — 1-click apply (ghi ngược):** `ads_management` + `AdsActionService` áp dụng budget/pause/bid có guardrail + `AdActionLog` (audit/hoàn tác).

## 5. Components

### Integrations/Ads (namespace `CMBcoreSeller\Integrations\Ads`)
- `Contracts/AdsConnector` — `code()`, `displayName()`, `capabilities()`, OAuth (`buildAuthorizationUrl`, `exchangeCodeForToken`, `refreshToken`), đọc (`listAdAccounts`, `listEntities`, `fetchInsights`, `fetchInsightsAsync`/`pollAsync`), ghi (`updateBudget`, `setStatus`, `updateBid`) — method không hỗ trợ ⇒ `UnsupportedOperation`.
- `Facebook/FacebookAdsConnector` — Graph `vXX.0`; `/me/adaccounts`, `/{entity}/insights` (fields: spend, impressions, clicks, ctr, cpc, cpm, frequency, reach, actions, purchase_roas, cost_per_action_type…), async job edge, đọc throttle header; map raw → DTO chuẩn.
- `AdsRegistry`, DTO: `AdAccountDTO`, `AdEntityDTO`, `AdInsightDTO`, `AdActionResultDTO`.
- Config `config/integrations.php` → `ads_facebook` (reuse Meta app id/secret hoặc env riêng `FACEBOOK_ADS_*`, graph_version, redirect_uri). Bật qua `INTEGRATIONS_ADS` CSV (`facebook`).

### Modules/Marketing (`CMBcoreSeller\Modules\Marketing`)
- Models (BelongsToTenant, mọi bảng có `tenant_id`): `AdAccount` (external `act_id`, token mã hoá, status, currency), `AdEntity` (level=campaign|adset|ad, parent_id self-FK, name, status, budget), `AdInsightSnapshot` (ad_entity_id, level, date_start/stop, window, fetched_at, metrics jsonb + cột số chính), `AdRecommendation` (P2), `AdActionLog` (P3).
- Jobs: `SyncAdAccountEntities` (cây entity, idempotent theo external id), `SyncAdInsights` (active entities, since=last, async khi nặng, ShouldBeUnique), `RefreshAdsToken`.
- Services: `AdsSyncService`, `AdsEvaluationService` (P2), `AdsActionService` (P3, guardrail).
- Http: `AdsOAuthController` (connect/callback), `AdAccountController` (list/connect/disconnect/refresh), `AdInsightController` (metrics + trend), `AdRecommendationController` (P2), `AdActionController` (P3, apply có xác nhận). Routes: api per-module + `GET /oauth/facebook_ads/callback` (web).
- ServiceProvider đăng ký, `loadMigrationsFrom`, bind contracts.

### Integrations/Ai (mở rộng nhỏ)
- Thêm capability `analysis.generate` + method `analyze(AiContext $ctx, array $data, string $instruction, array $schema): array` trả JSON có cấu trúc. Claude/OpenAI implement qua JSON/structured output; Manual stub cho test. Giữ ADR-0018 (core không biết tên provider).

### Frontend `features/marketing`
- Dashboard: cây account→campaign→adset→ad, metric cards + trend, badge "đang hoàn tất (≤28d)", nút **Refresh** (dispatch sync ngay) + auto-poll 15' (TanStack Query refetch). Bảng **khuyến nghị AI** (P2) + nút **Áp dụng** 1-click có modal xác nhận (P3). Icon @ant-design/icons; ưu tiên Segmented/Radio.

## 6. Data model & flow

- 1 seller → N `ad_accounts`. Insights lưu **snapshot bất biến** theo (entity, window, fetched_at) ⇒ vẽ xu hướng + biết độ tươi + so sánh trước/sau hành động. Tiền = integer theo `currency` tài khoản (giữ raw).
- Flow: connect (OAuth ads) → `SyncAdAccountEntities` → scheduler `ads-insights-poll` 15' lọc account active → `SyncAdInsights` (since=last_fetched, async khi rộng) → ingest snapshot (idempotent) → FE. Refresh tay = dispatch ngay (rate-limit guard).

## 7. Real-time & rate-limit
Poll 15' khớp refresh FB. Trước mỗi batch đọc `x-fb-ads-insights-throttle`: `util_pct` cao ⇒ giãn nhịp / chuyển async / `release()` job. Backoff theo BUC. Cửa sổ 28 ngày: cột `is_finalizing` để FE chú thích số còn dao động.

## 8. AI đánh giá (Phase 2)
`AdsEvaluationService`: gom metric theo entity + baseline/mục tiêu (CTR/CPC/CPM/ROAS/CPA/spend/frequency, xu hướng N ngày) → prompt cấu trúc (PII-safe) → `Ai::analyze` trả JSON:
```
{ findings:[{entity, metric, severity, observation}],
  recommendations:[{type: budget|pause|bid|advisory, entity_id, change:{...}, rationale, confidence, expected_impact}] }
```
Lưu `AdRecommendation` (trạng thái: new/applied/dismissed). Chi phí token tính qua `EstimatesAiCost` (đã có).

## 9. 1-click apply (Phase 3)
`AdsActionService` ánh xạ recommendation actionable → Graph (`ads_management`):
- `budget` → update daily/lifetime budget; `pause/resume` → set status; `bid` → bid amount/strategy.
- **Guardrail:** giới hạn % thay đổi budget/lần (config), chặn tắt nhầm hàng loạt, bắt buộc xác nhận người dùng, kiểm quyền RBAC, idempotent, ghi `AdActionLog` (before/after + actor + trace) để đối soát/hoàn tác. advisory ⇒ không có nút apply.

## 10. OAuth/app & checklist vận hành (bài học Lazada)
- **Tái dùng Meta app hiện có** (Meta cho 1 app nhiều product/scope): thêm product **Marketing API** + scope `ads_read`, `ads_management`, `business_management`. OAuth ads RIÊNG (`/oauth/facebook_ads/callback`), token user/system-user lưu `ad_accounts` (KHÔNG dùng page token/`channel_accounts`).
- Checklist prod (PR-blocking khi go-live): App Review **Advanced Access `ads_management`**, **Standard Marketing API Access Tier**, **Business Verification**. Dev tier chỉ đủ test nội bộ.

## 11. Error handling
- Token hết hạn/thu hồi (#190) ⇒ `ad_accounts.status=expired` → FE nhắc kết nối lại; refresh job.
- Rate-limit/throttle ⇒ release + backoff (không mất dữ liệu).
- Async job fail/timeout ⇒ retry có giới hạn, ghi lỗi vào meta, không chặn account khác.
- Lỗi Graph khi apply ⇒ rollback trạng thái recommendation + ghi `AdActionLog` lỗi, báo người dùng.

## 12. Testing
- Connector shape-tested: `Http::fake` insights + async job + throttle header; OAuth exchange; updateBudget/setStatus/updateBid request shape.
- Jobs idempotent (ingest 2 lần không nhân đôi snapshot).
- OAuth callback feature test (tạo `ad_accounts`).
- AI eval với Manual provider stub (JSON schema).
- ActionService guardrail tests (chặn vượt %/tắt hàng loạt/thiếu quyền) + audit log.
- FE typecheck/lint/build.

## 13. Build sequence (Phase 1 trước)
1. Migrations `ad_accounts`, `ad_entities`, `ad_insight_snapshots` + Marketing ServiceProvider.
2. `Integrations/Ads` contract + DTO + `FacebookAdsConnector` (OAuth + list + insights) + registry + config (test-first).
3. `AdsOAuthController` + routes + `AdAccountController` (connect/list).
4. `SyncAdAccountEntities` + `SyncAdInsights` + scheduler 15' + adaptive throttle.
5. `AdInsightController` + FE dashboard (read) + refresh.
6. Quality gate (pint/phpstan/phpunit/npm).
7. (Phase 2) Ai `analyze` + `AdsEvaluationService` + `AdRecommendation` + FE bảng khuyến nghị.
8. (Phase 3) `ads_management` + `AdsActionService` + guardrail + `AdActionLog` + FE 1-click.

## 14. ADR
Thêm **ADR mới** "Trục Integration Ads (Marketing connector registry)" — Ads là trục thứ 6 (cạnh Channels/Carriers/Payments/Messaging/Ai), connector pattern, core/module không biết tên provider; token ads tách khỏi `channel_accounts`.

## 15. Rủi ro / mở
- App Review `ads_management` có thể lâu → Phase 1+2 (read+advisory) chạy được với `ads_read` trước; Phase 3 chờ duyệt.
- Bid/strategy nâng cao, rủi ro cao → có thể tách khỏi v1 của Phase 3 nếu cần.
- Chi phí token AI khi nhiều account/entity → cân nhắc tần suất eval (không nhất thiết mỗi 15').
