# Plan B — 4 nguồn lỗi hệ thống còn lại (vận đơn/tem, thanh toán, subscription, AI) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm 5 nguồn cảnh báo mới cho tab "Hệ thống" (đã dựng ở Plan A): tạo vận đơn/lấy tem thất bại, thanh toán thất bại, subscription hết hạn, AI provider lỗi liên tiếp, AI hết hạn mức — mỗi nguồn là 1 domain event mới bắn từ đúng 1 điểm trong service hiện có + 1 listener mới trong module `Notifications`.

**Architecture:** Theo đúng pattern đã có (`ChannelAccountNeedsReconnect` → `NotifyOnChannelReconnect`, `StockPushed` → `NotifyOnStockPushFailed` ở Plan A): mỗi module nguồn (`Fulfillment`, `Billing`, `Messaging`) chỉ thêm 1 lệnh `event(new X(...))` tại điểm lỗi đã xác định — KHÔNG đổi luồng xử lý nghiệp vụ hiện có. Module `Notifications` nghe qua `Event::listen` trong `NotificationsServiceProvider`, dùng `NotificationDispatcher::dispatch()` (đã tự gán `category` từ Plan A). **Phụ thuộc Plan A đã merge xong** (cần `NotificationType::categoryFor()`, cột `category`). Đây là **Plan B trong loạt 3 plan độc lập** (spec: `docs/superpowers/specs/2026-07-23-notification-tabs-and-general-pages-design.md`) — Plan C (tab "Chung") không phụ thuộc Plan B, có thể làm song song.

**Tech Stack:** Laravel 11 (PHP 8.3+, PHPUnit, `Illuminate\Support\Facades\Cache` cho đếm lỗi liên tiếp), không có phần FE (Plan A đã dựng đủ UI 3 tab).

## Global Constraints

- Chạy mọi lệnh PHP từ `app/`.
- PSR-4 `CMBcoreSeller\` map vào `app/app/`.
- Module chỉ giao tiếp qua `Contracts/` hoặc domain event — KHÔNG `use` Services nội bộ module khác (`docs/01-architecture/modules.md:63` xác nhận event là kênh giao tiếp hợp lệ).
- Mỗi event mới chỉ bắn tại ĐÚNG 1 điểm đã xác định trong service hiện có — không đổi hành vi nghiệp vụ/retry sẵn có, chỉ thêm dòng `event(...)`.
- Test private method dùng `ReflectionMethod` + `setAccessible(true)` (pattern có sẵn trong `tests/Feature/Fulfillment/ShipmentBuildPayloadDeliveryOptionsTest.php`).
- Không viết JS test mới — Plan B không đổi FE.

---

## Task 1: Vận đơn/tem thất bại — `ShipmentIssueDetected`

**Files:**
- Create: `app/app/Modules/Fulfillment/Events/ShipmentIssueDetected.php`
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php:327-347` (`markLabelUnavailable`)
- Modify: `app/app/Modules/Fulfillment/Jobs/FetchChannelLabel.php:89-107`
- Create: `app/app/Modules/Notifications/Listeners/NotifyOnShipmentIssue.php`
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Fulfillment/ShipmentIssueDetectedEventTest.php`
- Test: `app/tests/Feature/Notifications/NotificationListenersTest.php` (thêm)

**Interfaces:**
- Produces: `ShipmentIssueDetected(tenantId, orderId, orderNumber, shipmentId, reason)`. Bắn tại 2 điểm: (a) `ShipmentService::markLabelUnavailable()` khi lỗi TERMINAL không phải `shopee_advance_fulfilment`; (b) `FetchChannelLabel::markAsyncFetchExhausted()` (method mới tách ra) khi async retry cạn hết 10 lượt (~50').

- [ ] **Step 1: Viết test trước cho cả 2 điểm bắn event**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Channels\Exceptions\ShippingDocumentUnavailable;
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentIssueDetected;
use CMBcoreSeller\Modules\Fulfillment\Jobs\FetchChannelLabel;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ReflectionMethod;
use Tests\TestCase;

class ShipmentIssueDetectedEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderAndShipment(Tenant $tenant): array
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'source' => 'lazada',
            'external_order_id' => 'LZ_1', 'order_number' => 'LZ_1',
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'PROCESSED',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subHour(), 'source_updated_at' => now()->subHour(),
            'tags' => [], 'packages' => [],
        ]);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'Lazada 3PL',
            'tracking_no' => null, 'package_no' => 'PKG_1', 'status' => Shipment::STATUS_CREATED,
            'cod_amount' => 0, 'label_path' => null, 'label_fetch_next_retry_at' => now()->addSeconds(15), 'raw' => [],
        ]);

        return [$order, $shipment];
    }

    public function test_terminal_label_unavailable_fires_event_for_real_issue(): void
    {
        Event::fake([ShipmentIssueDetected::class]);
        $tenant = Tenant::create(['name' => 'ShipIssueShop1']);
        [$order, $shipment] = $this->makeOrderAndShipment($tenant);

        $method = new ReflectionMethod(ShipmentService::class, 'markLabelUnavailable');
        $method->setAccessible(true);
        $method->invoke(
            app(ShipmentService::class), $order, $shipment, 'lazada',
            ShippingDocumentUnavailable::terminal('lazada_dbs_sof', 'DBS/SOF — ĐVVC ngoài Lazada')
        );

        Event::assertDispatched(ShipmentIssueDetected::class, fn ($e) => $e->shipmentId === (int) $shipment->getKey()
            && $e->orderId === (int) $order->getKey() && $e->tenantId === (int) $tenant->getKey());
    }

    public function test_terminal_label_unavailable_does_not_fire_for_info_only_reason(): void
    {
        Event::fake([ShipmentIssueDetected::class]);
        $tenant = Tenant::create(['name' => 'ShipIssueShop2']);
        [$order, $shipment] = $this->makeOrderAndShipment($tenant);

        $method = new ReflectionMethod(ShipmentService::class, 'markLabelUnavailable');
        $method->setAccessible(true);
        $method->invoke(
            app(ShipmentService::class), $order, $shipment, 'shopee',
            ShippingDocumentUnavailable::terminal('shopee_advance_fulfilment', 'SPX tự xử lý')
        );

        Event::assertNotDispatched(ShipmentIssueDetected::class);
    }

    public function test_async_fetch_exhausted_fires_event(): void
    {
        Event::fake([ShipmentIssueDetected::class]);
        $tenant = Tenant::create(['name' => 'ShipIssueShop3']);
        [$order, $shipment] = $this->makeOrderAndShipment($tenant);

        $job = new FetchChannelLabel((int) $shipment->getKey());
        $method = new ReflectionMethod(FetchChannelLabel::class, 'markAsyncFetchExhausted');
        $method->setAccessible(true);
        $method->invoke($job, $order, $shipment, 10);

        Event::assertDispatched(ShipmentIssueDetected::class, fn ($e) => $e->shipmentId === (int) $shipment->getKey());
        $this->assertNull($shipment->fresh()->label_fetch_next_retry_at);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/ShipmentIssueDetectedEventTest.php`
