# Báo cáo chuyển đổi (Purchase) từ đơn tạo trong chat Facebook về Meta Ads — Design

> Khi NV tạo đơn ngay trong khung chat Messenger với khách đến từ quảng cáo Click-to-Messenger (CTM),
> gửi sự kiện `Purchase` về Meta qua **Conversions API for Business Messaging** để Meta gắn kết quả bán hàng
> với đúng quảng cáo, tránh trộn lẫn với hội thoại tự nhiên (organic).
> Ngày: 2026-07-14 · Trạng thái: approved. Làm trên `main`.

## 1. Bối cảnh / vì sao khả thi

Facebook có sẵn cơ chế chính chủ cho đúng nhu cầu này (đã tra developers.facebook.com):
- Webhook `message.referral` khi khách bấm quảng cáo CTM trả `source=ADS`, `ad_id`, `ads_context_data` — hệ thống
  **đã capture sẵn** vào `conversation.meta['ad_referral']` (first-touch, xem `FacebookPageConnector::adReferralMeta()`
  + `MessageIngestionService::stampAdReferral()`).
- Để báo "đơn mua" về đúng quảng cáo đó, kênh Messenger **không cần `ctwa_clid`** (chỉ WhatsApp cần) — chỉ cần định
  danh bằng `page_id` + `page_scoped_user_id` (PSID). Meta tự khớp PSID này với lượt click quảng cáo phía họ.
- Yêu cầu: 1 **dataset** gắn theo Page (Dataset API) + access token có quyền **`page_events`** (Advanced Access,
  cần Meta App Review — xem §7).

Điểm móc nối: `ConversationController::linkOrder()` (`app/app/Modules/Messaging/Http/Controllers/ConversationController.php:317`)
nhận cờ `notify_customer=true` **chỉ khi** đơn vừa được tạo TRONG khung chat (không phải link đơn cũ) — đúng điều
kiện "tạo đơn từ chat", kết hợp `conversation.meta['ad_referral']` để loại tin nhắn tự nhiên.

## 2. Contract mới (connector capability)

`Integrations\Messaging\Contracts\ConversionReportingConnector` (mới, theo đúng pattern Interface Segregation của
`UtilityTemplateConnector` — chỉ Facebook Page hỗ trợ, không ép Zalo/TikTok/Lazada/Shopee implement):

```php
interface ConversionReportingConnector
{
    /** Tạo (nếu chưa có) dataset gắn theo Page, trả dataset_id. Ném RuntimeException nếu thiếu quyền page_events. */
    public function ensureDataset(MessagingAuthContext $auth): string;

    /** Gửi 1 event Purchase. $eventId dùng để Meta/log dedupe (vd "order-{id}"). */
    public function reportPurchase(
        MessagingAuthContext $auth,
        string $datasetId,
        string $psid,
        int $valueVnd,
        \DateTimeInterface $eventTime,
        string $eventId,
    ): void;
}
```

`FacebookPageConnector implements ConversionReportingConnector`, khai báo capability `conversion.report` trong map
capability hiện có. Golden rule: mọi nơi gọi phải qua `instanceof ConversionReportingConnector && supports('conversion.report')`,
không `instanceof FacebookPageConnector`.

`reportPurchase` build payload theo tài liệu Facebook:
```json
{"event_name":"Purchase","event_time":<unix>,"action_source":"business_messaging","messaging_channel":"messenger",
 "user_data":{"page_id":"<PAGE_ID>","page_scoped_user_id":"<PSID>"},
 "custom_data":{"currency":"VND","value":<order.total>},"event_id":"order-<id>"}
```
POST `graph.facebook.com/{version}/{dataset_id}/events`. Lỗi permission (missing `page_events`) map thành exception
riêng (vd `MissingScopeException`) để job phân biệt được với lỗi tạm thời.

## 3. Data — không cần migration

Cả hai chỗ lưu trạng thái đều dùng cột JSON đã có sẵn, không thêm bảng/cột:

- `messaging_account_meta.settings['fb_conversions']`: `{enabled: bool, dataset_id: ?string, last_error: ?string, last_error_at: ?string}`.
  (Sửa lại trong lúc triển khai: bản thiết kế ban đầu ghi `channel_accounts.meta['fb_conversions']`, nhưng
  `messaging_account_meta.settings` — bảng cấu hình 1:1-theo-page đã có, cột `encrypted:array` — phù hợp hơn cho
  loại cấu hình bán-nhạy-cảm này, và đã được dùng đúng cho mục đích này xuyên suốt các task đã triển khai.)
- `orders.meta['fb_conversion_reported_at']`: dấu mốc ISO-8601, dùng để chống gửi trùng (idempotent theo đúng bất
  biến "mọi sync job phải idempotent" của dự án).

## 4. Backend flow

