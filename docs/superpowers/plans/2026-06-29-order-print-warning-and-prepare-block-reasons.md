# Cảnh báo "không in được" + Lý do cụ thể khi đơn chưa thể chuẩn bị — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hiển thị cảnh báo "không thể in đơn được" cho đơn terminal chưa có tem, và thay thông báo "đang chờ" chung chung bằng lý do cụ thể (suy từ raw status của sàn) khi đơn chưa thể chuẩn bị.

**Architecture:** Bộ mã lý do chuẩn (`PrepareBlockReason` enum, core, không gắn tên sàn). Ánh xạ `raw_status → lý do` nằm ở từng connector (`Integrations/Channels/*`) — nơi DUY NHẤT chuỗi raw status sàn được phép xuất hiện (giống `mapStatus`). `ShipmentService::assertChannelOrderFulfillable` và `OrderResource` cùng dùng nguồn này. FE thêm tooltip + cảnh báo + khóa nút.

**Tech Stack:** Laravel 11 (PHP 8.2, PHPUnit), React 18 + Ant Design + TypeScript (Vite).

## Global Constraints

- **All PHP/Node commands run from `app/`** (repo root chỉ có docker/docs). `cd app` trước mọi lệnh.
- **PSR-4 `CMBcoreSeller\` → `app/app/`**.
- **Core không biết tên sàn**: không `if ($provider === ...)` / `match($source)` trong module Orders/Fulfillment. Ánh xạ raw status CHỈ ở connector.
- Chuỗi hiển thị **tiếng Việt**; định danh/code **tiếng Anh**.
- Tính per-row trong API Resource phải **rẻ, không gọi API sàn, không N+1**.
- UI icon dùng `@ant-design/icons` (KHÔNG emoji); ưu tiên Tooltip + Tag theo pattern sẵn có.
- Quality gate (mirror CI, chạy từ `app/`): `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`.

---

### Task 1: Enum `PrepareBlockReason` (core)

**Files:**
- Create: `app/app/Support/Enums/PrepareBlockReason.php`
- Test: `app/tests/Unit/Support/Enums/PrepareBlockReasonTest.php`

**Interfaces:**
- Consumes: (none)
- Produces: `enum PrepareBlockReason: string` với các case `AwaitingPayment='awaiting_payment'`, `PlatformHold='platform_hold'`, `PlatformFulfilled='platform_fulfilled'`, `CancelInProgress='cancel_in_progress'`, `PlatformProcessing='platform_processing'`; method `label(): string`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Support\Enums;

use CMBcoreSeller\Support\Enums\PrepareBlockReason;
use PHPUnit\Framework\TestCase;

class PrepareBlockReasonTest extends TestCase
{
    public function test_each_case_has_vietnamese_label(): void
    {
        foreach (PrepareBlockReason::cases() as $case) {
            $this->assertNotSame('', trim($case->label()));
        }
    }

    public function test_known_labels(): void
    {
        $this->assertSame('Chờ người mua thanh toán', PrepareBlockReason::AwaitingPayment->label());
        $this->assertSame('Đang xử lý yêu cầu huỷ — chưa thể chuẩn bị', PrepareBlockReason::CancelInProgress->label());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run (from `app/`): `php artisan test --filter=PrepareBlockReasonTest`
Expected: FAIL — class `CMBcoreSeller\Support\Enums\PrepareBlockReason` not found.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace CMBcoreSeller\Support\Enums;

/**
 * Lý do một đơn CHƯA thể chuẩn bị hàng (suy từ raw status của sàn). Core-level,
 * KHÔNG gắn tên sàn — connector map raw status sang các case này.
 */
enum PrepareBlockReason: string
{
    case AwaitingPayment = 'awaiting_payment';
    case PlatformHold = 'platform_hold';
    case PlatformFulfilled = 'platform_fulfilled';
    case CancelInProgress = 'cancel_in_progress';
    case PlatformProcessing = 'platform_processing';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Chờ người mua thanh toán',
            self::PlatformHold => 'Sàn đang tạm giữ đơn (thời gian người mua được huỷ / duyệt COD) — chưa cho chuẩn bị',
            self::PlatformFulfilled => 'Đơn do sàn xử lý kho (FBT/FBL) — bạn không cần chuẩn bị',
            self::CancelInProgress => 'Đang xử lý yêu cầu huỷ — chưa thể chuẩn bị',
            self::PlatformProcessing => 'Sàn đang xử lý đơn — chưa thể chuẩn bị',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PrepareBlockReasonTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Support/Enums/PrepareBlockReason.php app/tests/Unit/Support/Enums/PrepareBlockReasonTest.php
git commit -m "feat(fulfillment): thêm enum PrepareBlockReason (lý do đơn chưa thể chuẩn bị)"
```

