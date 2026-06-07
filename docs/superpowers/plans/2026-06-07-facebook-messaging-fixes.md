# Facebook Messaging — Khắc phục 4 vấn đề luồng nhắn tin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sửa 4 vấn đề trong luồng nhắn tin Facebook: (1) biến `{{buyer.name}}` trong mẫu tiện ích không được thay tên, (2) đồng bộ tin nhắn chậm, (3) bình luận không hiện tên khách, (4) nhắn tin riêng từ bình luận không tạo hộp thoại DM ngay.

**Architecture:** Modular monolith Laravel 11 + React SPA. Code messaging ở `app/app/Modules/Messaging/`, connector Facebook ở `app/app/Integrations/Messaging/Facebook/`. Connector map payload → DTO chuẩn, core không biết tên sàn (extensibility-rules.md). Mọi sửa đổi tôn trọng quy tắc module + connector.

**Tech Stack:** PHP 8.2 / Laravel 11, PHPUnit, React 18 + Vite + Ant Design + TanStack Query, Postgres (prod) / SQLite (dev/test), Horizon/Redis, Laravel Reverb (mới, cho realtime).

---

## Bối cảnh điều tra (bằng chứng prod — đã xác minh trực tiếp 2026-06-07)

Truy cập DB prod read-only (`103.77.242.79:5432`) + giải mã page token gọi Graph API thật:

- **#1**: Bảng `utility_templates` **0 rows** (feature mới, migration đề ngày 2026-06-07). Hai hệ thống biến tách biệt: mẫu thường (`message_templates`) dùng `TemplateResolver`/`TemplateContextBuilder` hỗ trợ `{{buyer.name}}`; mẫu tiện ích (`utility_templates`) dùng tham số **vị trí** `{{1}},{{2}}` của Meta, map cứng chỉ 2 nguồn `order_number`+`tracking_url` trong `OrderConfirmationNotifier::resolveTemplate()`, **không** đi qua `TemplateResolver`.
- **#2**: `created_at` của `messages` lưu **giờ local (+7)** còn `sent_at` lưu **UTC** → "lag 7h" là **artifact timezone** (bug thật). `webhook_events` có **22 FB event/tuần**, xử lý **0–2 giây/event, signature_ok**, nhưng **0 event `feed`** (comment) dù mọi page đã subscribe đủ field → **comment chỉ về qua backfill mỗi giờ**. **Không có realtime broadcasting** (`BROADCAST_CONNECTION=log`, không Reverb, FE không có `laravel-echo`) → inbox poll mỗi 10–20s.
- **#3**: 561/561 comment thread prod có `buyer_name` rỗng, `buyer_external_id` rỗng, **0 participants, 0 avatars**. **Test Graph thật trên page active**: query lồng `/feed?fields=comments{from{name}}` và `GET /{comment_id}?fields=from` **đều OMIT `from`** cho comment của khách thường (chỉ comment của page mới có `from`). Case hiếm có tên ("Tu Vu") là **test user/role được thêm vào app** (toàn quyền xem dữ liệu public) — đã được người dùng xác nhận; khách thật KHÔNG có. → **Giới hạn nền tảng Facebook**: không đọc được danh tính người bình luận nếu họ chưa có quan hệ nhận diện, trừ khi app có **Page Public Content Access (qua App Review)**. KHÔNG phải bug code thuần.
- **#4**: 2 comment có `fb_private_psid` → DM thread CÓ tồn tại nhưng tạo **~7 phút sau** (qua webhook echo/backfill), không tức thì. `FacebookCommentController::privateMessage()` gửi Graph OK, bắt được PSID nhưng **chỉ stamp `meta.fb_private_psid` lên comment thread, không tạo Conversation DM**.

**Test baseline (memory):** Không có JS test runner — FE chỉ verify bằng `npm run lint && typecheck && build` + thủ công. BE dùng PHPUnit; FE/BE không xanh toàn cục, có 7 test GHN/fulfillment fail sẵn trên main (không liên quan).