Expected: FAIL — class `ShipmentIssueDetected` và method `markAsyncFetchExhausted` chưa tồn tại.

- [ ] **Step 3: Tạo event `ShipmentIssueDetected`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Plan B (2026-07-23) — tạo/lấy tem vận đơn thất bại đến mức seller cần biết (terminal hoặc
 * async retry cạn). `Notifications` module lắng nghe để báo tab "Hệ thống".
 */
class ShipmentIssueDetected
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $orderId,
        public readonly ?string $orderNumber,
        public readonly int $shipmentId,
        public readonly string $reason,
    ) {}
}
```

- [ ] **Step 4: Sửa `ShipmentService::markLabelUnavailable()`**

Thêm import ở đầu `app/app/Modules/Fulfillment/Services/ShipmentService.php` (cùng khối `use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentCreated;`):

```php
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentIssueDetected;
```

Sửa cuối method `markLabelUnavailable()` (dòng 344-347 hiện tại) — thêm khối `if (! $infoOnly)` ngay sau `Log::warning`:

```php
        Log::warning('shipment.channel_label_unavailable', [
            'shipment' => $shipment->getKey(), 'provider' => $provider, 'reason_code' => $e->reasonCode,
        ]);
        if (! $infoOnly) {
            // Plan B (2026-07-23) — chỉ báo tab "Hệ thống" cho lỗi THẬT cần seller xử lý, không báo case
            // thông tin (shopee_advance_fulfilment) — FE đã hiện warning vàng riêng cho case đó.
            event(new ShipmentIssueDetected(
                tenantId: (int) $order->tenant_id,
                orderId: (int) $order->getKey(),
                orderNumber: $order->order_number,
                shipmentId: (int) $shipment->getKey(),
                reason: (string) $order->issue_reason,
            ));
        }
    }
```

- [ ] **Step 5: Sửa `FetchChannelLabel.php` — tách `markAsyncFetchExhausted()` + bắn event**

Thêm import ở đầu file:

```php
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentIssueDetected;
```

Sửa khối exhausted trong `handle()` (dòng 103-104 hiện tại) — thay:

```php
            $shipment->forceFill(['label_fetch_next_retry_at' => null])->save();
            Log::warning('shipment.fetch_channel_label_async_exhausted', ['shipment' => $shipment->getKey(), 'attempts' => $attempt]);

            return;
```

bằng:

```php
            $this->markAsyncFetchExhausted($order, $shipment, $attempt);

            return;
```

Thêm method mới vào cuối class, trước method `failed()`:

```php
    /**
     * Plan B (2026-07-23) — sync + async retry đều cạn (~50' tổng), tem vẫn không lấy được ⇒ báo tab
     * "Hệ thống" cho seller. Tách riêng để test được không phụ thuộc cơ chế `attempts()` của queue thật.
     */
    private function markAsyncFetchExhausted(Order $order, Shipment $shipment, int $attempts): void
    {
        $shipment->forceFill(['label_fetch_next_retry_at' => null])->save();
        Log::warning('shipment.fetch_channel_label_async_exhausted', ['shipment' => $shipment->getKey(), 'attempts' => $attempts]);
        event(new ShipmentIssueDetected(
            tenantId: (int) $order->tenant_id,
            orderId: (int) $order->getKey(),
            orderNumber: $order->order_number,
            shipmentId: (int) $shipment->getKey(),
            reason: 'Không lấy được tem/vận đơn từ sàn sau nhiều lần thử lại — bấm "Nhận phiếu giao hàng" để thử lại thủ công.',
        ));
    }
```

- [ ] **Step 6: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/ShipmentIssueDetectedEventTest.php`
Expected: PASS (3 tests)

- [ ] **Step 7: Thêm `NotificationType::FULFILLMENT_SHIPMENT_ISSUE`**

Trong `app/app/Modules/Notifications/Support/NotificationType.php`, thêm hằng số (sau `INVENTORY_STOCK_PUSH_FAILED`):

```php
    /** Tạo vận đơn/lấy tem thất bại đến mức seller cần xử lý (Plan B, 2026-07-23). */
    public const FULFILLMENT_SHIPMENT_ISSUE = 'fulfillment.shipment_issue';
```

Thêm dòng vào `CATEGORY_MAP`:

```php
        self::FULFILLMENT_SHIPMENT_ISSUE => self::CATEGORY_SYSTEM,
```

- [ ] **Step 8: Viết listener `NotifyOnShipmentIssue`**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentIssueDetected;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Plan B (2026-07-23) — tạo vận đơn/lấy tem thất bại ⇒ thông báo in-app tab "Hệ thống".
 * Dedup theo shipment id.
 */
class NotifyOnShipmentIssue implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(ShipmentIssueDetected $event): void
    {
        $label = $event->orderNumber ?: ('#'.$event->orderId);

        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::FULFILLMENT_SHIPMENT_ISSUE,
            'level' => 'warning',
            'title' => "Đơn {$label} gặp lỗi lấy vận đơn/tem",
            'body' => $event->reason,
            'action_url' => '/orders/'.$event->orderId,
            'data' => [
                'order_id' => $event->orderId,
                'shipment_id' => $event->shipmentId,
            ],
            'dedup_key' => 'fulfillment.shipment_issue:'.$event->shipmentId,
        ]);
    }
}
```

- [ ] **Step 9: Đăng ký listener trong provider**

Trong `app/app/Modules/Notifications/NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentIssueDetected;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnShipmentIssue;
```

Thêm dòng đăng ký sau `Event::listen(StockPushed::class, NotifyOnStockPushFailed::class);` (thêm ở Plan A):

```php
        Event::listen(ShipmentIssueDetected::class, NotifyOnShipmentIssue::class);
```

- [ ] **Step 10: Thêm test listener vào `NotificationListenersTest.php`**

Thêm import + test method mới (cuối class, trước `}`):

```php
use CMBcoreSeller\Modules\Fulfillment\Events\ShipmentIssueDetected;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnShipmentIssue;
```

```php
    public function test_shipment_issue_creates_system_notification(): void
    {
        (new NotifyOnShipmentIssue(app(NotificationDispatcher::class)))->handle(new ShipmentIssueDetected(
            tenantId: (int) $this->tenant->getKey(), orderId: 77, orderNumber: 'LZ_77',
            shipmentId: 5, reason: 'Không lấy được tem.',
        ));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::FULFILLMENT_SHIPMENT_ISSUE, $n->type);
        $this->assertSame('system', $n->category);
        $this->assertSame('fulfillment.shipment_issue:5', $n->dedup_key);
    }
