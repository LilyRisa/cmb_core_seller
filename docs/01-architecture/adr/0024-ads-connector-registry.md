# ADR-0024: Trục Integration `Ads` (Marketing connector registry)

- **Trạng thái:** Accepted
- **Ngày:** 2026-06-04
- **Liên quan:** ADR-0017 (connector/registry), ADR-0018 (AI provider-agnostic), SPEC 2026-06-04 (Facebook Ads near-real-time + AI)

## Bối cảnh

Cần lấy dữ liệu quảng cáo Facebook (Marketing API: ad account → campaign → adset → ad + insights) near-real-time, AI đánh giá, và áp dụng tối ưu 1-click. Đây là năng lực MỚI, không thuộc 5 trục Integration hiện có (Channels/Carriers/Payments/Messaging/Ai). Câu hỏi: nhét vào Channels/Messaging hay tạo trục mới?

## Quyết định

Thêm **trục Integration thứ 6 — `Ads`** (`app/app/Integrations/Ads/`, namespace `CMBcoreSeller\Integrations\Ads`) theo đúng Connector + Registry pattern (ADR-0017): `Contracts/AdsConnector`, `AdsRegistry`, `FacebookAdsConnector`, DTO chuẩn. Module domain **`Marketing`** sở hữu storage + jobs + HTTP, tiêu thụ `Ads` qua registry và `Ai` (phase sau) cho phần đánh giá.

- **Core/module không bao giờ biết tên provider** — resolve qua `AdsRegistry::for($code)`; bật theo `config('integrations.ads')` (`INTEGRATIONS_ADS` CSV). Thêm Google/TikTok Ads sau = 1 connector + 1 dòng register.
- **Token ads tách riêng** khỏi `channel_accounts` (orders) và page token (messaging): bảng `ad_accounts` (provider `facebook`), scope `ads_read` (+ `ads_management` phase 3), OAuth riêng `/oauth/facebook_ads/callback`. Tái dùng Meta app hiện có (Meta cho 1 app nhiều product/scope).
- **Near-real-time = polling ~15'** (FB insights refresh ~15', không streaming/webhook hiệu suất). Adaptive pacing theo header `x-fb-ads-insights-throttle`; async job cho query nặng.

## Phương án đã cân nhắc

- **A. Trục `Ads` riêng (chọn).** Đúng extensibility-rules, cô lập khỏi messaging/orders, dễ mở rộng provider ads khác.
- B. Nhét vào Channels. ✗ Channels = marketplace orders/inventory/fulfillment; ads khác bản chất, làm phình ChannelConnector.
- C. Nhét vào Messaging (vì cùng Facebook). ✗ Ads ≠ chat; vi phạm ranh giới module + ADR-0017.

## Hệ quả

- **Tích cực:** Marketing module độc lập, test được bằng `Http::fake`; tắt mặc định (`INTEGRATIONS_ADS` rỗng) ⇒ zero tác động hệ hiện có; thêm provider ads mới không đụng core.
- **Đánh đổi / việc theo sau:** cần App Review (`ads_management`) + Standard Marketing API Access Tier + Business Verification cho production đa tenant (xem `docs/04-channels/facebook-ads-setup.md`). AI đánh giá (Phase 2) thêm capability `analysis.generate` vào `AiAssistantConnector`. 1-click apply (Phase 3) cần guardrail + audit (`AdActionLog`).