**Lệnh chạy từ `app/`.** Quality gate: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`.

---

## File Structure (tổng quan thay đổi)

**#1 — Mẫu tiện ích buyer.name**
- Modify: `app/app/Modules/Messaging/Services/OrderConfirmationNotifier.php` — thêm `buyer.name`/`buyer.first_name` vào `$data` của `resolveTemplate()`.
- Modify: `app/resources/js/pages/MessagingUtilityTemplatesPage.tsx` — thêm `buyer.name` vào danh sách nguồn biến + chú thích rõ mô hình vị trí `{{1}}/{{2}}`.
- Test: `app/tests/Unit/Messaging/OrderConfirmationNotifierVarsTest.php` (mới).

**#2 — Đồng bộ chậm**
- Timezone (giữ HCM): connector `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php` — chuẩn hoá `sent_at` epoch về `config('app.timezone')` (+7). KHÔNG đổi `APP_TIMEZONE`.
- Realtime Reverb: `app/composer.json` (`laravel/reverb`), `app/config/reverb.php`, `app/config/broadcasting.php`, `app/.env` + `docker-compose.prod.yml` (service `reverb`), `app/package.json` (`laravel-echo`+`pusher-js`), `app/resources/js/lib/echo.ts` (mới), `app/resources/js/lib/messaging.tsx` (subscribe + giữ `refetchInterval` làm fallback).
- Comment webhook + backfill cadence: `app/routes/console.php` — phân tầng nhịp + điều tra nhận `feed` webhook.
- Test: `app/tests/Feature/Messaging/MessageTimezoneTest.php` (mới).

**#3 — Tên khách ở bình luận (ĐÁNH GIÁ SCALE, không sửa chức năng)**
- Doc: `docs/04-channels/facebook-messenger-setup.md` hoặc ADR — đánh giá giới hạn Meta + 7 điểm scale + đề xuất tối ưu.
- (Tuỳ chọn, chờ duyệt) `messaging_account_meta` cờ `can_read_comment_author` + bỏ Graph call lãng phí ở `SyncCommentAvatars`/`fetchCommentThreads`.
- (Tuỳ chọn) `app/resources/js/pages/MessagingPage.tsx` — fallback "Khách Facebook".

**#4 — Nhắn riêng tạo hộp thoại DM**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php` — `sendCommentPrivateMessage`/`sendPrivatePart` trả thêm `message_id` (mid).
- Modify: `app/app/Modules/Messaging/Http/Controllers/FacebookCommentController.php` — sau khi gửi, `ingest` outbound vào DM thread keyed theo PSID + fire `ConversationCreated`.
- Modify: `app/resources/js/lib/messaging.tsx` + `app/resources/js/components/messaging/CommentPrivateMessageModal.tsx` — điều hướng tới DM thread mới sau khi gửi.
- Test: `app/tests/Feature/Messaging/CommentPrivateMessageCreatesDmTest.php` (mới).

---

## QUYẾT ĐỊNH (đã chốt với người dùng 2026-06-07)

1. **#3 — giới hạn Meta, KHÔNG sửa code chức năng.** Code hiện đã tương thích. → Part 3 chỉ **đánh giá scale + đề xuất tối ưu** (Step 2/3 là tuỳ chọn, chờ duyệt). App Review (Page Public Content Access) là việc ngoài code — chỉ ghi vào doc.
2. **Timezone — GIỮ HCM hiển thị.** KHÔNG đổi `APP_TIMEZONE=UTC`. Part 4A chỉ chuẩn hoá `sent_at` FB về +7 cho khớp `created_at`.
3. **#1** — mô hình Meta là **vị trí** `{{1}}/{{2}}`; thêm `buyer.name` làm **nguồn chọn được** cho 1 vị trí (không phải gõ `{{buyer.name}}` tự do — Meta sẽ từ chối). UI nói rõ.