```

- [ ] **Step 11: Chạy toàn bộ test liên quan**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationListenersTest.php tests/Feature/Fulfillment/ShipmentIssueDetectedEventTest.php tests/Feature/Fulfillment/FetchChannelLabelTenantContextTest.php`
Expected: PASS toàn bộ (bao gồm test regression tenant-context không bị phá).

- [ ] **Step 12: Commit**

```bash
git add app/app/Modules/Fulfillment/Events/ShipmentIssueDetected.php app/app/Modules/Fulfillment/Services/ShipmentService.php app/app/Modules/Fulfillment/Jobs/FetchChannelLabel.php app/app/Modules/Notifications/Listeners/NotifyOnShipmentIssue.php app/app/Modules/Notifications/Support/NotificationType.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Fulfillment/ShipmentIssueDetectedEventTest.php app/tests/Feature/Notifications/NotificationListenersTest.php
git commit -m "feat(notifications): alert on shipment label fetch failure (system tab)"
```

---

## Task 2: Thanh toán thất bại — `PaymentFailed`

**Files:**
- Create: `app/app/Modules/Billing/Events/PaymentFailed.php`
- Modify: `app/app/Modules/Billing/Services/PaymentService.php`
- Create: `app/app/Modules/Notifications/Listeners/NotifyOnPaymentFailed.php`
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Billing/SePayWebhookTest.php` (thêm)
- Test: `app/tests/Feature/Notifications/NotificationListenersTest.php` (thêm)

**Interfaces:**
- Produces: `PaymentFailed(tenantId, invoiceId, gateway, externalRef)` — chỉ bắn khi webhook báo KHÔNG thành công **và** suy ra được invoice/tenant từ `reference` (orphan thật thì không bắn, không biết báo ai).

- [ ] **Step 1: Thêm test vào `SePayWebhookTest.php`**

Thêm import ở đầu file:

```php
use CMBcoreSeller\Modules\Billing\Events\PaymentFailed;
use Illuminate\Support\Facades\Event;
```

Thêm test method mới vào cuối class (trước `}`):

```php
    public function test_non_succeeded_webhook_with_resolvable_invoice_fires_payment_failed_event(): void
    {
        Event::fake([PaymentFailed::class]);
        $invoice = $this->createPendingInvoice();

        $payload = array_merge($this->sepayPayload($invoice->code, 199_000, 'TX-FAIL'), ['transferType' => 'out']);
        // transferType 'out' ⇒ PaymentNotification::isSucceeded() false (SePay chỉ 'in' là tiền vào thành công).
        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $payload)->assertOk();

        Event::assertDispatched(PaymentFailed::class, fn ($e) => $e->tenantId === (int) $this->tenant->getKey()
            && $e->invoiceId === (int) $invoice->getKey());
    }

    public function test_non_succeeded_webhook_with_unresolvable_reference_does_not_fire_event(): void
    {
        Event::fake([PaymentFailed::class]);

        $payload = array_merge($this->sepayPayload('INV-NOT-EXIST', 199_000, 'TX-FAIL2'), ['transferType' => 'out']);
        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $payload)->assertOk();

        Event::assertNotDispatched(PaymentFailed::class);
    }
```

Kiểm tra nhanh: tìm định nghĩa `PaymentNotification::isSucceeded()` để xác nhận field nào quyết định (nếu không phải `transferType`, sửa test theo đúng field thật — xem Step 2).

- [ ] **Step 2: Xác nhận field quyết định `isSucceeded()` trước khi chạy test**

Run: `cd app && grep -n "isSucceeded" -r app/Integrations/Payments`

Đọc file DTO/adapter trả về từ kết quả grep, xác nhận field nào trong payload SePay quyết định `isSucceeded() === false` (có thể là `transferType !== 'in'`, hoặc field khác) — sửa `test_non_succeeded_webhook_with_resolvable_invoice_fires_payment_failed_event` cho khớp field thật nếu khác giả định `transferType: 'out'` ở Step 1.

- [ ] **Step 3: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Billing/SePayWebhookTest.php --filter=payment_failed`
Expected: FAIL — class `PaymentFailed` chưa tồn tại.

- [ ] **Step 4: Tạo event `PaymentFailed`**

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Plan B (2026-07-23) — webhook cổng thanh toán báo giao dịch KHÔNG thành công cho 1 invoice đã
 * suy ra được (qua reference/mã thanh toán). `Notifications` module lắng nghe để báo tab "Hệ thống".
 * Không dispatch khi KHÔNG suy ra invoice (orphan thật) — không biết báo cho tenant nào.
 */