---

### Task 2: Contract `prepareBlockReason` + ánh xạ trong 4 connector

> ⚠️ Thêm method vào interface `ChannelConnector` ⇒ **mọi** implementor phải có method, nếu không PHP fatal. Implementor hiện hữu: `TikTokConnector`, `ShopeeConnector`, `LazadaConnector`, `ManualConnector`. Làm trọn trong 1 task.

**Files:**
- Modify: `app/app/Integrations/Channels/Contracts/ChannelConnector.php` (thêm `use` + khai method sau `mapStatus`, ~dòng 94)
- Modify: `app/app/Integrations/Channels/TikTok/TikTokConnector.php`
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php`
- Modify: `app/app/Integrations/Channels/Lazada/LazadaConnector.php`
- Modify: `app/app/Integrations/Channels/Manual/ManualConnector.php`
- Test: `app/tests/Unit/Integrations/Channels/PrepareBlockReasonMapTest.php`

**Interfaces:**
- Consumes: `PrepareBlockReason` (Task 1).
- Produces: method trên `ChannelConnector`:
  `public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason;`
  Hợp đồng: trả `PrepareBlockReason` khi đơn CHƯA thể chuẩn bị; `null` khi đã sẵn sàng chuẩn bị / đã-đang giao / terminal. Thuần map chuỗi, KHÔNG gọi API.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Integrations\Channels;

use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
use Tests\TestCase;

class PrepareBlockReasonMapTest extends TestCase
{
    public function test_tiktok_mapping(): void
    {
        $c = app(TikTokConnector::class);
        $this->assertSame(PrepareBlockReason::AwaitingPayment, $c->prepareBlockReason('UNPAID'));
        $this->assertSame(PrepareBlockReason::PlatformHold, $c->prepareBlockReason('ON_HOLD'));
        $this->assertSame(PrepareBlockReason::PlatformFulfilled, $c->prepareBlockReason('AWAITING_SHIPMENT', ['fulfillment_type' => 'FULFILLMENT_BY_TIKTOK']));
        $this->assertNull($c->prepareBlockReason('AWAITING_SHIPMENT'));
        $this->assertNull($c->prepareBlockReason('AWAITING_COLLECTION'));
    }

    public function test_lazada_mapping(): void
    {
        $c = app(LazadaConnector::class);
        $this->assertSame(PrepareBlockReason::AwaitingPayment, $c->prepareBlockReason('unpaid'));
        $this->assertSame(PrepareBlockReason::PlatformProcessing, $c->prepareBlockReason('topack'));
        $this->assertNull($c->prepareBlockReason('pending'));
    }

    public function test_shopee_mapping(): void
    {
        $c = app(ShopeeConnector::class);
        $this->assertSame(PrepareBlockReason::AwaitingPayment, $c->prepareBlockReason('UNPAID'));
        $this->assertSame(PrepareBlockReason::CancelInProgress, $c->prepareBlockReason('IN_CANCEL'));
        $this->assertNull($c->prepareBlockReason('READY_TO_SHIP'));
        $this->assertNull($c->prepareBlockReason('RETRY_SHIP'));
    }

    public function test_manual_never_blocks(): void
    {
        $this->assertNull(app(ManualConnector::class)->prepareBlockReason('anything'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PrepareBlockReasonMapTest`
Expected: FAIL — method `prepareBlockReason` not defined on connectors.

- [ ] **Step 3a: Khai method trong interface**

Trong `app/app/Integrations/Channels/Contracts/ChannelConnector.php`, thêm import dưới khối `use` (cạnh các DTO):

```php
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
```

Ngay sau method `mapStatus(...)` (kết thúc ~dòng 94), thêm:

```php
    /**
     * Lý do đơn CHƯA thể chuẩn bị hàng, suy từ raw status của sàn (+ fulfillment_type khi cần).
     * Trả null khi đơn đã sẵn sàng chuẩn bị / đã-đang giao / terminal. Thuần map chuỗi — KHÔNG gọi API.
     * Cùng `mapStatus`, đây là nơi DUY NHẤT chuỗi raw status của sàn được phép xuất hiện.
     *
     * @param  array<string, mixed>  $rawOrder
     */
    public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason;
```