### Còn cần xác nhận
- **#2 Reverb**: triển khai hạ tầng (1 service container + cổng WS + proxy route `/app`)? (bạn đã chọn "làm realtime" — xác nhận OK phần hạ tầng.)
- **#4 + #1**: bắt đầu code ngay (rõ ràng nhất)?

---

## Part 1 — #1: Thêm nguồn biến `buyer.name` cho mẫu tiện ích

**Files:**
- Modify: `app/app/Modules/Messaging/Services/OrderConfirmationNotifier.php:114-133`
- Modify: `app/resources/js/pages/MessagingUtilityTemplatesPage.tsx` (danh sách nguồn biến ~dòng 20-24, help text ~181-186)
- Test: `app/tests/Unit/Messaging/OrderConfirmationNotifierVarsTest.php`

- [ ] **Step 1: Viết test fail** — `OrderConfirmationNotifierVarsTest.php`

```php
<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\OrderConfirmationNotifier;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class OrderConfirmationNotifierVarsTest extends TestCase
{
    public function test_buyer_name_source_resolves_from_conversation(): void
    {
        // resolveTemplate là private — gọi qua reflection, đối tượng giả lập tối thiểu.
        $conv = (object) ['buyer_name' => 'Nguyễn Văn A'];
        $order = (object) ['order_number' => 'DH-100'];
        $template = (object) ['variables' => ['buyer.name', 'order_number'], 'body' => 'Chào {{1}}, đơn {{2}}'];

        $m = new ReflectionMethod(OrderConfirmationNotifier::class, 'resolveDataMap');
        $m->setAccessible(true);
        // resolveDataMap($conv, $order, $url): array<string,string>
        $data = $m->invoke(
            (new \ReflectionClass(OrderConfirmationNotifier::class))->newInstanceWithoutConstructor(),
            $conv, $order, 'https://x/tracking?code=DH-100'
        );

        $this->assertSame('Nguyễn Văn A', $data['buyer.name']);
        $this->assertSame('DH-100', $data['order_number']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run (từ `app/`): `php artisan test --filter=OrderConfirmationNotifierVarsTest`
Expected: FAIL — `resolveDataMap` chưa tồn tại.

- [ ] **Step 3: Tách `$data` thành `resolveDataMap()` + thêm `buyer.name`/`buyer.first_name`**

Sửa `OrderConfirmationNotifier.php` — thay khối `$data` cứng trong `resolveTemplate()` bằng gọi helper mới; helper nhận `$conv`:

```php
private function resolveTemplate(UtilityTemplate $template, Conversation $conv, Order $order, string $url, string $fallbackPreview): array
{
    $data = $this->resolveDataMap($conv, $order, $url);

    $names = array_values((array) ($template->variables ?? []));
    $vars = array_map(fn ($name): string => (string) ($data[$name] ?? ''), $names);

    $preview = (string) $template->body;
    if ($preview === '') {
        $preview = $fallbackPreview;
    }
    foreach ($vars as $i => $value) {
        $preview = str_replace('{{'.($i + 1).'}}', $value, $preview);
    }

    return [$vars, $preview];
}

/**
 * Bảng nguồn dữ liệu điền biến vị trí của mẫu tiện ích.
 * @return array<string,string>
 */
private function resolveDataMap(object $conv, object $order, string $url): array
{
    $buyerName = trim((string) ($conv->buyer_name ?? ''));
    $firstName = $buyerName !== '' ? (string) preg_split('/\s+/', $buyerName)[count(preg_split('/\s+/', $buyerName)) - 1] : '';

    return [
        'order_number' => (string) $order->order_number,
        'tracking_url' => $url,
        'buyer.name' => $buyerName,
        'buyer.first_name' => $firstName,
    ];
}
```

Cập nhật lời gọi trong `send()` (dòng 90): `$this->resolveTemplate($template, $conv, $order, $url, $body)`.

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=OrderConfirmationNotifierVarsTest` → PASS.