class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $invoiceId,
        public readonly string $gateway,
        public readonly string $externalRef,
    ) {}
}
```

- [ ] **Step 5: Sửa `PaymentService.php`**

Thay toàn bộ nội dung file:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Integrations\Payments\DTO\PaymentNotification;
use CMBcoreSeller\Modules\Billing\Events\InvoicePaid;
use CMBcoreSeller\Modules\Billing\Events\PaymentFailed;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Áp `PaymentNotification` từ gateway vào DB:
 *   - dedupe unique `(gateway, external_ref)` ⇒ webhook chạy 2 lần = 1 row.
 *   - match invoice qua `reference` (= `invoice.code`).
 *   - underpay ⇒ payment ghi nhận `succeeded` nhưng invoice GIỮ NGUYÊN `pending` (user phải chuyển thêm).
 *   - đủ tiền ⇒ phát event `InvoicePaid` ⇒ listener `ActivateSubscription` chạy.
 *   - KHÔNG thành công ⇒ phát `PaymentFailed` (Plan B, 2026-07-23) NẾU suy ra được invoice/tenant từ
 *     reference — để `Notifications` module báo tab "Hệ thống".
 *
 * @return array{outcome:'created'|'duplicate'|'orphan'|'failed', payment?:Payment, invoice?:Invoice}
 */
class PaymentService
{
    /**
     * @return array{outcome:string, payment?:Payment, invoice?:Invoice, reason?:string}
     */
    public function applyNotification(PaymentNotification $notification): array
    {
        // 1) Webhook báo failed ⇒ ghi log + bỏ qua (không insert payment). Cố gắng suy tenant từ
        // reference CHỈ để báo tab "Hệ thống" — không suy được (orphan thật) thì chỉ log.
        if (! $notification->isSucceeded()) {
            Log::warning('payments.webhook.non_succeeded', ['gateway' => $notification->gateway, 'ref' => $notification->reference]);

            $invoice = $this->resolveInvoiceByReference($notification->reference);
            if ($invoice !== null) {
                event(new PaymentFailed(
                    tenantId: (int) $invoice->tenant_id,
                    invoiceId: (int) $invoice->getKey(),
                    gateway: $notification->gateway,
                    externalRef: $notification->externalRef,
                ));
            }

            return ['outcome' => 'failed', 'reason' => 'non_succeeded'];
        }

        // 2) Dedupe theo (gateway, external_ref) — webhook chạy 2 lần = no-op.
        $existing = Payment::query()->withoutGlobalScope(TenantScope::class)
            ->where('gateway', $notification->gateway)
            ->where('external_ref', $notification->externalRef)
            ->first();
        if ($existing !== null) {
            return ['outcome' => 'duplicate', 'payment' => $existing];
        }

        // 3) Match invoice qua reference.
        if ($notification->reference === '') {
            return ['outcome' => 'orphan', 'reason' => 'no_reference'];
        }
        $invoice = $this->resolveInvoiceByReference($notification->reference);
        if ($invoice === null) {
            Log::warning('payments.webhook.orphan', ['ref' => $notification->reference, 'gateway' => $notification->gateway]);

            return ['outcome' => 'orphan', 'reason' => 'invoice_not_found'];
        }

        // 4) Insert payment + cập nhật invoice atomic.
        return DB::transaction(function () use ($notification, $invoice) {
            $payment = Payment::query()->create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->getKey(),
                'gateway' => $notification->gateway,
                'external_ref' => $notification->externalRef,
                'amount' => $notification->amount,
                'status' => Payment::STATUS_SUCCEEDED,
                'raw_payload' => $notification->rawPayload,
                'occurred_at' => $notification->occurredAt,
            ]);

            // Tổng đã thanh toán cho invoice (cộng các payments succeeded khác — nếu có).
            $totalPaid = (int) Payment::query()->withoutGlobalScope(TenantScope::class)
                ->where('invoice_id', $invoice->getKey())
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->sum('amount');

            if ($totalPaid >= (int) $invoice->total && $invoice->status !== Invoice::STATUS_PAID) {
                $invoice->forceFill([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => now(),
                ])->save();

                InvoicePaid::dispatch($invoice->fresh(), $payment);

                return ['outcome' => 'created', 'payment' => $payment, 'invoice' => $invoice->fresh()];
            }

            // Underpay — invoice giữ pending; FE poll sẽ thấy chưa paid.
            return ['outcome' => 'created', 'payment' => $payment, 'invoice' => $invoice];
        });
    }

    /** Suy invoice từ `reference` — theo `code` trước, fallback mã thanh toán SePay `<prefix><id>`. */
    private function resolveInvoiceByReference(string $reference): ?Invoice
    {
        if ($reference === '') {
            return null;
        }
        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)
            ->where('code', $reference)->first();
        if ($invoice !== null) {
            return $invoice;
        }
        $prefix = (string) config('integrations.payments.sepay.payment_code_prefix', 'CMBCC');
        if ($prefix !== '' && preg_match('/^'.preg_quote($prefix, '/').'0*(\d{1,10})$/i', $reference, $m) === 1) {
            return Invoice::query()->withoutGlobalScope(TenantScope::class)->find((int) $m[1]);
        }

        return null;
    }
}
```

- [ ] **Step 6: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Billing/SePayWebhookTest.php`
Expected: PASS toàn bộ (kể cả test cũ — refactor `resolveInvoiceByReference` phải giữ nguyên hành vi step 3).

- [ ] **Step 7: Thêm `NotificationType::BILLING_PAYMENT_FAILED`**

Trong `NotificationType.php`, thêm hằng số (sau `FULFILLMENT_SHIPMENT_ISSUE`):

```php
    /** Giao dịch thanh toán không thành công (Plan B, 2026-07-23). */
    public const BILLING_PAYMENT_FAILED = 'billing.payment_failed';
```

Thêm vào `CATEGORY_MAP`:

```php
        self::BILLING_PAYMENT_FAILED => self::CATEGORY_SYSTEM,
```

- [ ] **Step 8: Viết listener `NotifyOnPaymentFailed`**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Billing\Events\PaymentFailed;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Plan B (2026-07-23) — giao dịch thanh toán thất bại ⇒ thông báo in-app tab "Hệ thống".
 * Dedup theo (gateway, external_ref) — webhook lặp không tạo trùng.
 */
class NotifyOnPaymentFailed implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(PaymentFailed $event): void
    {
        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::BILLING_PAYMENT_FAILED,
            'level' => 'warning',
            'title' => 'Giao dịch thanh toán không thành công',
            'body' => 'Một giao dịch thanh toán gần đây không thành công — vui lòng kiểm tra lại hoặc thử chuyển khoản lại.',
            'action_url' => '/settings/plan',
            'data' => [
                'invoice_id' => $event->invoiceId,
                'gateway' => $event->gateway,
            ],
            'dedup_key' => 'billing.payment_failed:'.$event->gateway.':'.$event->externalRef,
        ]);
    }
}
```

- [ ] **Step 9: Đăng ký listener trong provider**

Trong `NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Billing\Events\PaymentFailed;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnPaymentFailed;
```

Thêm dòng đăng ký:

```php
        Event::listen(PaymentFailed::class, NotifyOnPaymentFailed::class);
```

- [ ] **Step 10: Thêm test listener vào `NotificationListenersTest.php`**

Thêm import + test:

```php
use CMBcoreSeller\Modules\Billing\Events\PaymentFailed;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnPaymentFailed;
```

```php
    public function test_payment_failed_creates_system_notification(): void
    {
        (new NotifyOnPaymentFailed(app(NotificationDispatcher::class)))->handle(new PaymentFailed(
            tenantId: (int) $this->tenant->getKey(), invoiceId: 9, gateway: 'sepay', externalRef: 'TX-9',
        ));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::BILLING_PAYMENT_FAILED, $n->type);
        $this->assertSame('system', $n->category);
        $this->assertSame('billing.payment_failed:sepay:TX-9', $n->dedup_key);
    }
```

- [ ] **Step 11: Chạy toàn bộ test liên quan**

Run: `cd app && vendor/bin/phpunit tests/Feature/Billing/SePayWebhookTest.php tests/Feature/Notifications/NotificationListenersTest.php`
Expected: PASS toàn bộ

- [ ] **Step 12: Commit**

```bash
git add app/app/Modules/Billing/Events/PaymentFailed.php app/app/Modules/Billing/Services/PaymentService.php app/app/Modules/Notifications/Listeners/NotifyOnPaymentFailed.php app/app/Modules/Notifications/Support/NotificationType.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Billing/SePayWebhookTest.php app/tests/Feature/Notifications/NotificationListenersTest.php
git commit -m "feat(notifications): alert on payment failure (system tab)"
```

---

## Task 3: Subscription hết hạn — `SubscriptionExpired`