- [ ] **Step 3b: TikTok** — thêm import + method trong `TikTokConnector.php` (đặt ngay sau `mapStatus`, ~dòng 210)

Import (cạnh `use ...StandardOrderStatus;`):
```php
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
```
Method:
```php
    public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason
    {
        // FBT: TikTok tự xử lý kho — người bán không chuẩn bị (recipient bị redact). Doc: fulfillment-api-overview.
        if (strtoupper((string) data_get($rawOrder, 'fulfillment_type')) === 'FULFILLMENT_BY_TIKTOK') {
            return PrepareBlockReason::PlatformFulfilled;
        }

        // Doc Order API: ON_HOLD "not allowed to be fulfilled" (remorse 1h); UNPAID chưa thanh toán.
        return match (strtoupper(trim($rawStatus))) {
            'UNPAID' => PrepareBlockReason::AwaitingPayment,
            'ON_HOLD' => PrepareBlockReason::PlatformHold,
            default => null, // AWAITING_SHIPMENT/PARTIALLY_SHIPPING = sẵn sàng; còn lại đã giao/terminal
        };
    }
```

- [ ] **Step 3c: Shopee** — thêm import + method trong `ShopeeConnector.php` (sau `mapStatus`)

```php
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
```
```php
    public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason
    {
        // Doc Order Management §10: UNPAID chưa thanh toán; IN_CANCEL đang xử lý yêu cầu huỷ.
        return match (strtoupper(trim($rawStatus))) {
            'UNPAID' => PrepareBlockReason::AwaitingPayment,
            'IN_CANCEL' => PrepareBlockReason::CancelInProgress,
            default => null, // READY_TO_SHIP/RETRY_SHIP = cho chuẩn bị; còn lại đã giao/terminal
        };
    }
```

- [ ] **Step 3d: Lazada** — thêm import + method trong `LazadaConnector.php` (sau `mapStatus`)

```php
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
```
```php
    public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason
    {
        // Doc GetOrders enum + pack flow: unpaid chưa thanh toán; topack sàn đang xử lý; pending = trạng thái để Pack.
        return match (strtolower(trim($rawStatus))) {
            'unpaid' => PrepareBlockReason::AwaitingPayment,
            'topack' => PrepareBlockReason::PlatformProcessing,
            default => null,
        };
    }
```

- [ ] **Step 3e: Manual** — thêm import + method trong `ManualConnector.php`

```php
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
```
```php
    public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason
    {
        // Đơn thủ công không có trạng thái sàn; preparability đã chặn bằng terminal/âm tồn ở assertPreparable.
        return null;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PrepareBlockReasonMapTest`
Expected: PASS (4 test).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Channels/Contracts/ChannelConnector.php \
        app/app/Integrations/Channels/TikTok/TikTokConnector.php \
        app/app/Integrations/Channels/Shopee/ShopeeConnector.php \
        app/app/Integrations/Channels/Lazada/LazadaConnector.php \
        app/app/Integrations/Channels/Manual/ManualConnector.php \
        app/tests/Unit/Integrations/Channels/PrepareBlockReasonMapTest.php
git commit -m "feat(channels): prepareBlockReason map raw_status sàn → lý do chuẩn (TikTok/Shopee/Lazada/Manual)"
```

---

### Task 3: Hợp nhất `assertChannelOrderFulfillable` dùng `prepareBlockReason`

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php:346-359` (method `assertChannelOrderFulfillable`)
- Test: `app/tests/Feature/Fulfillment/AssertChannelOrderFulfillableTest.php`

**Interfaces:**
- Consumes: `ChannelConnector::prepareBlockReason` (Task 2), `$this->channels` (ChannelRegistry, đã inject sẵn ở constructor dòng 44).
- Produces: hành vi — `createForOrder()`/`assertPreparable()` ném `RuntimeException` với **message cụ thể** (`$reason->label()`) cho đơn sàn bị chặn.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use RuntimeException;
use Tests\TestCase;