**Bật/tắt (settings kênh Facebook Page):** endpoint mới trên `MessagingChannelController` (theo cùng route nhóm
settings kênh hiện có), `PATCH .../fb-conversions {enabled: bool}`.
- Bật lần đầu (`dataset_id` null) → gọi `ensureDataset()` **đồng bộ ngay trong request** để phát hiện thiếu quyền
  sớm (không đợi tới đơn đầu tiên). Thiếu quyền `page_events` → trả lỗi rõ, kèm cờ `needs_reauth: true`.
- Tắt → chỉ set `enabled=false`, giữ nguyên `dataset_id` (bật lại không cần tạo dataset mới).

**Trigger:** trong `linkOrder()`, ngay cạnh khối gọi `OrderConfirmationNotifier` hiện có, khi `notify_customer===true`:
```php
ReportOrderConversionToMeta::dispatch($conv->id, $order->id);
```
Best-effort, không chặn response (giống notifier bên cạnh).

**Job `Modules\Messaging\Jobs\ReportOrderConversionToMeta`** (queued, `ShouldBeUnique` theo `order_id` để chống
double-dispatch):
1. Load lại conversation + order (trong tenant). Guard: `provider==='facebook_page'`, `meta['ad_referral']` không
   rỗng, `order.meta['fb_conversion_reported_at']` chưa có → nếu fail guard nào, kết thúc êm (không lỗi, không log ồn).
2. Load channel account, guard `meta['fb_conversions']['enabled']===true`.
3. Resolve connector qua registry Messaging hiện có; guard `instanceof ConversionReportingConnector &&
   supports('conversion.report')`.
4. `dataset_id = meta['fb_conversions']['dataset_id'] ?? $connector->ensureDataset($auth)` (fallback hiếm khi race
   với bước bật toggle), persist lại nếu vừa tạo mới.
5. `reportPurchase($auth, $datasetId, $conv->buyer_external_id, $order->total, $order->created_at, "order-{$order->id}")`.
6. Thành công → `order->update(['meta' => [...($order->meta ?? []), 'fb_conversion_reported_at' => now()->toIso8601String()]])`.
7. `MissingScopeException` → set `messaging_account_meta.settings['fb_conversions']['last_error']='missing_scope'` +
   timestamp, **không** để queue tự retry (không ích gì khi thiếu quyền) — dừng job.
8. Lỗi khác (mạng, 5xx tạm thời) → để cơ chế retry mặc định của queue xử lý, hết lượt thì log rồi thôi — không bao
   giờ được phép làm hỏng luồng tạo/link đơn phía trước.

Giá trị gửi: **`order.total`** (tổng tiền khách trả, gồm ship) — đúng số tiền thực chi. Thời điểm gửi: **ngay khi
tạo đơn từ chat**, kể cả COD chưa thu tiền (đặt đơn = purchase theo chuẩn Meta CAPI cho business messaging).

## 5. Frontend

1 switch trong màn cài đặt kênh Facebook Page hiện có: "Gửi dữ liệu chuyển đổi (mua hàng) về Facebook Ads", kèm
dòng phụ giải thích chỉ áp dụng cho hội thoại đến từ quảng cáo Click-to-Messenger. Khi bật lỗi thiếu quyền → Alert
+ nút "Cấp quyền lại" tái dùng đúng component reauth đã có cho kênh revoked. Không thêm dashboard/thống kê riêng
(YAGNI cho bản đầu).

## 6. Testing

- Unit: `FacebookPageConnector::reportPurchase` — payload đúng field (`event_name`, `action_source`,
  `messaging_channel`, `user_data`, `custom_data`) qua `Http::fake`; lỗi permission ném đúng exception type.
- Feature: `linkOrder` với `notify_customer=true` + có `ad_referral` + toggle bật → job được dispatch
  (`Queue::fake` assert pushed với đúng conv/order id); thiếu 1 trong 3 điều kiện → không dispatch.
- Job: chạy thành công → đánh dấu `order.meta`; chạy lần 2 (đã đánh dấu) → không gọi connector nữa (idempotent);
  lỗi thiếu quyền → set `messaging_account_meta.settings['fb_conversions'].last_error`, không throw ra ngoài job.

## 7. Giới hạn / phụ thuộc ngoài code

- Cần thêm scope `page_events` vào OAuth (`FacebookPageConnector.php:113`) — đây là **Advanced Access permission**,
  phải nộp **Meta App Review** cho app (việc làm 1 lần, ngoài phạm vi code, do phía vận hành thực hiện). Trước khi
  được duyệt, tính năng build xong nhưng bật lên sẽ luôn báo thiếu quyền — giống các tính năng "INERT tới khi set
  env/duyệt ngoài" trước đây trong dự án.
- Trang kênh đã kết nối TRƯỚC khi thêm scope này phải "Cấp quyền lại" (reauth) mới có `page_events` — không tự
  động có được từ token cũ.
- Chỉ scope Facebook Messenger (kênh `facebook_page`). WhatsApp/Instagram Direct — ngoài phạm vi, để ngỏ vì
  interface đã tách capability, thêm connector khác sau này không đụng core.
