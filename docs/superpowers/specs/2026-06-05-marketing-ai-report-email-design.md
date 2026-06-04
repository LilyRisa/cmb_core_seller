# AI Marketing Report — async + email + phân tích creative — Design

> Tiếp nối SPEC 2026-06-04 (Đối soát + Dự báo AI). Chuyển generate sang job nền, làm giàu dữ liệu phân tích (hôm nay + quá khứ + nội dung quảng cáo), gửi email khi xong.
> Ngày: 2026-06-05 · Trạng thái: draft (chờ duyệt). ADR: ADR-0017 (Ads axis), ADR-0018 (AI).

## 1. Mục tiêu
Khi người dùng tạo dự báo AI marketing: chạy **bất đồng bộ** (Horizon), phân tích **tất cả chỉ số trong ngày + quá khứ** theo campaign, **đánh giá nội dung quảng cáo/bài post có tối ưu chưa** (đọc creative text + tương tác), rồi **gửi email báo cáo cho mọi Owner/Admin của tenant**.

Ràng buộc: additive — provider AI marketing riêng + cooldown giữ nguyên; `GET /forecast` (đọc cache) không đổi; integration layer không biết tên sàn.

## 2. Quyết định đã chốt (brainstorm 2026-06-05)
- **Async**: generate → job Horizon (queue `marketing-ai`); nút trả `queued`, FE poll cache.
- **Người nhận email**: mọi user có role Owner/Admin của tenant.
- **Nguồn creative**: đọc creative từng quảng cáo (`fetchAdCreatives`), + best-effort bài Page (engagement).
- **Email**: chứa toàn bộ nội dung báo cáo (dự báo + chiến lược + đánh giá creative) + link mở `/marketing`.

## 3. Đọc creative — connector (read axis)
- DTO `AdCreativeDTO`: `adId, adName, effectiveStatus, primaryText (?), headline (?), cta (?), pagePostId (?), raw`.
- Thêm `fetchAdCreatives(string $accessToken, string $externalAccountId): list<AdCreativeDTO>` vào **`AdsConnector`** (cùng nhóm read `listEntities`/`fetchInsights`) + capability `creatives.read`.
- `FacebookAdsConnector`: `GET act_X/ads?fields=id,name,effective_status,creative{body,title,object_story_spec{link_data{message,name,call_to_action{type}}},effective_object_story_id}`. Map:
  - `primaryText` ← `creative.object_story_spec.link_data.message` ?? `creative.body`
  - `headline` ← `link_data.name` ?? `creative.title`
  - `cta` ← `link_data.call_to_action.type`
  - `pagePostId` ← `creative.effective_object_story_id`
  - Lỗi !2xx ⇒ RuntimeException (như các read khác).

## 4. Làm giàu dữ liệu cho AI — `AdsForecastService`
`generate(account, force)` khi thật sự sinh báo cáo gom `$data`:
- `reconciliation`: `AdReconciliationService::reconcile(account, 14)` (đã có) — chuỗi 14 ngày spend/conversations/leads vs đơn thủ công.
- `campaigns_today`: `connector->fetchInsights(token, externalAccountId, 'campaign', ['date_preset'=>'today'])` → per-campaign (spend/impr/clicks/ctr/cpc/cpm/roas/conversations/leads).
- `campaigns_14d`: `fetchInsights(..., ['time_range'=>{since,until}])` 14 ngày → per-campaign tổng hợp.
- `creatives`: `connector->fetchAdCreatives(token, externalAccountId)` (nếu `supports('creatives.read')`).
- `page_posts` (best-effort, bọc try/catch): từ `pagePostId` của creatives suy ra page_id → `listPages` lấy page token → `listPagePosts` → message + like/comment/share. Lỗi ⇒ bỏ qua, không làm hỏng báo cáo.
- `currency`: account currency.

Instruction mở rộng: yêu cầu AI (1) phân tích chỉ số hôm nay vs quá khứ, (2) **đánh giá nội dung từng quảng cáo/bài post có tối ưu chưa** dựa trên text + tương tác + hiệu suất (CTR thấp = nội dung chưa hấp dẫn…), (3) đề xuất cải thiện.

## 5. AI output mở rộng
Giữ `{forecast:{next_7d:{...}}, strategy:[...]}` + thêm:
```
creative_review: [ { ref: "<ad_id|post_id>", name: "<tên>", verdict: "tốt"|"cần cải thiện",
                     issues: [string], suggestions: [string] } ]
```
Stub deterministic (Manual adapter / chưa cấu hình) cũng trả `creative_review` (rỗng hoặc gợi ý generic) ⇒ test 0 quota.