class AssertChannelOrderFulfillableTest extends TestCase
{
    public function test_blocks_unpaid_channel_order_with_specific_message(): void
    {
        // Tạo tenant + channel account (provider 'tiktok') + order UNPAID. Dùng factory/helper hiện có của repo.
        [$order] = $this->makeChannelOrderRawStatus('tiktok', 'UNPAID', standard: 'unpaid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chờ người mua thanh toán');

        app(ShipmentService::class)->assertPreparable($order->fresh());
    }

    public function test_allows_ready_to_ship_channel_order(): void
    {
        [$order] = $this->makeChannelOrderRawStatus('shopee', 'READY_TO_SHIP', standard: 'ready_to_ship');

        // Không ném vì raw status không bị chặn (có thể ném vì lý do khác như âm tồn — test này chỉ
        // khẳng định KHÔNG ném message lý-do-sàn).
        try {
            app(ShipmentService::class)->assertPreparable($order->fresh());
            $this->assertTrue(true);
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('Chờ người mua thanh toán', $e->getMessage());
        }
    }
}
```

> Lưu ý cho người triển khai: dùng đúng factory/seeding của repo cho Tenant/ChannelAccount/Order (xem các test Feature có sẵn trong `tests/Feature/Orders` hoặc `tests/Feature/Fulfillment`). `makeChannelOrderRawStatus()` là helper bạn tự viết trong test (tạo ChannelAccount với `provider`, Order với `channel_account_id`, `raw_status`, `status`, `source=provider`, `tenant_id`). Bọc trong `runAs`/tenant context như các test fulfillment hiện có.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AssertChannelOrderFulfillableTest`
Expected: FAIL — message hiện tại là chuỗi chung chung cũ (`...sàn chưa cho chuẩn bị hàng...`), không chứa "Chờ người mua thanh toán".

- [ ] **Step 3: Thay thân method**

Trong `ShipmentService.php`, thay toàn bộ thân `assertChannelOrderFulfillable` (dòng 346-359) bằng:

```php
    private function assertChannelOrderFulfillable(Order $order): void
    {
        $account = ChannelAccount::query()->find($order->channel_account_id);
        if (! $account || ! $this->channels->has((string) $account->provider)) {
            return;
        }
        // Nguồn sự thật DUY NHẤT cho "đơn chưa thể chuẩn bị" = connector (map raw_status sàn). Thuần map, không gọi API.
        // Chạy trong luồng HTTP đồng bộ (có tenant) nên ChannelAccount::find() OK — không dính bug job thiếu runAs.
        $reason = $this->channels->for((string) $account->provider)->prepareBlockReason(
            (string) $order->raw_status,
            ['fulfillment_type' => $order->fulfillment_type],
        );
        if ($reason !== null) {
            throw new RuntimeException($reason->label());
        }
    }
```

> `config(...unfulfillable_raw_statuses)` không còn được tham chiếu ở đây; giữ nguyên khóa config (vô hại) — KHÔNG xóa ở task này để tránh ảnh hưởng ngoài phạm vi.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AssertChannelOrderFulfillableTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShipmentService.php \
        app/tests/Feature/Fulfillment/AssertChannelOrderFulfillableTest.php
git commit -m "refactor(fulfillment): assertChannelOrderFulfillable dùng prepareBlockReason (thông báo cụ thể)"
```

---

### Task 4: Expose `prepare_block_reason` trong `OrderResource`

**Files:**
- Modify: `app/app/Modules/Orders/Http/Resources/OrderResource.php` (thêm `use`, thêm field ~dòng 74, thêm helper private)
- Test: `app/tests/Feature/Orders/OrderResourcePrepareBlockReasonTest.php`

**Interfaces:**
- Consumes: `ChannelConnector::prepareBlockReason` (Task 2) qua `app(ChannelRegistry::class)`.
- Produces: field API `prepare_block_reason: string|null` (nhãn VN, null khi không bị chặn / đơn manual / terminal).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Orders;

use Tests\TestCase;

class OrderResourcePrepareBlockReasonTest extends TestCase
{
    public function test_unpaid_channel_order_exposes_reason(): void
    {
        $payload = $this->indexOrdersForRawStatus('shopee', 'UNPAID', standard: 'unpaid');
        $row = $payload['data'][0];
        $this->assertSame('Chờ người mua thanh toán', $row['prepare_block_reason']);
    }

    public function test_ready_channel_order_has_null_reason(): void
    {
        $payload = $this->indexOrdersForRawStatus('shopee', 'READY_TO_SHIP', standard: 'ready_to_ship');
        $this->assertNull($payload['data'][0]['prepare_block_reason']);
    }

    public function test_manual_order_has_null_reason(): void
    {
        $payload = $this->indexManualOrder(standard: 'pending');
        $this->assertNull($payload['data'][0]['prepare_block_reason']);
    }
}
```

