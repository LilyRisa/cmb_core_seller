# Phân tích AI theo từng chiến dịch — sub-feature G — Design

> Phân tích AI cho **một chiến dịch cụ thể** với **số ngày tùy chọn**, **chỉ số tùy chọn**, và **nội dung bài viết + lượt like/comment** của các quảng cáo trong chiến dịch.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). Làm trên `main`. Tái dùng hạ tầng AI forecast v1.

## 1. Bối cảnh / tái dùng
- Forecast v1 (per **account**) đã có: `MarketingAnalysisClient` (contract) + `LlmMarketingAnalysisClient`, model `AdForecast`, job `GenerateAdForecast`, controller cooldown-gated async + email. G nhân rộng pattern này xuống cấp **campaign**.
- `fetchInsights($token, $campaignExternalId, 'campaign'|'ad', $query)` gọi `/{campaign_id}/insights` ⇒ lấy insights đúng 1 chiến dịch (và breakdown theo ad). `fetchAdCreatives` trả creatives toàn account kèm `adId`/`pagePostId`.
- "core không biết sàn" giữ nguyên: field Graph chỉ ở connector.

## 2. Connector — engagement của bài (mới)
Thêm vào `AdsWriteConnector` (nơi `listPagePosts` đã ở) + Facebook implement:
- `fetchPostEngagement(string $accessToken, array $postIds): array<string, array{likes:int,comments:int,shares:int,message:?string}>`
  - Batch Graph `GET /?ids=ID1,ID2&fields=likes.summary(true).limit(0),comments.summary(true).limit(0),shares,message`.
  - Trả map keyed theo post id; thiếu field → 0/null; lỗi HTTP → ném `RuntimeException` (service nuốt best-effort).
  - `$postIds` rỗng → trả `[]` (không gọi HTTP).

## 3. Lưu trữ (tenant-scoped)
- Migration `campaign_ai_insights`: `id`, `tenant_id`(index), `ad_account_id`(index), `campaign_external_id`(string,index), `payload` json, `params` json (`{days, metrics, include_engagement}`), `provider_code` nullable, `model` nullable, `generated_at` timestamp, timestamps; `unique(['ad_account_id','campaign_external_id'])` (giữ 1 bản mới nhất / chiến dịch).
- Model `CampaignAiInsight` (BelongsToTenant; fillable; casts payload/params=>array, generated_at=>datetime; `@property` docblock cho phpstan).

## 4. Service `CampaignInsightAnalysisService`
- `generate(AdAccount $account, string $campaignExternalId, array $params, bool $force=false): CampaignAiInsight`
  - cooldown `config('marketing.campaign_insight_cooldown_minutes', 60)`: trong cửa sổ ⇒ trả bản cache, KHÔNG gọi AI. Khi `$params` khác lần trước thì coi như yêu cầu mới (bỏ qua cooldown) để người dùng đổi chỉ số/số ngày được phân tích lại.
  - `buildData`: 
    - `campaign`: name/objective/status/budgets (từ `AdEntity` external_id, không global scope theo account).
    - `range`: since = now-(days-1), until = today.
    - `campaign_metrics`: từ `fetchInsights(campaign)` → lọc theo `metrics` đã chọn.
    - `ads`: `fetchInsights(ad)` → mỗi ad lọc metrics + `ad_id`.
    - `creatives`: `fetchAdCreatives` lọc các ad thuộc tập `ad_id` của chiến dịch → primary_text/headline/cta/post_id.
    - `engagement` (nếu `include_engagement` & connector `instanceof AdsWriteConnector`): `fetchPostEngagement` cho post_id của creatives → like/comment/share/message. Best-effort try/catch + Log::warning.
  - instruction VN: "phân tích riêng chiến dịch này — hiệu quả theo chỉ số đã chọn, nội dung bài & tương tác, đề xuất hành động."
  - `client->analyze(data, instruction)` → `updateOrCreate` theo `(ad_account_id, campaign_external_id)`.