**Files:**
- Create: `app/app/Modules/Billing/Events/SubscriptionExpired.php`
- Modify: `app/app/Modules/Billing/Services/SubscriptionExpiryService.php`
- Create: `app/app/Modules/Notifications/Listeners/NotifyOnSubscriptionExpired.php`
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Billing/SubscriptionExpiryEventTest.php`
- Test: `app/tests/Feature/Notifications/NotificationListenersTest.php` (thêm)

**Interfaces:**
- Produces: `SubscriptionExpired(tenantId, subscriptionId)` — bắn ở CẢ 3 nhánh của `SubscriptionExpiryService::run()` khi 1 subscription chuyển sang `STATUS_EXPIRED`.

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Events\SubscriptionExpired;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionExpiryService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SubscriptionExpiryEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_trial_expiry_fires_subscription_expired_event(): void
    {
        Event::fake([SubscriptionExpired::class]);
        $tenant = Tenant::create(['name' => 'ExpiryShop1']);
        $trial = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $trial->getKey(),
            'status' => Subscription::STATUS_TRIALING, 'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'current_period_start' => now()->subMonth(), 'current_period_end' => now()->addYears(50),
        ]);

        app(SubscriptionExpiryService::class)->run();

        Event::assertDispatched(SubscriptionExpired::class, fn ($e) => $e->tenantId === (int) $tenant->getKey()
            && $e->subscriptionId === (int) $sub->getKey());
    }

    public function test_paid_plan_period_end_fires_subscription_expired_event(): void
    {
        Event::fake([SubscriptionExpired::class]);
        $tenant = Tenant::create(['name' => 'ExpiryShop2']);
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subMonth(), 'current_period_end' => now()->subDay(),
        ]);

        app(SubscriptionExpiryService::class)->run();

        Event::assertDispatched(SubscriptionExpired::class, fn ($e) => $e->subscriptionId === (int) $sub->getKey());
    }

    public function test_cancelled_period_end_fires_subscription_expired_event(): void
    {
        Event::fake([SubscriptionExpired::class]);
        $tenant = Tenant::create(['name' => 'ExpiryShop3']);
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_CANCELLED, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subMonth(), 'current_period_end' => now()->addDay(),
            'cancel_at' => now()->subDay(),
        ]);

        app(SubscriptionExpiryService::class)->run();

        Event::assertDispatched(SubscriptionExpired::class, fn ($e) => $e->subscriptionId === (int) $sub->getKey());
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Billing/SubscriptionExpiryEventTest.php`
Expected: FAIL — class `SubscriptionExpired` chưa tồn tại.

- [ ] **Step 3: Tạo event `SubscriptionExpired`**

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Plan B (2026-07-23) — subscription chuyển sang `expired` (trial hết hạn / hết kỳ không gia hạn /
 * cancelled chạy hết kỳ). `Notifications` module lắng nghe để báo tab "Hệ thống". Dedup theo
 * subscription id — mỗi subscription chỉ transition sang expired đúng 1 lần (idempotent theo
 * SubscriptionExpiryService::run()), nên không cần thêm cơ chế chống spam ở event.
 */
class SubscriptionExpired
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $subscriptionId,
    ) {}
}
```

- [ ] **Step 4: Sửa `SubscriptionExpiryService.php`**

Thêm import ở đầu file:

```php
use CMBcoreSeller\Modules\Billing\Events\SubscriptionExpired;
```

Sửa **3 khối `->each(...)`** trong `run()` — thêm `event(new SubscriptionExpired(...))` ngay sau `DB::transaction(...)` (đã commit, ngoài closure transaction), TRƯỚC dấu `}` đóng closure `each`. Khối 1 (trial hết hạn, dòng 45-56 hiện tại):

```php
            ->each(function (Subscription $sub) use (&$expired, &$fallback) {
                DB::transaction(function () use ($sub, &$expired, &$fallback) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_EXPIRED,
                        'ended_at' => now(),
                    ])->save();
                    $expired++;
                    if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
                        $fallback++;
                    }
                });
                event(new SubscriptionExpired((int) $sub->tenant_id, (int) $sub->getKey()));
            });
```

Khối 2 (gói trả phí hết kỳ, dòng 65-79 hiện tại):

```php
            ->each(function (Subscription $sub) use (&$expired, &$fallback) {
                DB::transaction(function () use ($sub, &$expired, &$fallback) {
                    $sub->forceFill(['status' => Subscription::STATUS_EXPIRED, 'ended_at' => now()])->save();
                    $expired++;

                    if (($sub->meta['pro_trial'] ?? false) === true) {
                        $this->revertProTrial($sub);

                        return;
                    }
                    if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
                        $fallback++;
                    }
                });
                event(new SubscriptionExpired((int) $sub->tenant_id, (int) $sub->getKey()));
            });
```

Khối 3 (cancelled hết kỳ, dòng 87-98 hiện tại):

```php
            ->each(function (Subscription $sub) use (&$expired, &$fallback) {
                DB::transaction(function () use ($sub, &$expired, &$fallback) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_EXPIRED,
                        'ended_at' => now(),
                    ])->save();
                    $expired++;
                    if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
                        $fallback++;
                    }
                });
                event(new SubscriptionExpired((int) $sub->tenant_id, (int) $sub->getKey()));
            });
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Billing/SubscriptionExpiryEventTest.php tests/Feature/Billing/ProTrialRevertTest.php`
Expected: PASS toàn bộ (regression `ProTrialRevertTest` không bị phá).

- [ ] **Step 6: Thêm `NotificationType::BILLING_SUBSCRIPTION_EXPIRED`**

Trong `NotificationType.php`, thêm hằng số:

```php
    /** Subscription chuyển sang hết hạn (Plan B, 2026-07-23). */
    public const BILLING_SUBSCRIPTION_EXPIRED = 'billing.subscription_expired';
```

Thêm vào `CATEGORY_MAP`:

```php
        self::BILLING_SUBSCRIPTION_EXPIRED => self::CATEGORY_SYSTEM,
```

- [ ] **Step 7: Viết listener `NotifyOnSubscriptionExpired`**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Billing\Events\SubscriptionExpired;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Plan B (2026-07-23) — subscription hết hạn ⇒ thông báo in-app tab "Hệ thống". Dedup theo
 * subscription id (mỗi subscription chỉ transition 1 lần).
 */
class NotifyOnSubscriptionExpired implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(SubscriptionExpired $event): void
    {
        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::BILLING_SUBSCRIPTION_EXPIRED,
            'level' => 'warning',
            'title' => 'Gói dịch vụ của bạn đã hết hạn',
            'body' => 'Gói đăng ký hiện tại đã kết thúc. Vào Cài đặt > Gói để gia hạn hoặc chọn gói mới.',
            'action_url' => '/settings/plan',
            'data' => ['subscription_id' => $event->subscriptionId],
            'dedup_key' => 'billing.subscription_expired:'.$event->subscriptionId,
        ]);
    }
}
```