> Người triển khai: `indexOrdersForRawStatus()/indexManualOrder()` là helper test tự viết — seed tenant + (với đơn sàn) ChannelAccount `provider` + Order (`source=provider`, `channel_account_id`, `raw_status`, `status`), rồi `getJson('/api/v1/orders', [...auth + X-Tenant-Id])` và trả `$response->json()`. Tham khảo `tests/Feature/Orders/*` có sẵn cho cách auth Sanctum + header tenant.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrderResourcePrepareBlockReasonTest`
Expected: FAIL — key `prepare_block_reason` không tồn tại.

- [ ] **Step 3: Thêm field + helper**

Trong `OrderResource.php`:

(a) Thêm import dưới khối `use` (cạnh `use ...StandardOrderStatus;`):
```php
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
```

(b) Trong `toArray()`, ngay sau dòng `'issue_reason' => $this->issue_reason,` (dòng 74) thêm:
```php
            // Lý do cụ thể đơn CHƯA thể chuẩn bị (thay "đang chờ" chung chung). Null = chuẩn bị được / manual / terminal.
            'prepare_block_reason' => $this->prepareBlockReasonLabel(),
```

(c) Thêm method private (cạnh `canBadReport()`):
```php
    /**
     * Lý do (nhãn VN) đơn sàn chưa thể chuẩn bị — map từ raw_status qua connector. Rẻ: thuần map, không gọi API.
     * Null cho đơn manual (không có channel_account_id), đơn terminal, hoặc provider không có connector.
     */
    private function prepareBlockReasonLabel(): ?string
    {
        if (! $this->channel_account_id || $this->status->isTerminal()) {
            return null;
        }
        $provider = (string) $this->source;
        $registry = app(ChannelRegistry::class);
        if (! $registry->has($provider)) {
            return null;
        }

        return $registry->for($provider)->prepareBlockReason(
            (string) $this->raw_status,
            ['fulfillment_type' => $this->fulfillment_type],
        )?->label();
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OrderResourcePrepareBlockReasonTest`
Expected: PASS.

- [ ] **Step 5: Chạy quality gate PHP & commit**

Run: `vendor/bin/pint --test && vendor/bin/phpstan analyse`
Expected: PASS (nếu pint báo format, chạy `vendor/bin/pint` rồi add lại).

```bash
git add app/app/Modules/Orders/Http/Resources/OrderResource.php \
        app/tests/Feature/Orders/OrderResourcePrepareBlockReasonTest.php
git commit -m "feat(orders): expose prepare_block_reason ở OrderResource"
```

---

### Task 5: Frontend — cảnh báo "không in được" + tooltip lý do + khóa nút

**Files:**
- Modify: `app/resources/js/lib/orders.tsx` (interface `Order`, ~dòng 41-102)
- Modify: `app/resources/js/pages/OrdersPage.tsx` (cột "Đơn hàng" ~dòng 521; cột "Trạng thái" dòng 569; `eliPrepare` dòng 232; skip dòng 300; `actionable` dòng 316)

**Interfaces:**
- Consumes: field API `prepare_block_reason` (Task 4); field sẵn có `is_terminal`, `shipment.has_label`.
- Produces: (UI only).

- [ ] **Step 1: Thêm field vào interface `Order`**

Trong `app/resources/js/lib/orders.tsx`, thêm vào interface `Order` (cạnh `issue_reason`):
```ts
    prepare_block_reason?: string | null;
```

- [ ] **Step 2: Part A — cảnh báo "không in được" (cột "Đơn hàng")**

Trong `OrdersPage.tsx`, ngay sau dòng 521 (`{o.shipment && o.shipment.print_count > 0 && <PrintCountBadge .../>}`), thêm:
```tsx
                        {o.is_terminal && !o.shipment?.has_label && (
                            <Tooltip title="Đơn đã ở trạng thái cuối (hoàn tất/đã huỷ/đã trả) mà chưa từng lưu phiếu giao hàng — không thể in đơn được.">
                                <Tag icon={<WarningOutlined />} style={{ marginInlineEnd: 0 }}>Không in được</Tag>
                            </Tooltip>
                        )}
```
(`WarningOutlined`, `Tooltip`, `Tag` đã được import sẵn trong file.)

- [ ] **Step 3: Part B — tooltip lý do trên cột "Trạng thái"**

Thay dòng 569 (cột Trạng thái) bằng:
```tsx
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 140, render: (v, o) => (
            o.prepare_block_reason
                ? <Tooltip title={o.prepare_block_reason}><span style={{ cursor: 'help' }}><StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /></span></Tooltip>
                : <StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} />
        ) },