- [ ] **Step 5: Cập nhật UI nguồn biến** — `MessagingUtilityTemplatesPage.tsx`

Thêm `{ key: 'buyer.name', label: 'Tên khách' }` và `{ key: 'buyer.first_name', label: 'Tên gọi (chữ cuối)' }` vào danh sách nguồn chọn cho mỗi vị trí biến. Thêm dòng chú thích (icon `<InfoCircleOutlined />`, KHÔNG emoji — theo quy ước UI): "Mẫu tiện ích dùng tham số theo vị trí `{{1}}, {{2}}` của Facebook. Gõ `{{buyer.name}}` trực tiếp trong nội dung sẽ KHÔNG hoạt động — hãy đặt `{{1}}` rồi chọn nguồn 'Tên khách' bên dưới."

- [ ] **Step 6: Verify FE + commit**

Run: `npm run lint && npm run typecheck && npm run build`
```bash
git add app/app/Modules/Messaging/Services/OrderConfirmationNotifier.php app/tests/Unit/Messaging/OrderConfirmationNotifierVarsTest.php app/resources/js/pages/MessagingUtilityTemplatesPage.tsx
git commit -m "feat(messaging): cho phép biến buyer.name trong mẫu tin tiện ích"
```

---

## Part 2 — #4: Nhắn riêng từ bình luận tạo hộp thoại DM ngay

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php` (`sendCommentPrivateMessage` ~1362, `sendPrivatePart` ~1413)
- Modify: `app/app/Modules/Messaging/Http/Controllers/FacebookCommentController.php:183-260`
- Modify: `app/resources/js/lib/messaging.tsx` (`useSendCommentPrivateMessage` ~466-485), `app/resources/js/components/messaging/CommentPrivateMessageModal.tsx`
- Test: `app/tests/Feature/Messaging/CommentPrivateMessageCreatesDmTest.php`

- [ ] **Step 1: Viết test fail** — gửi nhắn riêng phải tạo DM conversation (`thread_type=message`) keyed theo PSID + 1 message outbound.

```php
<?php

namespace Tests\Feature\Messaging;

// Đăng ký FacebookPageConnector vào MessagingRegistry trong test (env chỉ bật tiktok_chat —
// xem memory "Messaging: FB not enabled in test env"); fake connector trả psid + message_id.
// Tạo comment conversation (thread_type=comment), POST private-message, assert:
//   - tồn tại Conversation thread_type=message, external_conversation_id = <psid>
//   - có 1 Message outbound body khớp
//   - response trả về conversation DM (để FE điều hướng)

class CommentPrivateMessageCreatesDmTest extends \Tests\TestCase
{
    public function test_private_message_from_comment_creates_dm_thread(): void
    {
        $this->markTestIncomplete('Điền sau khi chốt cách fake connector — xem ReplyTest hiện có làm mẫu.');
    }
}
```

> Lưu ý: test Feature đầy đủ cần dựng tenant + channel_account + đăng ký connector giả. Tham chiếu test sẵn có cho `FacebookCommentController::reply` (nếu có) hoặc `MessageIngestionService` để tái dùng helper. Đây là bước viết test thật, không để `markTestIncomplete` ở bản cuối.

- [ ] **Step 2: Connector trả `message_id`** — `FacebookPageConnector::sendPrivatePart` đọc `message_id` từ Send API response (ngoài `recipient_id`), `sendCommentPrivateMessage` gộp vào kết quả:

```php
// sendPrivatePart(): sau khi $res successful
$recipientId = $res->json('recipient_id');
$messageId   = $res->json('message_id');
return ['ok' => true, 'psid' => (string) $recipientId, 'message_id' => (string) $messageId];