- [ ] **Step 8: Đăng ký listener trong provider**

Trong `NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Billing\Events\SubscriptionExpired;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnSubscriptionExpired;
```

Thêm dòng đăng ký:

```php
        Event::listen(SubscriptionExpired::class, NotifyOnSubscriptionExpired::class);
```

- [ ] **Step 9: Thêm test listener vào `NotificationListenersTest.php`**

```php
use CMBcoreSeller\Modules\Billing\Events\SubscriptionExpired;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnSubscriptionExpired;
```

```php
    public function test_subscription_expired_creates_system_notification(): void
    {
        (new NotifyOnSubscriptionExpired(app(NotificationDispatcher::class)))
            ->handle(new SubscriptionExpired((int) $this->tenant->getKey(), 12));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::BILLING_SUBSCRIPTION_EXPIRED, $n->type);
        $this->assertSame('system', $n->category);
        $this->assertSame('billing.subscription_expired:12', $n->dedup_key);
    }
```

- [ ] **Step 10: Chạy toàn bộ test liên quan**

Run: `cd app && vendor/bin/phpunit tests/Feature/Billing/SubscriptionExpiryEventTest.php tests/Feature/Billing/ProTrialRevertTest.php tests/Feature/Notifications/NotificationListenersTest.php`
Expected: PASS toàn bộ

- [ ] **Step 11: Commit**

```bash
git add app/app/Modules/Billing/Events/SubscriptionExpired.php app/app/Modules/Billing/Services/SubscriptionExpiryService.php app/app/Modules/Notifications/Listeners/NotifyOnSubscriptionExpired.php app/app/Modules/Notifications/Support/NotificationType.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Billing/SubscriptionExpiryEventTest.php app/tests/Feature/Notifications/NotificationListenersTest.php
git commit -m "feat(notifications): alert on subscription expiry (system tab)"
```

---

## Task 4: AI provider lỗi liên tiếp — `AiProviderErrorDetected`

**Files:**
- Create: `app/app/Modules/Messaging/Events/AiProviderErrorDetected.php`
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php`
- Create: `app/app/Modules/Notifications/Listeners/NotifyOnAiProviderError.php`
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Unit/Messaging/AiProviderErrorStreakTest.php`
- Test: `app/tests/Feature/Notifications/NotificationListenersTest.php` (thêm)

**Interfaces:**
- Produces: `AiProviderErrorDetected(tenantId, providerCode, consecutiveErrors)` — bắn khi lỗi provider (timeout/connection/response lỗi) đạt **3 lần liên tiếp** cho cùng tenant (đếm qua cache, TTL 15', reset khi có 1 lần thành công). KHÔNG bắn ở lỗi đơn lẻ đầu tiên.

- [ ] **Step 1: Viết test trước (test method riêng, không cần dựng toàn bộ pipeline AiSuggestionService)**

```php
<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Events\AiProviderErrorDetected;
use CMBcoreSeller\Modules\Messaging\Models\AiAssistantRun;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ReflectionMethod;
use Tests\TestCase;

class AiProviderErrorStreakTest extends TestCase
{
    use RefreshDatabase;

    private function invoke(int $tenantId, string $provider, string $status): void
    {
        $method = new ReflectionMethod(AiSuggestionService::class, 'trackProviderErrorStreak');
        $method->setAccessible(true);
        $method->invoke(app(AiSuggestionService::class), $tenantId, $provider, $status);
    }

    public function test_fires_event_only_on_third_consecutive_error(): void
    {
        Event::fake([AiProviderErrorDetected::class]);

        $this->invoke(42, 'claude', AiAssistantRun::STATUS_ERROR);
        Event::assertNotDispatched(AiProviderErrorDetected::class);

        $this->invoke(42, 'claude', AiAssistantRun::STATUS_ERROR);
        Event::assertNotDispatched(AiProviderErrorDetected::class);

        $this->invoke(42, 'claude', AiAssistantRun::STATUS_ERROR);
        Event::assertDispatched(AiProviderErrorDetected::class, fn ($e) => $e->tenantId === 42
            && $e->providerCode === 'claude' && $e->consecutiveErrors === 3);
    }

    public function test_success_resets_streak(): void
    {
        Event::fake([AiProviderErrorDetected::class]);

        $this->invoke(43, 'claude', AiAssistantRun::STATUS_ERROR);
        $this->invoke(43, 'claude', AiAssistantRun::STATUS_ERROR);
        $this->invoke(43, 'claude', AiAssistantRun::STATUS_SUCCESS);
        $this->invoke(43, 'claude', AiAssistantRun::STATUS_ERROR);
        $this->invoke(43, 'claude', AiAssistantRun::STATUS_ERROR);

        Event::assertNotDispatched(AiProviderErrorDetected::class);
    }

    public function test_streak_is_scoped_per_tenant_and_provider(): void
    {
        Event::fake([AiProviderErrorDetected::class]);

        $this->invoke(44, 'claude', AiAssistantRun::STATUS_ERROR);
        $this->invoke(44, 'claude', AiAssistantRun::STATUS_ERROR);
        $this->invoke(45, 'claude', AiAssistantRun::STATUS_ERROR);

        Event::assertNotDispatched(AiProviderErrorDetected::class);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Unit/Messaging/AiProviderErrorStreakTest.php`
Expected: FAIL — class `AiProviderErrorDetected` và method `trackProviderErrorStreak` chưa tồn tại.

- [ ] **Step 3: Tạo event `AiProviderErrorDetected`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Plan B (2026-07-23) — provider AI lỗi nhiều lần LIÊN TIẾP cho 1 tenant (timeout/connection/
 * response lỗi). `Notifications` module lắng nghe để báo tab "Hệ thống" — không báo ở lỗi đơn lẻ
 * để tránh spam khi chỉ 1 request thoáng qua.
 */
class AiProviderErrorDetected
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $providerCode,
        public readonly int $consecutiveErrors,
    ) {}
}
```

- [ ] **Step 4: Sửa `AiSuggestionService.php` — thêm streak tracking trong `recordRun()`**

Thêm import ở đầu file (cùng khối `use CMBcoreSeller\Modules\Messaging\...`):

```php
use CMBcoreSeller\Modules\Messaging\Events\AiProviderErrorDetected;
use Illuminate\Support\Facades\Cache;
```

Thêm hằng số ngay dưới khai báo `class AiSuggestionService` (trước `public function __construct`):

```php
    /** Số lỗi provider LIÊN TIẾP trước khi báo tab "Hệ thống" (Plan B, 2026-07-23). */
    private const PROVIDER_ERROR_STREAK_THRESHOLD = 3;