```

- [ ] **Step 4: Khóa nút "Chuẩn bị hàng" cho đơn bị chặn**

(a) Dòng 232 — `eliPrepare`, thêm điều kiện `&& !o.prepare_block_reason`:
```tsx
    const eliPrepare = selectedOrders.filter((o) => !o.shipment && PREPARE_OK_STATUSES.includes(o.status) && !o.out_of_stock && !o.prepare_block_reason);
```

(b) Dòng ~298-301 — thêm nhánh skip với lý do cụ thể (đặt TRƯỚC nhánh `!PREPARE_OK_STATUSES`):
```tsx
                for (const o of chunk) {
                    if (o.shipment) skips.push({ id: o.id, status: 'skipped', reason: 'Đã có phiếu giao hàng — bỏ qua.' });
                    else if (o.prepare_block_reason) skips.push({ id: o.id, status: 'skipped', reason: o.prepare_block_reason });
                    else if (!PREPARE_OK_STATUSES.includes(o.status)) skips.push({ id: o.id, status: 'skipped', reason: 'Đơn không ở trạng thái chuẩn bị — bỏ qua.' });
                    else actionable.push(o.id);
                }
```

(c) Dòng 316 — `actionable` trong `doBulkPrepare`, thêm `&& !o.prepare_block_reason`:
```tsx
        const actionable = selectedOrders.filter((o) => !o.shipment && PREPARE_OK_STATUSES.includes(o.status) && !o.prepare_block_reason);
```

- [ ] **Step 5: Chạy quality gate FE**

Run (từ `app/`): `npm run lint && npm run typecheck && npm run build`
Expected: PASS (không lỗi tsc/eslint mới; 7 test GHN/fulfillment fail sẵn không liên quan FE).

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/lib/orders.tsx app/resources/js/pages/OrdersPage.tsx
git commit -m "feat(orders-ui): cảnh báo 'không in được' + tooltip lý do chưa chuẩn bị + khóa nút Chuẩn bị hàng"
```

---

## Self-Review

**Spec coverage:**
- Part A (cảnh báo terminal + chưa có tem) → Task 5 Step 2 (dùng field sẵn có, không đổi BE — khớp spec). ✓
- Part B enum `PrepareBlockReason` → Task 1. ✓
- Part B ánh xạ connector (TikTok/Lazada/Shopee/Manual, bảng doc) → Task 2. ✓
- Part B hợp nhất `assertChannelOrderFulfillable` → Task 3. ✓
- Part B expose `prepare_block_reason` → Task 4. ✓
- Part B FE tooltip + khóa nút → Task 5. ✓
- Kiểm thử PHP (mapping + resource + assert) → Task 1-4; FE lint/typecheck/build → Task 5. ✓

**Placeholder scan:** Không có "TBD/TODO/handle edge cases". Các helper test (`makeChannelOrderRawStatus`, `indexOrdersForRawStatus`...) được mô tả rõ là helper người triển khai tự viết theo factory repo — kèm chỉ dẫn cụ thể (đây là phần test-fixture phụ thuộc seeding repo, không phải logic feature).

**Type consistency:** `prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason` đồng nhất ở interface + 4 connector + 2 call site (ShipmentService, OrderResource). Enum cases dùng đúng tên (`AwaitingPayment`, `PlatformHold`, `PlatformFulfilled`, `CancelInProgress`, `PlatformProcessing`). Field FE `prepare_block_reason` khớp key resource. `$this->channels` (ShipmentService) vs `app(ChannelRegistry::class)` (OrderResource) — đúng theo nơi gọi.

## Lưu ý vận hành (sau khi merge)

- Prod chạy **baked Docker image, deploy KHÔNG tự build từ main** ([[prod-ops-ssh-and-deploy]]) ⇒ thay đổi chỉ hiệu lực sau rebuild + redeploy.
- Không có migration ⇒ không cần `migrate`.