## 6. Job + Email
- **`GenerateAdForecast`** job (queue `marketing-ai`, `ShouldBeUnique` `uniqueId=forecast:{accountId}`, `tries=1`): controller đã gate cooldown nên job luôn `generate(account, force:true)` (sinh mới) → rồi `notify` Owner/Admin. Lỗi AI vẫn trả stub (đã có) ⇒ vẫn lưu + gửi email (báo cáo ước lượng).
- **`MarketingForecastReadyNotification`** (Notification, `ShouldQueue`, queue `notifications`, `via mail`): `toMail` dùng blade `notifications::marketing-forecast-ready` render forecast 7 ngày + strategy + creative_review + nút "Xem chi tiết" → `<app.url>/marketing`. Subject `[<brand>] Báo cáo quảng cáo đã sẵn sàng`.
- **Người nhận**: job query `tenant->users()->wherePivotIn('role', [Role::Owner->value, Role::Admin->value])->get()` → `Notification::send($users, new MarketingForecastReadyNotification($account, $forecast))`. (Chạy trong tenant context của account.)

## 7. Controller (dispatch thay vì chạy inline)
`POST /ad-accounts/{id}/forecast` (`AdForecastController::generate`):
- `$existing = service->cached(account)`; cooldown = `config('marketing.forecast_cooldown_minutes',360)`.
- Còn cooldown & có cache ⇒ trả `{ data: <forecast>, status:'cached', queued:false }` (KHÔNG gọi AI — như cũ).
- Hết cooldown ⇒ `GenerateAdForecast::dispatch(account->id)` → trả `{ data: <existing|null>, status:'generating', queued:true }`.
`GET /forecast` không đổi. FE poll cache tới khi `generated_at` mới.

## 8. Frontend
- `useGenerateForecast` đọc response `{queued,status}`: nếu `queued` → message "Đang tạo báo cáo, sẽ gửi email khi xong" + bắt đầu poll `useAdForecast` (refetchInterval ngắn tới khi `generated_at` đổi, rồi dừng).
- Panel "Dự báo & chiến lược": thêm mục **"Đánh giá nội dung quảng cáo"** render `creative_review` (verdict tag + issues + suggestions). Icons @ant-design/icons.

## 9. Không đụng luồng khác
Marketing additive: connector thêm 1 read method; AI client chỉ mở rộng prompt/schema; cooldown/`GET` giữ nguyên; Notifications module thêm 1 notification + 1 blade (không sửa cái cũ). Messaging/Orders không đụng.

## 10. Testing
- Connector `fetchAdCreatives` (Http::fake): map object_story_spec/effective_object_story_id; throw khi !2xx; `supports('creatives.read')`.
- `AdsForecastService`: dùng Manual AI stub → payload có `creative_review`; gom data gọi đúng fetchInsights(today/14d) + fetchAdCreatives; page_posts best-effort không làm hỏng khi lỗi.
- `GenerateAdForecast` job: `Notification::fake()` assert gửi cho đúng Owner/Admin (không gửi staff); lưu ad_forecasts; idempotent unique.
- `MarketingForecastReadyNotification`: `toMail` render đúng subject + view data (forecast/strategy/creative_review).
- Controller: hết cooldown ⇒ `Queue::assertPushed(GenerateAdForecast)` + `status:generating`; còn cooldown ⇒ trả cache, không dispatch.
- FE: typecheck/lint.

## 11. Build sequence
1. `AdCreativeDTO` + `fetchAdCreatives` (connector, capability) + test.
2. `AdsForecastService` gom data giàu + instruction + stub creative_review + test.
3. `GenerateAdForecast` job + `MarketingForecastReadyNotification` + blade + Owner/Admin recipients + test.
4. `AdForecastController::generate` chuyển sang dispatch + test.
5. FE: generate→poll + panel creative_review.

## 12. Giới hạn / v2
- **`page_posts` engagement (like/comment/share) hoãn sang v2**: creative đã mang text bài post (`primaryText` từ object_story_spec / `effective_object_story_id`), đủ để AI đánh giá nội dung; bổ sung tương tác từng post (qua `listPages`+`listPagePosts`) là follow-up.
- Email gửi snapshot nội dung tại thời điểm tạo; không gửi lại khi trả cache.
- AI vẫn cooldown-guarded (tiết kiệm quota); job chỉ chạy khi hết cooldown.