```

Sửa method `recordRun()` (dòng 1009-1019 hiện tại) — thay:

```php
    private function recordRun(int $tenantId, Conversation $conv, string $providerCode, ?string $model, string $status, array $attrs): AiAssistantRun
    {
        return AiAssistantRun::create(array_merge([
            'tenant_id' => $tenantId,
            'conversation_id' => $conv->id,
            'provider_code' => $providerCode,
            'model' => $model,
            'mode' => AiAssistantRun::MODE_SUGGEST,
            'status' => $status,
        ], $attrs));
    }
```

bằng:

```php
    private function recordRun(int $tenantId, Conversation $conv, string $providerCode, ?string $model, string $status, array $attrs): AiAssistantRun
    {
        $run = AiAssistantRun::create(array_merge([
            'tenant_id' => $tenantId,
            'conversation_id' => $conv->id,
            'provider_code' => $providerCode,
            'model' => $model,
            'mode' => AiAssistantRun::MODE_SUGGEST,
            'status' => $status,
        ], $attrs));

        $this->trackProviderErrorStreak($tenantId, $providerCode, $status);

        return $run;
    }

    /**
     * Plan B (2026-07-23) — đếm lỗi provider LIÊN TIẾP theo (tenant, provider) qua cache (TTL 15').
     * Thành công ⇒ reset về 0. Đạt ngưỡng {@see self::PROVIDER_ERROR_STREAK_THRESHOLD} ⇒ báo tab
     * "Hệ thống" + reset streak (bắn lại nếu tích đủ ngưỡng lần nữa).
     */
    private function trackProviderErrorStreak(int $tenantId, string $providerCode, string $status): void
    {
        $key = "ai.provider_error_streak.{$tenantId}.{$providerCode}";
        if ($status !== AiAssistantRun::STATUS_ERROR) {
            Cache::forget($key);

            return;
        }
        $streak = (int) Cache::get($key, 0) + 1;
        if ($streak < self::PROVIDER_ERROR_STREAK_THRESHOLD) {
            Cache::put($key, $streak, now()->addMinutes(15));

            return;
        }
        Cache::forget($key);
        event(new AiProviderErrorDetected($tenantId, $providerCode, $streak));
    }
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Unit/Messaging/AiProviderErrorStreakTest.php`
Expected: PASS (3 tests)

- [ ] **Step 6: Thêm `NotificationType::AI_PROVIDER_ERROR`**

Trong `NotificationType.php`, thêm hằng số:

```php
    /** Provider AI lỗi liên tiếp (Plan B, 2026-07-23). */
    public const AI_PROVIDER_ERROR = 'ai.provider_error';
```

Thêm vào `CATEGORY_MAP`:

```php
        self::AI_PROVIDER_ERROR => self::CATEGORY_SYSTEM,
```

- [ ] **Step 7: Viết listener `NotifyOnAiProviderError`**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\AiProviderErrorDetected;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Plan B (2026-07-23) — provider AI lỗi liên tiếp ⇒ thông báo in-app tab "Hệ thống". Dedup theo
 * provider code — chỉ 1 bản chưa đọc tại 1 thời điểm cho cùng provider.
 */
class NotifyOnAiProviderError implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(AiProviderErrorDetected $event): void
    {
        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::AI_PROVIDER_ERROR,
            'level' => 'warning',
            'title' => 'Trợ lý AI đang gặp lỗi kết nối',
            'body' => "AI liên tục lỗi ({$event->consecutiveErrors} lần gần nhất) — tin nhắn có thể không được trả lời tự động. Vui lòng kiểm tra lại sau ít phút.",
            'action_url' => '/settings/messaging',
            'data' => ['provider_code' => $event->providerCode, 'consecutive_errors' => $event->consecutiveErrors],
            'dedup_key' => 'ai.provider_error:'.$event->providerCode,
        ]);
    }
}
```

- [ ] **Step 8: Đăng ký listener trong provider**

Trong `NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Messaging\Events\AiProviderErrorDetected;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAiProviderError;
```

Thêm dòng đăng ký:

```php
        Event::listen(AiProviderErrorDetected::class, NotifyOnAiProviderError::class);
```

- [ ] **Step 9: Thêm test listener vào `NotificationListenersTest.php`**

```php
use CMBcoreSeller\Modules\Messaging\Events\AiProviderErrorDetected;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAiProviderError;
```

```php
    public function test_ai_provider_error_creates_system_notification(): void
    {
        (new NotifyOnAiProviderError(app(NotificationDispatcher::class)))
            ->handle(new AiProviderErrorDetected((int) $this->tenant->getKey(), 'claude', 3));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::AI_PROVIDER_ERROR, $n->type);
        $this->assertSame('system', $n->category);
        $this->assertSame('ai.provider_error:claude', $n->dedup_key);
    }
```

- [ ] **Step 10: Chạy toàn bộ test liên quan**

Run: `cd app && vendor/bin/phpunit tests/Unit/Messaging/AiProviderErrorStreakTest.php tests/Feature/Notifications/NotificationListenersTest.php`
Expected: PASS toàn bộ

- [ ] **Step 11: Commit**

```bash
git add app/app/Modules/Messaging/Events/AiProviderErrorDetected.php app/app/Modules/Messaging/Services/AiSuggestionService.php app/app/Modules/Notifications/Listeners/NotifyOnAiProviderError.php app/app/Modules/Notifications/Support/NotificationType.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Unit/Messaging/AiProviderErrorStreakTest.php app/tests/Feature/Notifications/NotificationListenersTest.php
git commit -m "feat(notifications): alert on consecutive AI provider errors (system tab)"
```

---

## Task 5: AI hết hạn mức — `AiCreditExhausted`

**Files:**
- Create: `app/app/Modules/Messaging/Events/AiCreditExhausted.php`
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php`
- Create: `app/app/Modules/Notifications/Listeners/NotifyOnAiCreditExhausted.php`
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Messaging/AiCreditExhaustedEventTest.php`
- Test: `app/tests/Feature/Notifications/NotificationListenersTest.php` (thêm)

**Interfaces:**
- Produces: `AiCreditExhausted(tenantId, periodUsed, monthlyAllowance)` — bắn trong `AiSuggestionService::assertHasCredit()` khi `$this->credits->canUse()` trả `false`, trước khi ném `AiSuggestionException::limitReached`.

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Messaging\Events\AiCreditExhausted;
use CMBcoreSeller\Modules\Messaging\Exceptions\AiSuggestionException;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ReflectionMethod;
use Tests\TestCase;

class AiCreditExhaustedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_assert_has_credit_fires_event_when_exhausted(): void
    {
        Event::fake([AiCreditExhausted::class]);
        $this->seed(BillingPlanSeeder::class);
        $tenant = Tenant::create(['name' => 'AiCreditShop']);
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->first();
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDay(), 'current_period_end' => now()->addMonth(),
        ]);
        $tenantId = (int) $tenant->getKey();
        app(AiCreditService::class)->consume($tenantId, 500); // cạn hết hạn mức Pro (500/tháng)

        $method = new ReflectionMethod(AiSuggestionService::class, 'assertHasCredit');
        $method->setAccessible(true);

        try {
            $method->invoke(app(AiSuggestionService::class), $tenantId);
            $this->fail('Kỳ vọng AiSuggestionException khi hết hạn mức.');
        } catch (AiSuggestionException) {
            // đúng kỳ vọng
        }

        Event::assertDispatched(AiCreditExhausted::class, fn ($e) => $e->tenantId === $tenantId
            && $e->monthlyAllowance === 500 && $e->periodUsed === 500);
    }

    public function test_assert_has_credit_does_not_fire_event_when_credit_available(): void
    {
        Event::fake([AiCreditExhausted::class]);
        $this->seed(BillingPlanSeeder::class);
        $tenant = Tenant::create(['name' => 'AiCreditShop2']);
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->first();
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDay(), 'current_period_end' => now()->addMonth(),
        ]);

        $method = new ReflectionMethod(AiSuggestionService::class, 'assertHasCredit');
        $method->setAccessible(true);
        $method->invoke(app(AiSuggestionService::class), (int) $tenant->getKey());

        Event::assertNotDispatched(AiCreditExhausted::class);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/AiCreditExhaustedEventTest.php`
Expected: FAIL — class `AiCreditExhausted` chưa tồn tại.

- [ ] **Step 3: Tạo event `AiCreditExhausted`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Plan B (2026-07-23) — tenant hết hạn mức AI (SPEC 0032) khi cố dùng AI suggest/auto-reply.
 * `Notifications` module lắng nghe để báo tab "Hệ thống" (auto-reply/suggest ngừng hoạt động tới
 * khi hạn mức được làm mới kỳ sau hoặc mua thêm).
 */
class AiCreditExhausted
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $periodUsed,
        public readonly int $monthlyAllowance,
    ) {}
}
```

- [ ] **Step 4: Sửa `assertHasCredit()` trong `AiSuggestionService.php`**

Thêm import:

```php
use CMBcoreSeller\Modules\Messaging\Events\AiCreditExhausted;
```

Sửa method (dòng 117-123 hiện tại) — thay:

```php
    private function assertHasCredit(int $tenantId): void
    {
        if (! $this->credits->canUse($tenantId, 1)) {
            $s = $this->credits->summary($tenantId);
            throw AiSuggestionException::limitReached((int) $s['period_used'], (int) $s['monthly_allowance']);
        }
    }
```

bằng:

```php
    private function assertHasCredit(int $tenantId): void
    {
        if (! $this->credits->canUse($tenantId, 1)) {
            $s = $this->credits->summary($tenantId);
            event(new AiCreditExhausted($tenantId, (int) $s['period_used'], (int) $s['monthly_allowance']));
            throw AiSuggestionException::limitReached((int) $s['period_used'], (int) $s['monthly_allowance']);
        }
    }
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/AiCreditExhaustedEventTest.php`
Expected: PASS (2 tests)

- [ ] **Step 6: Thêm `NotificationType::AI_CREDIT_EXHAUSTED`**

Trong `NotificationType.php`, thêm hằng số:

```php
    /** Hết hạn mức AI trong kỳ (Plan B, 2026-07-23). */
    public const AI_CREDIT_EXHAUSTED = 'ai.credit_exhausted';
```

Thêm vào `CATEGORY_MAP`:

```php
        self::AI_CREDIT_EXHAUSTED => self::CATEGORY_SYSTEM,
```

- [ ] **Step 7: Viết listener `NotifyOnAiCreditExhausted`**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\AiCreditExhausted;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Date;

/**
 * Plan B (2026-07-23) — hết hạn mức AI trong kỳ ⇒ thông báo in-app tab "Hệ thống". Dedup theo
 * tháng hiện tại — chỉ báo 1 lần/tháng, tự "hết dedup" khi sang kỳ mới.
 */
class NotifyOnAiCreditExhausted implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(AiCreditExhausted $event): void
    {
        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::AI_CREDIT_EXHAUSTED,
            'level' => 'warning',
            'title' => 'Đã hết hạn mức trả lời AI trong kỳ',
            'body' => "Đã dùng {$event->periodUsed}/{$event->monthlyAllowance} lượt AI trong kỳ này — AI sẽ ngừng gợi ý/trả lời tự động tới kỳ sau hoặc khi mua thêm.",
            'action_url' => '/settings/plan',
            'data' => ['period_used' => $event->periodUsed, 'monthly_allowance' => $event->monthlyAllowance],
            'dedup_key' => 'ai.credit_exhausted:'.Date::now()->format('Y-m'),
        ]);
    }
}
```

- [ ] **Step 8: Đăng ký listener trong provider**

Trong `NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Messaging\Events\AiCreditExhausted;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAiCreditExhausted;
```

Thêm dòng đăng ký:

```php
        Event::listen(AiCreditExhausted::class, NotifyOnAiCreditExhausted::class);
```

- [ ] **Step 9: Thêm test listener vào `NotificationListenersTest.php`**

```php
use CMBcoreSeller\Modules\Messaging\Events\AiCreditExhausted;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAiCreditExhausted;
```

```php
    public function test_ai_credit_exhausted_creates_system_notification(): void
    {
        (new NotifyOnAiCreditExhausted(app(NotificationDispatcher::class)))
            ->handle(new AiCreditExhausted((int) $this->tenant->getKey(), 500, 500));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::AI_CREDIT_EXHAUSTED, $n->type);
        $this->assertSame('system', $n->category);
        $this->assertStringContainsString('500/500', $n->body);
    }
```

- [ ] **Step 10: Chạy toàn bộ test liên quan**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/AiCreditExhaustedEventTest.php tests/Feature/Notifications/NotificationListenersTest.php`
Expected: PASS toàn bộ

- [ ] **Step 11: Commit**

```bash
git add app/app/Modules/Messaging/Events/AiCreditExhausted.php app/app/Modules/Messaging/Services/AiSuggestionService.php app/app/Modules/Notifications/Listeners/NotifyOnAiCreditExhausted.php app/app/Modules/Notifications/Support/NotificationType.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Messaging/AiCreditExhaustedEventTest.php app/tests/Feature/Notifications/NotificationListenersTest.php
git commit -m "feat(notifications): alert on AI credit exhaustion (system tab)"
```

---

## Hoàn tất Plan B

Sau Task 5: chạy toàn bộ quality gate:

```bash
cd app
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
```

Tất cả phải xanh. Plan B không có route mới, không cần cập nhật `docs/05-api/endpoints.md`, không cần migration mới, không cần bước backfill riêng — chỉ **CẦN DEPLOY** (không cần thao tác thủ công gì thêm sau deploy, khác Plan A).