// sendCommentPrivateMessage(): trả thêm message_ids của các phần đã gửi
return ['psid' => $psid, 'message_id' => $firstMessageId, 'delivered' => $delivered, 'total' => $total];
```

- [ ] **Step 3: Controller ingest outbound vào DM thread** — trong `privateMessage()`, sau khối `if ($result['delivered'] === 0)` và sau khi có `$result['psid']`:

```php
$psidFinal = (string) ($result['psid'] ?? '');
$dmConv = null;
if ($psidFinal !== '') {
    // Tạo/đính DM thread keyed theo PSID (ensureConversation đặt thread_type mặc định = message).
    $ingest = $this->ingestion->ingest($account, new MessageDTO(
        externalConversationId: $psidFinal,
        externalMessageId: ($result['message_id'] ?? '') !== ''
            ? (string) $result['message_id']
            : 'private:'.$commentId.':'.now()->valueOf(), // dùng mid thật để echo webhook dedupe
        buyerExternalId: $psidFinal,
        direction: MessageDirection::Outbound,
        kind: $attachments !== [] ? MessageKind::Image : MessageKind::Text,
        body: $body !== '' ? $body : null,
        attachments: $attachments,
        sentAt: now()->toImmutable(),
        raw: ['type' => 'private_reply', 'comment_id' => $commentId],
    ));
    $dmConv = $ingest['conversation'];
    // Kế thừa tên khách từ comment nếu có (thường comment chưa có tên — xem #3).
    if (blank($dmConv->buyer_name) && filled($conv->buyer_name)) {
        $dmConv->forceFill(['buyer_name' => $conv->buyer_name])->save();
    }
    $this->ingestion->fireEventsForNewMessage($dmConv, $ingest['message'], $ingest['created']);
}
```

Trả về DM conversation để FE điều hướng:

```php
return response()->json([
    'data' => (new ConversationResource($conv))->toArray($request),
    'meta' => [
        'delivered' => $result['delivered'],
        'total' => $result['total'],
        'dm_conversation_id' => $dmConv?->id,
    ],
]);
```

- [ ] **Step 4: Chạy test backend, xác nhận PASS**

Run: `php artisan test --filter=CommentPrivateMessageCreatesDmTest` → PASS.

- [ ] **Step 5: FE điều hướng tới DM thread** — `useSendCommentPrivateMessage` `onSuccess` đọc `meta.dm_conversation_id`, invalidate queries rồi `select`/navigate tới thread đó; `CommentPrivateMessageModal` đóng modal và mở DM.

```ts
onSuccess: (res) => {
    qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
    const dmId = res?.meta?.dm_conversation_id;
    if (dmId) {
        // chuyển người dùng sang hộp thoại DM mới (store Zustand chọn thread).
        useMessagingStore.getState().selectConversation(dmId);
    }
},
```

(Khớp tên hàm store thực tế trong `lib/messaging.tsx` — kiểm tra `selectConversation`/tương đương khi code.)

- [ ] **Step 6: Verify + commit**

Run: `php artisan test --filter=Messaging` ; `npm run lint && npm run typecheck && npm run build`
```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php app/app/Modules/Messaging/Http/Controllers/FacebookCommentController.php app/resources/js/lib/messaging.tsx app/resources/js/components/messaging/CommentPrivateMessageModal.tsx app/tests/Feature/Messaging/CommentPrivateMessageCreatesDmTest.php
git commit -m "fix(messaging): nhắn riêng từ bình luận tạo hộp thoại DM ngay (FB)"
```

---

## Part 3 — #3: Tên khách ở bình luận → ĐÁNH GIÁ SCALE (không sửa chức năng)

> **Quyết định người dùng (2026-06-07):** Đây là giới hạn nền tảng Meta, code hiện tại **đã tương thích** (request `from{name}`, có thì lưu, không có thì fallback êm, không crash). → **KHÔNG sửa code để lấy tên.** Thay vào đó **đánh giá phương pháp triển khai có tối ưu khi mở rộng quy mô** và đề xuất tối ưu (người dùng tự chọn áp dụng).

### Đánh giá hiện trạng (bằng chứng prod)

Kiến trúc sync FB hiện tại:
- `messaging:reconcile-sync` chạy **hằng giờ** (`routes/console.php:122`) → lặp **TẤT CẢ** channel account active+messaging_enabled, mỗi account dispatch `BackfillMessagingChannel` + `BackfillFacebookComments` (`ReconcileMessagingSync.php:29-44`). Hiện ~36 page FB → ~72 job backfill/giờ.
- `fetchCommentThreads` request `from{id,name,picture}` cho mọi comment, và `SyncCommentAvatars` gọi Graph **1 lần/comment mới** (webhook path) để lấy avatar+tên.
- Queue backfill chạy ở `supervisor-messaging-bg` (`horizon.php:281-300`), **dùng chung** với `messaging` (flows/auto-reply — latency-sensitive), marketing-sync/ai/publish. Prod maxProcesses 8.

### Vấn đề ở quy mô lớn (xếp theo mức độ)

1. **Tên người bình luận — KHÔNG cần làm gì (app đang App Review).** `from` của khách thật hiện bị Meta ẩn, nhưng app **đang trong quá trình xét duyệt** Page Public Content Access. Khi được duyệt, code hiện tại (`fetchCommentThreads`/`SyncCommentAvatars` đã request `from{name}` và lưu khi có) sẽ **tự động điền tên** — không cần đổi gì. Quyết định người dùng (2026-06-07): các call này **không tốn quota đáng kể**, **giữ nguyên**, KHÔNG thêm capability gate/chặn.

2. **reconcile-sync quét toàn bộ account bất kể hoạt động (CAO).** Ở 100s–1000s page, hằng giờ sinh hàng trăm job backfill, đa số fetch rỗng (page idle). Mỗi job paginate Graph → tải queue + rate-limit.
   - **Tối ưu:** phân tầng nhịp — page có hoạt động gần đây (≤7 ngày) backfill thường xuyên; page idle backfill thưa (vài giờ/ngày). Dùng `last_synced_at`/`last_message_at` để lọc. Webhook là chính (đã ~2s khi event về); backfill chỉ vá khoảng trống.

3. **Comment webhook (`feed`) không về → toàn bộ comment phụ thuộc backfill (CAO — đòn bẩy lớn nhất).** Nếu sửa được nhận `feed` webhook, comment thành realtime + **xoá bỏ hoàn toàn gánh backfill comment hằng giờ**. (Trùng Part 4C — điều tra `feed` delivery.)

4. **Job enrichment 1 Graph call/đơn vị = N+1 với Graph (TRUNG BÌNH).** `SyncConversationProfile` đã throttle 24h (tốt). `SyncCommentAvatars` **không throttle, theo từng comment** — kẻ phạm chính (cộng hưởng vấn đề #1).

5. **Backfill dùng chung supervisor với flows/auto-reply (TRUNG BÌNH).** Backfill ồ ạt có thể bỏ đói `messaging` queue (listener flow/auto-reply latency-sensitive).
   - **Tối ưu:** tách backfill sang queue/supervisor riêng có giới hạn, giữ `messaging` luôn nhạy.

6. **Rate limit Graph (TRUNG BÌNH).** Backfill đồng loạt nhiều page dễ chạm BUC throttling → rơi vào nhánh `FACEBOOK_RATE_LIMIT` release(120) (`BackfillFacebookComments.php:129`). Cần jitter/pace dispatch theo ngân sách rate-limit mỗi page.

7. **`->each()` nạp toàn bộ account vào RAM (THẤP).** Ổn ở 36; ở 1000s nên `chunkById`. `ShouldBeUnique(900s)` đã chống chồng lấn (tốt).

### Deliverable Part 3
- [ ] **Step 1:** Viết doc đánh giá vào `docs/04-channels/facebook-messenger-setup.md` (hoặc ADR `docs/01-architecture/adr/`): tên comment sẽ tự điền khi App Review duyệt xong (không cần code); + các điểm scale 2–7 trên. KHÔNG đổi code chức năng, KHÔNG thêm capability gate, KHÔNG đổi fallback hiển thị.

> Quyết định người dùng (2026-06-07): bỏ luôn việc đổi nhãn "Khách Facebook" — #3 **không có thay đổi code nào**, chỉ là báo cáo đánh giá (tuỳ chọn).

---

## Part 4 — #2: Đồng bộ tin nhắn nhanh hơn

> 3 nhánh độc lập: (4A) sửa bug timezone, (4B) realtime Reverb, (4C) tăng tần suất backfill comment. Commit riêng từng nhánh.

### 4A — Sửa nhất quán timezone `sent_at` (GIỮ hiển thị HCM)

> **Quyết định người dùng (2026-06-07):** Timezone **phải giữ HCM (Asia/Ho_Chi_Minh)** hiển thị trên hệ thống. → KHÔNG đổi `APP_TIMEZONE=UTC`. `created_at` đã đúng +7 (Eloquent `freshTimestamp()` theo APP_TIMEZONE). Vấn đề là `sent_at` được set từ Carbon **UTC** (epoch FB / `now()->toImmutable()` parse) rồi lưu nguyên wall-clock UTC → khi đọc lại Eloquent diễn giải theo +7 ⇒ **hiển thị sớm 7h** và lệch so với `created_at`. Sửa: đảm bảo `sent_at` cũng ở **+7** trước khi lưu, nhất quán với `created_at`.

**Files:** điều tra `app/app/Modules/Messaging/Models/Message.php` (`$casts['sent_at']`), các điểm set `sent_at` (`MessageIngestionService`, connector parse epoch FB); Test: `app/tests/Feature/Messaging/MessageTimezoneTest.php`

- [ ] **Step 1**: Xác định chính xác chỗ `sent_at` thành UTC — trong connector `FacebookPageConnector` (parse `timestamp` epoch ms của webhook/Graph) và `now()->toImmutable()` (đã là +7 nếu APP_TIMEZONE=+7, nên điểm lỗi là **epoch parse**). Epoch parse mặc định ra UTC → cần `->setTimezone(config('app.timezone'))` trước khi đưa vào DTO `sentAt`.
- [ ] **Step 2**: Viết test: ingest 1 tin với `sentAt` từ epoch FB cụ thể → đọc lại `message->sent_at` phải khớp giờ HCM tương ứng (và lệch với `created_at` < 5 phút cho tin realtime).

```php
public function test_sent_at_stored_in_hcm_consistent_with_created_at(): void
{
    // epoch ms ~ "now" → ingest qua MessageIngestionService → sent_at đọc lại phải ở +7,
    // |created_at - sent_at| < 5 phút (KHÔNG còn lệch ~7h).
    $this->markTestIncomplete('Điền: dựng channel_account + ingest MessageDTO(sentAt epoch) + assert tz.');
}
```

- [ ] **Step 3**: Sửa connector: chuẩn hoá Carbon `sent_at` về `config('app.timezone')` ngay khi parse epoch (1 helper dùng chung trong connector). KHÔNG đổi `APP_TIMEZONE`.
- [ ] **Step 4**: Chạy `php artisan test` toàn bộ — đảm bảo không vỡ window 24h / dashboard range / so sánh thời gian khác.
- [ ] **Step 5**: (Tuỳ chọn) Backfill dữ liệu cũ: script 1 lần dịch `sent_at` của tin FB cũ +7h cho khớp. **Cần xác nhận** trước khi chạy trên prod (đụng dữ liệu thật).
- [ ] **Step 6**: Commit `fix(messaging): sent_at FB lưu theo giờ HCM, hết lệch 7h với created_at`.

### 4B — Realtime Reverb (đẩy tin tức thời thay vì poll 10-20s)

**Files:** `app/composer.json`, `app/config/reverb.php`, `app/config/broadcasting.php`, `app/.env`, `docker-compose.prod.yml`, `app/package.json`, `app/resources/js/lib/echo.ts`, `app/resources/js/lib/messaging.tsx`

- [ ] **Step 1**: `composer require laravel/reverb` (từ `app/`); `php artisan reverb:install`. Đặt `BROADCAST_CONNECTION=reverb` (dev `.env`).
- [ ] **Step 2**: Xác nhận event đã `ShouldBroadcast` + broadcast trên `tenant.{id}.messaging` (đã có: `MessageReceived`, `ConversationCreated`, `BroadcastsOnTenantChannel`). Định nghĩa private channel authz trong `routes/channels.php` (kiểm tra tenant của user).
- [ ] **Step 3**: FE — `npm i laravel-echo pusher-js`; tạo `lib/echo.ts` khởi tạo Echo (reverb), `lib/messaging.tsx` subscribe `tenant.{id}.messaging` → invalidate/patch TanStack Query cache khi có `MessageReceived`/`ConversationCreated`.
- [ ] **Step 4**: Giảm `refetchInterval` làm lưới an toàn: thread 10s→ giữ 10s, list 15s→ giữ; KHÔNG cần siết mạnh vì realtime đã đẩy (chỉ giữ poll làm fallback). (Quyết định: realtime là chính, poll là backup.)
- [ ] **Step 5**: `docker-compose.prod.yml` — thêm service `reverb` (`php artisan reverb:start`), cổng WS, biến `REVERB_*`; cấu hình proxy ngoài route `/app` (websocket) → container reverb. Cập nhật `docs/07-infra/`.
- [ ] **Step 6**: Verify dev: mở 2 tab, gửi tin, thấy hiện tức thời không cần đợi poll. Commit `feat(messaging): realtime inbox qua Laravel Reverb (tenant channel)`.

### 4C — Comment & backfill cadence

**Files:** `app/routes/console.php` (lịch `messaging:reconcile-sync` ~dòng 122), điều tra subscription `feed`.

- [ ] **Step 1**: Điều tra vì sao `feed` webhook (comment) không về dù subscribe (`webhook_events` 0 feed event). Kiểm: Meta App → Webhooks → page subscription field `feed`; verify endpoint xử lý `feed` change (`mapFeedChange`); thử comment thật → xem có POST tới `/webhook/...` không (log). Có thể app cần **Live mode** hoặc field `feed` chưa thực sự bật ở cấp app.
- [ ] **Step 2**: Tăng tần suất backfill comment: tách lịch riêng cho comment xuống `everyTenMinutes` (thay vì hourly) cho page active, vẫn `withoutOverlapping()`/`onOneServer()`. Cân nhắc rate limit Graph.
- [ ] **Step 3**: Verify + commit `perf(messaging): tăng tần suất đồng bộ comment + sửa nhận feed webhook`.

---

## Self-Review (đã chạy)

1. **Spec coverage**: #1→Part1, #2→Part4 (4A/4B/4C), #3→Part3, #4→Part2. Đủ 4.
2. **Placeholder scan**: 3 test còn `markTestIncomplete` (Part2 Step1, Part3 Step1) — **phải điền code test thật khi thực thi** (cần dựng tenant/connector giả; tham chiếu test reply hiện có). Part4A Step1 và 4C Step1 là **điều tra**, không phải code mù.
3. **Type consistency**: `ingest()` trả `['conversation','message','created']` (đã xác minh); `MessageDTO` fields (externalConversationId, externalMessageId, buyerExternalId, direction, kind, body, attachments, sentAt, raw) khớp `MessageIngestionService`/`reply()`; `sendCommentPrivateMessage` trả `['psid','message_id','delivered','total']` (thêm message_id ở Part2 Step2); `resolveTemplate` đổi chữ ký thêm `$conv` — cập nhật lời gọi ở `send()`.

**Điểm cần bạn chốt trước khi thực thi**: 3 quyết định ở mục "QUYẾT ĐỊNH CẦN XÁC NHẬN" + quyết định kiến trúc timezone (Part4A Step1: đổi `APP_TIMEZONE=UTC` toàn cục hay không).