- `cached(account, campaignExternalId): ?CampaignAiInsight`.
- Lọc metrics dùng allow-list = khoá của `AdsReportService::metrics` (spend/impressions/clicks/reach/ctr/cpc/cpm/frequency/purchase_roas/messaging_conversations/leads).

## 5. Job + Controller + Request + Routes
- Job `GenerateCampaignAiInsight(int $adAccountId, string $campaignExternalId, array $params)` (queue `marketing-ai`, ShouldBeUnique theo campaign): gọi service force=true, gửi email Owner/Admin (tái dùng pattern; thông báo riêng `MarketingCampaignInsightReadyNotification` — tối giản, hoặc tái dùng channel mail trực tiếp). v1: chỉ generate, KHÔNG email (khác forecast) để giảm phạm vi — kết quả hiển thị qua poll GET. *(Quyết định: bỏ email cho G; nói rõ ở §8.)*
- Controller `CampaignAiInsightController` (extends base Controller, Gate `marketing.view`):
  - `GET ad-accounts/{id}/campaigns/{campaignId}/ai-insight` → cached (no AI).
  - `POST ad-accounts/{id}/campaigns/{campaignId}/ai-insight` (body days/metrics/include_engagement) → cooldown-gated async dispatch, trả `{data, status, queued}`.
- Request `CampaignAiInsightRequest`: `days` int 1..90 (default 14); `metrics` array, each `in:`(allow-list); `include_engagement` boolean (default true).
- Routes thêm vào group `api/v1/marketing` (whereNumber id).

## 6. Frontend
- `lib/marketing.tsx`: types `CampaignAiInsight` (payload generic + params + generated_at), hooks `useCampaignAiInsight(accountId, campaignId)` (GET, enabled khi mở), `useGenerateCampaignAiInsight()` (POST body {days, metrics, include_engagement}).
- `MarketingDashboardPage`: ở level `campaign`, thêm cột hành động cuối bảng — nút `<BulbOutlined/> Phân tích AI` mở **Drawer** `CampaignAiInsightDrawer`:
  - Cấu hình: số ngày (Segmented 7/14/30 + InputNumber tùy chỉnh 1..90), chỉ số (Checkbox.Group allow-list, mặc định spend/impressions/clicks/ctr/cpc/purchase_roas), Switch "Kèm bài viết + like/comment".
  - Nút "Phân tích" → POST; nếu `queued` hiển thị "Đang phân tích…" + poll GET (refetchInterval khi đang chờ). Hiển thị payload (nhận xét + khuyến nghị + đánh giá creative) tương tự khối forecast.
  - Icons @ant-design/icons. Không emoji. Tránh `<Select>` cho tập nhỏ (số ngày dùng Segmented).

## 7. Bất biến / không đụng
- Pass-through; bảng mới có `tenant_id` + BelongsToTenant; controller mỏng → service → response; tiền = VND integer (giữ `spend` integer như insights hiện có).
- Không sửa forecast account-level; G độc lập.

## 8. Quyết định phạm vi
- **Không email** cho G v1 (forecast account-level mới email). Kết quả xem trực tiếp trong Drawer (poll). Email per-campaign là follow-up nếu cần.
- Engagement best-effort: lỗi token/quyền không làm hỏng phân tích (vẫn chạy với metrics + creative text).

## 9. Testing
- BE connector: `fetchPostEngagement` gọi đúng `?ids=&fields=` + map like/comment/share/message; rỗng → `[]`. (Http::fake.)
- BE service/feature: POST tạo job (cooldown-gated), GET trả cached; params đổi ⇒ bỏ qua cooldown; tenant isolation. Dùng stub `MarketingAnalysisClient` (binding test) để không gọi AI thật.
- FE: typecheck + lint + build.

## 10. Giới hạn / nối tiếp
- Email per-campaign, lịch sử nhiều bản phân tích (hiện giữ 1 bản mới nhất/chiến dịch) → follow-up.
