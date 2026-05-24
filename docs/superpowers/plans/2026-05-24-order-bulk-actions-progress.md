# Order Bulk Actions, Progress & Lazada Auto-RTS — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuyển màn xử lý đơn sang mô hình "nút luôn bấm được + validate-và-bỏ-qua per-đơn + popup tiến trình", sửa bug nuốt lỗi bulk pack/handover, thêm toggle per-shop Lazada auto-RTS-after-print và admin toggle hiển thị lỗi kỹ thuật.

**Architecture:** Backend giữ nguyên lõi `ShipmentService`; chỉ đổi tầng controller bulk để trả `results[]` per-đơn (ok/skipped/error) và thêm hook auto-RTS trong markPrinted. Frontend thêm engine `useBulkAction` + `BulkProgressModal` chạy theo chunk, thay logic disable-theo-status. Mọi hành vi mới mặc định không đổi behaviour cũ.

**Tech Stack:** Laravel 11 (PHP 8.3, Pest), React 18 + TypeScript + Ant Design 5 + TanStack Query.

Spec: `docs/superpowers/specs/2026-05-24-order-processing-actions-and-progress-design.md`.

---

## File Structure

**Backend (new):**
- `app/app/Modules/Fulfillment/Support/FulfillmentErrorMapper.php` — map exception/mã sàn → câu tiếng Việt + phân loại skipped/error.

**Backend (modify):**
- `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` — +1 key `fulfillment.expose_technical_errors`.
- `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php` — `pack`/`handover` trả `results[]`.
- `app/app/Modules/Fulfillment/Services/ShipmentService.php` — +method `autoReadyToShipAfterPrint(array $shipmentIds)`.
- `app/app/Modules/Fulfillment/Http/Controllers/PrintJobController.php` — gọi auto-RTS sau markPrinted (label).
- `app/app/Modules/Channels/Models/ChannelAccount.php` — +`auto_rts_after_print` fillable+cast.
- `app/app/Modules/Channels/Http/Resources/ChannelAccountResource.php` — expose `auto_rts_after_print` + `auto_rts_available`.
- `app/app/Modules/Channels/Http/Controllers/ChannelAccountController.php` — +`toggleAutoRts`.
- `app/routes/api.php` — +route toggle auto-rts.
- `app/database/migrations/...add_auto_rts_after_print_to_channel_accounts.php` — new column.
- `.env.example` — +`FULFILLMENT_EXPOSE_TECHNICAL_ERRORS`.

**Frontend (new):**
- `app/resources/js/lib/useBulkAction.ts` — chunked batch engine hook.
- `app/resources/js/components/BulkProgressModal.tsx` — progress modal.

**Frontend (modify):**
- `app/resources/js/lib/fulfillment.tsx` — `BulkItemResult` type + đổi pack/handover mutation trả `results[]`.
- `app/resources/js/lib/channels.tsx` — +`useSetChannelAutoRts`; `ChannelAccount` type +`auto_rts_after_print`/`auto_rts_available`.
- `app/resources/js/pages/OrdersPage.tsx` — gắn engine + modal; bỏ disable theo status; pre-check in tem.
- `app/resources/js/components/OrderProcessing.tsx` — `OrderActions` luôn hiện nút (theo quyền); pre-check in tem.
- `app/resources/js/pages/ChannelsPage.tsx` — Switch auto-RTS cho shop Lazada.

**Docs (modify):** `docs/04-channels/order-processing.md`, `docs/05-api/endpoints.md`, `docs/03-domain/fulfillment-and-printing.md`.

---

## Phase 1 — Backend: error mapper + admin toggle

### Task 1: Admin toggle key trong catalog

**Files:**
- Modify: `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` (thêm vào mảng group `fulfillment`)
- Test: `app/tests/Feature/Settings/SystemSettingsCatalogTest.php` (nếu chưa có, tạo)

- [ ] **Step 1: Thêm key vào catalog** — trong `all()`, sau `'fulfillment.print_label_size'`:

```php
'fulfillment.expose_technical_errors' => [
    'group' => 'fulfillment', 'type' => 'bool', 'is_secret' => false,
    'env' => 'FULFILLMENT_EXPOSE_TECHNICAL_ERRORS', 'label' => 'Hiện chi tiết lỗi kỹ thuật',
    'description' => 'Bật để hiện thông tin lỗi kỹ thuật khi xử lý đơn (debug). Prod nên TẮT — chỉ hiện câu tiếng Việt.',
],
```

- [ ] **Step 2: Cập nhật comment tổng số key** ở docblock (38 → 39, fulfillment 15 → 16).

- [ ] **Step 3: Thêm dòng vào `.env.example`** (gần các biến FULFILLMENT_*):

```
FULFILLMENT_EXPOSE_TECHNICAL_ERRORS=false
```

- [ ] **Step 4: Verify** — `cd app && php artisan tinker --execute="echo \CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog::has('fulfillment.expose_technical_errors') ? 'OK' : 'MISSING';"`
Expected: `OK`

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Settings/Support/SystemSettingsCatalog.php .env.example
git commit -m "feat(settings): admin toggle fulfillment.expose_technical_errors"
```

### Task 2: FulfillmentErrorMapper

**Files:**
- Create: `app/app/Modules/Fulfillment/Support/FulfillmentErrorMapper.php`
- Test: `app/tests/Unit/Fulfillment/FulfillmentErrorMapperTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

use CMBcoreSeller\Modules\Fulfillment\Support\FulfillmentErrorMapper;

it('classifies cancelled as skipped with friendly reason', function () {
    $r = FulfillmentErrorMapper::classify(new RuntimeException('Vận đơn đã huỷ.'));
    expect($r['status'])->toBe('skipped');
    expect($r['reason'])->toBe('Đơn đã huỷ — bỏ qua.');
});

it('classifies generic exception as error and keeps technical detail', function () {
    $r = FulfillmentErrorMapper::classify(new RuntimeException('cURL timeout #28'));
    expect($r['status'])->toBe('error');
    expect($r['reason'])->toBeString()->not->toBe('');
    expect($r['technical'])->toContain('cURL timeout #28');
});
```

- [ ] **Step 2: Run, expect fail** — `cd app && ./vendor/bin/pest tests/Unit/Fulfillment/FulfillmentErrorMapperTest.php`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Support;

use Throwable;

/**
 * Map exception khi xử lý đơn → kết quả per-đơn cho bulk action: phân loại
 * `skipped` (đơn không hợp lệ, bỏ qua êm) vs `error` (lỗi vận hành, nên thử lại),
 * kèm câu tiếng Việt thân thiện (`reason`) và chi tiết kỹ thuật (`technical`).
 *
 * `reason` LUÔN trả; `technical` do controller quyết định có lộ ra response hay không
 * (theo `system_setting('fulfillment.expose_technical_errors')`).
 */
class FulfillmentErrorMapper
{
    /** Các thông điệp "đơn không hợp lệ" ⇒ skipped (không phải lỗi vận hành). */
    private const SKIP_NEEDLES = [
        'đã huỷ' => 'Đơn đã huỷ — bỏ qua.',
        'đã bàn giao' => 'Đơn đã bàn giao trước đó — bỏ qua.',
        'đã được đóng gói' => 'Đơn đã đóng gói trước đó — bỏ qua.',
        'âm tồn' => 'Đơn có SKU âm tồn — bỏ qua, cần nhập thêm hàng.',
        'không có vận đơn' => 'Đơn chưa được chuẩn bị hàng — bỏ qua.',
    ];

    /** @return array{status:string,reason:string,technical:string} */
    public static function classify(Throwable $e): array
    {
        $msg = $e->getMessage();
        $lower = mb_strtolower($msg);
        foreach (self::SKIP_NEEDLES as $needle => $friendly) {
            if (str_contains($lower, $needle)) {
                return ['status' => 'skipped', 'reason' => $friendly, 'technical' => self::technical($e)];
            }
        }

        return ['status' => 'error', 'reason' => self::friendlyError($msg), 'technical' => self::technical($e)];
    }

    private static function friendlyError(string $msg): string
    {
        $lower = mb_strtolower($msg);
        return match (true) {
            str_contains($lower, 'timeout') || str_contains($lower, 'curl') => 'Kết nối tới sàn/ĐVVC bị gián đoạn — vui lòng thử lại sau ít phút.',
            str_contains($lower, 'rate') || str_contains($lower, 'limit') => 'Sàn đang giới hạn truy cập — thử lại sau ít phút.',
            str_contains($lower, '50008') => 'Người mua đã yêu cầu huỷ một sản phẩm trong đơn — kiểm tra lại đơn trên sàn.',
            // Câu nghiệp vụ tiếng Việt đã rõ thì giữ nguyên (do service ném ra).
            preg_match('/[\x{00C0}-\x{1EF9}]/u', $msg) === 1 => $msg,
            default => 'Xử lý đơn thất bại — vui lòng thử lại hoặc liên hệ hỗ trợ.',
        };
    }

    private static function technical(Throwable $e): string
    {
        return sprintf('%s: %s', class_basename($e), $e->getMessage());
    }
}
```

- [ ] **Step 4: Run, expect pass** — `cd app && ./vendor/bin/pest tests/Unit/Fulfillment/FulfillmentErrorMapperTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Support/FulfillmentErrorMapper.php app/tests/Unit/Fulfillment/FulfillmentErrorMapperTest.php
git commit -m "feat(fulfillment): error mapper for per-order bulk results"
```

---

## Phase 2 — Backend: bulk pack/handover trả results[]

### Task 3: Helper trả results trong ShipmentController + pack

**Files:**
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php:227-243` (method `pack`)
- Test: `app/tests/Feature/Fulfillment/BulkPackResultsTest.php`

- [ ] **Step 1: Write failing test** (giả lập 1 đơn ok, 1 đã packed→skipped). Dựng dữ liệu theo factory hiện có (tham khảo test fulfillment sẵn có để biết helper tạo tenant/order/shipment). Khung:

```php
<?php

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
// ... imports + helper login tenant như các test Feature/Fulfillment khác

it('bulk pack returns per-order results with ok and skipped', function () {
    // Arrange: tenant + user có quyền fulfillment.ship; 2 shipment created + 1 đã packed
    [$tenant, $headers] = loginTenantUser(['fulfillment.ship']); // dùng helper sẵn có của bộ test
    $ok = makeShipment($tenant, status: Shipment::STATUS_CREATED);
    $already = makeShipment($tenant, status: Shipment::STATUS_PACKED);

    // Act
    $res = $this->withHeaders($headers)->postJson('/api/v1/shipments/pack', [
        'shipment_ids' => [$ok->id, $already->id],
    ]);

    // Assert
    $res->assertOk();
    $results = collect($res->json('data.results'));
    expect($results->firstWhere('id', $ok->id)['status'])->toBe('ok');
    expect($results->firstWhere('id', $already->id)['status'])->toBe('skipped');
    expect($res->json('data.packed'))->toBe(1); // giữ field count cũ để tương thích
});
```
> Lưu ý: dùng đúng helper dựng dữ liệu/headers của bộ test Feature/Fulfillment hiện có (đọc 1 file test cùng thư mục trước khi viết).

- [ ] **Step 2: Run, expect fail** — `cd app && ./vendor/bin/pest tests/Feature/Fulfillment/BulkPackResultsTest.php`
Expected: FAIL (`data.results` null).

- [ ] **Step 3: Thêm helper private + sửa `pack`.** Thêm import `use CMBcoreSeller\Modules\Fulfillment\Support\FulfillmentErrorMapper;` ở đầu controller. Thay thân method `pack` (giữ permission + validate):

```php
$data = $request->validate(['shipment_ids' => ['required', 'array', 'min:1', 'max:500'], 'shipment_ids.*' => ['integer']]);
[$results, $packed] = $this->runBulkShipment(
    array_map('intval', $data['shipment_ids']),
    fn (Shipment $s) => $this->service->markPacked($s, 'user', $request->user()->getKey()),
);

return response()->json(['data' => ['packed' => $packed, 'results' => $results]]);
```

Thêm method private dùng chung (đặt ngay dưới `handover`):

```php
/**
 * Chạy một thao tác đổi trạng thái trên danh sách shipment, trả kết quả per-đơn.
 * `$run` trả bool: true ⇒ ok; false ⇒ skipped (no-op idempotent). Exception ⇒ phân loại
 * qua FulfillmentErrorMapper (skipped/error). `technical` chỉ kèm khi admin bật.
 *
 * @param  list<int>  $shipmentIds
 * @param  callable(Shipment):bool  $run
 * @return array{0: list<array<string,mixed>>, 1: int}  [results, successCount]
 */
private function runBulkShipment(array $shipmentIds, callable $run): array
{
    $expose = (bool) system_setting('fulfillment.expose_technical_errors', (bool) config('app.debug'));
    $results = [];
    $ok = 0;
    foreach (Shipment::query()->whereIn('id', $shipmentIds)->get() as $shipment) {
        $row = ['id' => (int) $shipment->getKey()];
        try {
            if ($run($shipment)) {
                $row['status'] = 'ok';
                $ok++;
            } else {
                $row['status'] = 'skipped';
                $row['reason'] = 'Đơn đã ở trạng thái này — bỏ qua.';
            }
        } catch (\Throwable $e) {
            $c = FulfillmentErrorMapper::classify($e);
            $row['status'] = $c['status'];
            $row['reason'] = $c['reason'];
            if ($expose) {
                $row['technical'] = $c['technical'];
            }
        }
        $results[] = $row;
    }

    return [$results, $ok];
}
```

- [ ] **Step 4: Run, expect pass** — `cd app && ./vendor/bin/pest tests/Feature/Fulfillment/BulkPackResultsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php app/tests/Feature/Fulfillment/BulkPackResultsTest.php
git commit -m "feat(fulfillment): bulk pack returns per-order results (fix L1)"
```

### Task 4: handover dùng cùng helper

**Files:**
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php:245-261` (method `handover`)
- Test: thêm case vào `app/tests/Feature/Fulfillment/BulkPackResultsTest.php`

- [ ] **Step 1: Thêm test handover** (1 ok, 1 đã handed→skipped) tương tự Task 3, gọi `/api/v1/shipments/handover`, assert `data.results` + `data.handed_over`.

- [ ] **Step 2: Run, expect fail.**

- [ ] **Step 3: Sửa `handover`** dùng `runBulkShipment`:

```php
$data = $request->validate(['shipment_ids' => ['required', 'array', 'min:1', 'max:500'], 'shipment_ids.*' => ['integer']]);
[$results, $n] = $this->runBulkShipment(
    array_map('intval', $data['shipment_ids']),
    fn (Shipment $s) => $this->service->handover($s, 'system', $request->user()->getKey()),
);

return response()->json(['data' => ['handed_over' => $n, 'results' => $results]]);
```

- [ ] **Step 4: Run, expect pass.**

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php app/tests/Feature/Fulfillment/BulkPackResultsTest.php
git commit -m "feat(fulfillment): bulk handover returns per-order results"
```

---

## Phase 3 — Backend: Lazada auto-RTS per-shop

### Task 5: Cột auto_rts_after_print

**Files:**
- Create: `app/database/migrations/2026_05_24_120000_add_auto_rts_after_print_to_channel_accounts.php`
- Modify: `app/app/Modules/Channels/Models/ChannelAccount.php` (fillable + casts)
- Modify: `app/app/Modules/Channels/Http/Resources/ChannelAccountResource.php`

- [ ] **Step 1: Migration** (mirror messaging_enabled):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-shop Lazada: tự đẩy /order/rts sau khi in tem ("sẵn sàng giao luôn").
 * Default false ⇒ shop hiện có giữ luồng 3 bước cũ. Chỉ dùng cho provider lazada
 * (UI chỉ hiện với Lazada; controller chặn provider khác).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->boolean('auto_rts_after_print')->default(false)->after('messaging_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->dropColumn('auto_rts_after_print');
        });
    }
};
```

- [ ] **Step 2: Model** — thêm `'auto_rts_after_print'` vào `$fillable` và `'auto_rts_after_print' => 'boolean'` vào `casts()`. Thêm `@property bool $auto_rts_after_print` vào docblock.

- [ ] **Step 3: Resource** — thêm vào `toArray`:

```php
'auto_rts_after_print' => (bool) $this->auto_rts_after_print,
'auto_rts_available' => $this->provider === 'lazada',
```

- [ ] **Step 4: Migrate** — `cd app && php artisan migrate`
Expected: migration chạy OK.

- [ ] **Step 5: Commit**

```bash
git add app/database/migrations/2026_05_24_120000_add_auto_rts_after_print_to_channel_accounts.php app/app/Modules/Channels/Models/ChannelAccount.php app/app/Modules/Channels/Http/Resources/ChannelAccountResource.php
git commit -m "feat(channels): add auto_rts_after_print column for Lazada shops"
```

### Task 6: Endpoint toggle auto-RTS

**Files:**
- Modify: `app/app/Modules/Channels/Http/Controllers/ChannelAccountController.php` (thêm method `toggleAutoRts`, mirror `toggleMessaging` ~dòng 130-145)
- Modify: `app/routes/api.php` (thêm route cạnh route messaging toggle)
- Test: `app/tests/Feature/Channels/ChannelAutoRtsToggleTest.php`

- [ ] **Step 1: Write failing test** (Lazada → ok + audit; non-Lazada → 422; thiếu quyền → 403):

```php
<?php

it('toggles auto_rts for a lazada shop', function () {
    [$tenant, $headers] = loginTenantUser(['channels.manage']); // helper bộ test
    $shop = makeChannelAccount($tenant, provider: 'lazada');

    $res = $this->withHeaders($headers)->patchJson("/api/v1/channel-accounts/{$shop->id}/auto-rts", [
        'auto_rts_after_print' => true,
    ]);

    $res->assertOk();
    expect($res->json('data.auto_rts_after_print'))->toBeTrue();
    expect($shop->fresh()->auto_rts_after_print)->toBeTrue();
});

it('rejects auto_rts toggle for non-lazada shop', function () {
    [$tenant, $headers] = loginTenantUser(['channels.manage']);
    $shop = makeChannelAccount($tenant, provider: 'tiktok');

    $this->withHeaders($headers)->patchJson("/api/v1/channel-accounts/{$shop->id}/auto-rts", [
        'auto_rts_after_print' => true,
    ])->assertStatus(422);
});
```
> Đọc test messaging toggle sẵn có để dùng đúng helper tạo shop/headers.

- [ ] **Step 2: Run, expect fail** (404 route chưa có).

- [ ] **Step 3: Thêm method** vào `ChannelAccountController` (mirror toggleMessaging; tên route hiện có cho messaging xác định khi đọc file):

```php
/** PATCH /channel-accounts/{id}/auto-rts — bật/tắt auto-RTS-after-print (chỉ Lazada). */
public function toggleAutoRts(Request $request, int $id): JsonResponse
{
    abort_unless($request->user()?->can('channels.manage'), 403, 'Bạn không có quyền.');
    $account = ChannelAccount::query()->findOrFail($id);
    $data = $request->validate(['auto_rts_after_print' => ['required', 'boolean']]);
    abort_unless($account->provider === 'lazada', 422, 'Tính năng này chỉ áp dụng cho gian hàng Lazada.');

    $account->forceFill(['auto_rts_after_print' => $data['auto_rts_after_print']])->save();
    AuditLog::record('fulfillment.channel.auto_rts.toggle', $account, ['auto_rts_after_print' => $data['auto_rts_after_print']]);

    return response()->json(['data' => new ChannelAccountResource($account)]);
}
```
> Đảm bảo `use ...AuditLog;` đã có (messaging toggle dùng) và `JsonResponse` imported.

- [ ] **Step 4: Thêm route** trong `app/routes/api.php` cạnh route messaging toggle (cùng group `verified`/`tenant`):

```php
Route::patch('channel-accounts/{id}/auto-rts', [ChannelAccountController::class, 'toggleAutoRts']);
```
> Dùng đúng prefix/path khớp route messaging hiện có (đọc route messaging trước; có thể là `channel-accounts/{id}/messaging`).

- [ ] **Step 5: Run, expect pass** — `cd app && ./vendor/bin/pest tests/Feature/Channels/ChannelAutoRtsToggleTest.php`

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Channels/Http/Controllers/ChannelAccountController.php app/routes/api.php app/tests/Feature/Channels/ChannelAutoRtsToggleTest.php
git commit -m "feat(channels): endpoint to toggle Lazada auto-rts-after-print"
```

### Task 7: Hook auto-RTS trong markPrinted

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php` (thêm `autoReadyToShipAfterPrint`)
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/PrintJobController.php` (method `markPrinted`, ~dòng 78)
- Test: `app/tests/Feature/Fulfillment/AutoRtsAfterPrintTest.php`

- [ ] **Step 1: Write failing test** — Lazada shop bật cờ + shipment đã `created` (sau pack) + đơn Lazada ⇒ sau khi mark-printed (job label), shipment chuyển `packed`. Shop tắt cờ ⇒ vẫn `created`. Mock connector RTS để không gọi mạng thật (dựa theo cách test fulfillment hiện mock channel registry — đọc test có sẵn).

```php
it('auto-marks-packed lazada orders after printing a label when shop flag on', function () {
    [$tenant, $headers] = loginTenantUser(['fulfillment.print']);
    $shop = makeChannelAccount($tenant, provider: 'lazada', autoRts: true);
    $order = makeChannelOrder($tenant, $shop);
    $sh = makeShipment($tenant, order: $order, status: \CMBcoreSeller\Modules\Fulfillment\Models\Shipment::STATUS_CREATED, trackingNo: 'LZ1');
    $job = makeLabelPrintJob($tenant, shipments: [$sh]);

    $this->withHeaders($headers)->postJson("/api/v1/print-jobs/{$job->id}/mark-printed", ['copies' => 1])->assertOk();

    expect($sh->fresh()->status)->toBe(\CMBcoreSeller\Modules\Fulfillment\Models\Shipment::STATUS_PACKED);
});
```

- [ ] **Step 2: Run, expect fail.**

- [ ] **Step 3: Thêm method** vào `ShipmentService`:

```php
/**
 * Sau khi in tem (mark-printed) cho print job loại `label`: với mỗi shipment thuộc đơn Lazada
 * mà gian hàng bật `auto_rts_after_print`, tự gọi markPacked (vốn đẩy /order/rts + chuyển
 * trạng thái nội bộ). Bỏ qua êm nếu không đủ điều kiện; lỗi RTS không ném (markPacked tự gắn has_issue).
 *
 * @param  list<int>  $shipmentIds
 */
public function autoReadyToShipAfterPrint(array $shipmentIds, ?int $userId = null): void
{
    if ($shipmentIds === []) {
        return;
    }
    $shipments = Shipment::query()->whereIn('id', $shipmentIds)->with('order')->get();
    foreach ($shipments as $shipment) {
        $order = $shipment->order;
        if (! $order || ! $order->channel_account_id) {
            continue;
        }
        $account = ChannelAccount::query()->find($order->channel_account_id);
        if (! $account || $account->provider !== 'lazada' || ! $account->auto_rts_after_print) {
            continue;
        }
        try {
            $this->markPacked($shipment, 'system', $userId); // idempotent: đã packed/handed ⇒ no-op
        } catch (\Throwable $e) {
            Log::warning('shipment.auto_rts_after_print_failed', ['shipment' => $shipment->getKey(), 'error' => $e->getMessage()]);
        }
    }
}
```
> `ChannelAccount`, `Log`, `Shipment` đã được import trong ShipmentService (pushReadyToShipOnChannel dùng).

- [ ] **Step 4: Gọi từ `PrintJobController::markPrinted`** — sau khi `$print->markPrinted($job, $copies)`, nếu `$job->type === PrintJob::TYPE_LABEL` thì gọi auto-RTS với `shipment_ids` trả về. Inject `ShipmentService` vào controller (constructor hoặc method param). Khung:

```php
$res = $this->print->markPrinted($job, $copies);
if ($job->type === PrintJob::TYPE_LABEL) {
    $shipmentService->autoReadyToShipAfterPrint($res['shipment_ids'], $request->user()?->getKey());
}
return response()->json(['data' => [...]]); // giữ nguyên shape cũ
```
> Đọc `PrintJobController::markPrinted` hiện tại để giữ đúng response shape; chỉ chèn lời gọi auto-RTS.

- [ ] **Step 5: Run, expect pass.**

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShipmentService.php app/app/Modules/Fulfillment/Http/Controllers/PrintJobController.php app/tests/Feature/Fulfillment/AutoRtsAfterPrintTest.php
git commit -m "feat(fulfillment): Lazada auto-rts after printing label (per-shop, default off)"
```

---

## Phase 4 — Frontend: engine + progress modal

### Task 8: useBulkAction hook

**Files:**
- Create: `app/resources/js/lib/useBulkAction.ts`

- [ ] **Step 1: Implement** (chunked engine; không cần test runner — verify khi tích hợp):

```ts
import { useCallback, useRef, useState } from 'react';

export type BulkItemStatus = 'pending' | 'running' | 'ok' | 'skipped' | 'error';

export interface BulkItem {
    id: number;
    label: string;       // hiển thị: order_number / mã
    sub?: string;        // nền tảng / ĐVVC
    status: BulkItemStatus;
    reason?: string;
    technical?: string;
}

export interface BulkServerResult {
    id: number;
    status: 'ok' | 'skipped' | 'error';
    reason?: string;
    technical?: string;
}

/** Hàm chạy 1 chunk id → trả kết quả per-id từ backend. */
export type ChunkRunner = (ids: number[]) => Promise<BulkServerResult[]>;

const CHUNK_SIZE = 25;

export function useBulkAction() {
    const [title, setTitle] = useState('');
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<BulkItem[]>([]);
    const [running, setRunning] = useState(false);
    const runnerRef = useRef<ChunkRunner | null>(null);

    const apply = useCallback((results: BulkServerResult[]) => {
        setItems((prev) => {
            const byId = new Map(results.map((r) => [r.id, r]));
            return prev.map((it) => {
                const r = byId.get(it.id);
                return r ? { ...it, status: r.status, reason: r.reason, technical: r.technical } : it;
            });
        });
    }, []);

    const runIds = useCallback(async (ids: number[], runner: ChunkRunner) => {
        setRunning(true);
        for (let i = 0; i < ids.length; i += CHUNK_SIZE) {
            const chunk = ids.slice(i, i + CHUNK_SIZE);
            setItems((prev) => prev.map((it) => (chunk.includes(it.id) ? { ...it, status: 'running' } : it)));
            try {
                apply(await runner(chunk));
            } catch (e) {
                const msg = e instanceof Error ? e.message : 'Lỗi không xác định';
                setItems((prev) => prev.map((it) => (chunk.includes(it.id) ? { ...it, status: 'error', reason: msg } : it)));
            }
        }
        setRunning(false);
    }, [apply]);

    const start = useCallback(async (cfg: { title: string; items: Omit<BulkItem, 'status'>[]; runner: ChunkRunner }) => {
        runnerRef.current = cfg.runner;
        setTitle(cfg.title);
        setItems(cfg.items.map((it) => ({ ...it, status: 'pending' })));
        setOpen(true);
        await runIds(cfg.items.map((it) => it.id), cfg.runner);
    }, [runIds]);

    const retryErrors = useCallback(async () => {
        const runner = runnerRef.current;
        if (!runner) return;
        const ids = items.filter((it) => it.status === 'error').map((it) => it.id);
        if (ids.length) await runIds(ids, runner);
    }, [items, runIds]);

    return { title, open, items, running, start, retryErrors, close: () => setOpen(false) };
}
```

- [ ] **Step 2: Typecheck** — `cd app && npx tsc --noEmit` (chỉ kiểm file mới không lỗi cú pháp; baseline có thể đỏ ở nơi khác).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/useBulkAction.ts
git commit -m "feat(ui): useBulkAction chunked batch engine"
```

### Task 9: BulkProgressModal

**Files:**
- Create: `app/resources/js/components/BulkProgressModal.tsx`

- [ ] **Step 1: Implement** (icons từ @ant-design/icons; không emoji):

```tsx
import { Modal, Progress, List, Tag, Space, Typography, Button } from 'antd';
import { CheckCircleTwoTone, MinusCircleTwoTone, CloseCircleTwoTone, LoadingOutlined, ClockCircleOutlined } from '@ant-design/icons';
import type { BulkItem } from '@/lib/useBulkAction';

const ICON: Record<BulkItem['status'], React.ReactNode> = {
    pending: <ClockCircleOutlined style={{ color: '#bfbfbf' }} />,
    running: <LoadingOutlined style={{ color: '#1677ff' }} />,
    ok: <CheckCircleTwoTone twoToneColor="#52c41a" />,
    skipped: <MinusCircleTwoTone twoToneColor="#faad14" />,
    error: <CloseCircleTwoTone twoToneColor="#ff4d4f" />,
};

export function BulkProgressModal({ title, open, items, running, onRetry, onClose }: {
    title: string; open: boolean; items: BulkItem[]; running: boolean;
    onRetry: () => void; onClose: () => void;
}) {
    const done = items.filter((i) => i.status !== 'pending' && i.status !== 'running').length;
    const ok = items.filter((i) => i.status === 'ok').length;
    const skipped = items.filter((i) => i.status === 'skipped').length;
    const errors = items.filter((i) => i.status === 'error').length;
    const pct = items.length ? Math.round((done / items.length) * 100) : 0;

    return (
        <Modal title={`${title} — ${done}/${items.length}`} open={open} onCancel={running ? undefined : onClose}
            maskClosable={!running} closable={!running} width={640}
            footer={[
                <Space key="sum" style={{ marginRight: 'auto' }}>
                    <Tag color="success">Thành công {ok}</Tag>
                    <Tag color="warning">Bỏ qua {skipped}</Tag>
                    <Tag color="error">Lỗi {errors}</Tag>
                </Space>,
                <Button key="retry" onClick={onRetry} disabled={running || errors === 0}>Thử lại đơn lỗi</Button>,
                <Button key="close" type="primary" onClick={onClose} disabled={running}>Đóng</Button>,
            ]}>
            <Progress percent={pct} status={running ? 'active' : errors ? 'exception' : 'success'} />
            <List size="small" style={{ maxHeight: 360, overflow: 'auto', marginTop: 12 }} dataSource={items}
                renderItem={(it) => (
                    <List.Item>
                        <Space>
                            {ICON[it.status]}
                            <Typography.Text strong>#{it.label}</Typography.Text>
                            {it.sub && <Typography.Text type="secondary">{it.sub}</Typography.Text>}
                            {it.reason && <Typography.Text type={it.status === 'error' ? 'danger' : 'secondary'}>— {it.reason}</Typography.Text>}
                        </Space>
                        {it.technical && <Typography.Text code style={{ fontSize: 11 }}>{it.technical}</Typography.Text>}
                    </List.Item>
                )} />
        </Modal>
    );
}
```

- [ ] **Step 2: Typecheck** — `cd app && npx tsc --noEmit` (file mới không lỗi).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/components/BulkProgressModal.tsx
git commit -m "feat(ui): BulkProgressModal per-order progress"
```

### Task 10: fulfillment.tsx — kiểu results + mutation pack/handover

**Files:**
- Modify: `app/resources/js/lib/fulfillment.tsx`

- [ ] **Step 1: Thêm type** (cạnh các type khác):

```ts
export interface BulkActionResult { id: number; status: 'ok' | 'skipped' | 'error'; reason?: string; technical?: string }
```

- [ ] **Step 2: Cập nhật mutation `usePackShipments`/`useHandoverShipments`** để trả `results` (đọc shape hiện tại; backend giờ trả `{ packed, results }` / `{ handed_over, results }`). Đảm bảo hàm mutationFn trả `res.data.data` (đã gồm `results`).

- [ ] **Step 3: Typecheck + Commit**

```bash
git add app/resources/js/lib/fulfillment.tsx
git commit -m "feat(ui): bulk pack/handover mutations expose per-order results"
```

### Task 11: Wire engine + modal vào OrdersPage; bỏ disable theo status; pre-check in tem

**Files:**
- Modify: `app/resources/js/pages/OrdersPage.tsx`

- [ ] **Step 1: Đọc các handler bulk hiện tại** (`runBulkPrepare`, pack, handover, refetch ~dòng 183-360) và các điều kiện hiển thị nút (~611-626).

- [ ] **Step 2: Khởi tạo engine** trong component: `const bulk = useBulkAction();` và render `<BulkProgressModal ... />` (mirror chỗ render `PrintJobBar`).

- [ ] **Step 3: Đổi handler pack/handover** sang dùng `bulk.start({ title, items, runner })`. `runner(ids)` map ids→shipment_ids, gọi mutation, trả `res.results`. `items` build từ selected orders (label = order_number, sub = nền tảng). Bỏ điều kiện lọc `selPackable` để **luôn cho bấm** — server sẽ skip đơn không hợp lệ.

- [ ] **Step 4: Nút luôn hiện** trên work tab: bỏ các điều kiện `disabled`/ẩn theo status của nút Nhóm XỬ LÝ; giữ gate `useCan('fulfillment.ship')`. Gom 2 cụm nút (Nhóm In · Nhóm Xử lý) bằng `Space`/`Button.Group`.

- [ ] **Step 5: Pre-check in tem** — trong handler in tem, nếu selection có >1 `source` (nền tảng) hoặc >1 `carrier` → `Modal.warning({ title:'Không thể in tem', content:'Chỉ có thể in tem cho cùng một nền tảng và một ĐVVC. Hãy lọc theo từng nền tảng/ĐVVC rồi in.' })` và return (không gọi backend).

- [ ] **Step 6: Typecheck** — `cd app && npx tsc --noEmit`.

- [ ] **Step 7: Verify thủ công** (xem mục Manual Verification) rồi **Commit**

```bash
git add app/resources/js/pages/OrdersPage.tsx
git commit -m "feat(ui): order bulk actions via progress engine, always-clickable + label pre-check"
```

### Task 12: OrderActions per-row luôn hiện nút + Nhóm IN status-agnostic

**Files:**
- Modify: `app/resources/js/components/OrderProcessing.tsx`

- [ ] **Step 1: Đọc `OrderActions` (~103-252)** và các biến guard (`isWaiting`, `preShipment`, `shOpen`, ...).

- [ ] **Step 2: Nhóm IN** — bỏ điều kiện `shOpen` chặn nút in: cho hiện "In tem/phiếu", "In hoá đơn" kể cả khi đơn đã giao/huỷ/đang giao, miễn `canPrint`. Nếu không có tài liệu (`!sh?.label_url` cho tem) → vẫn cho bấm nhưng xử lý trả "bỏ qua" (hoặc disable riêng nút tem khi chắc chắn không có label, kèm tooltip "Đơn chưa có tem").

- [ ] **Step 3: Pre-check in tem** ở mức per-row không cần (1 đơn = 1 nền tảng); giữ confirm in lại khi `print_count>0`.

- [ ] **Step 4: Nhóm XỬ LÝ per-row** — giữ hành vi đơn lẻ (1 đơn) nhưng không ẩn theo status nếu vô lý; tối thiểu giữ logic hiện có cho per-row để giảm rủi ro (trọng tâm thay đổi là bulk). Chỉ chỉnh phần Nhóm IN status-agnostic.

- [ ] **Step 5: Typecheck + Verify + Commit**

```bash
git add app/resources/js/components/OrderProcessing.tsx
git commit -m "feat(ui): print group status-agnostic on per-row actions"
```

---

## Phase 5 — Frontend: Lazada toggle UI

### Task 13: useSetChannelAutoRts + Switch trong ShopCard

**Files:**
- Modify: `app/resources/js/lib/channels.tsx`
- Modify: `app/resources/js/pages/ChannelsPage.tsx`

- [ ] **Step 1: Đọc `useSetChannelMessaging` + type `ChannelAccount`** trong `channels.tsx`.

- [ ] **Step 2: Thêm field type** `auto_rts_after_print: boolean; auto_rts_available: boolean;` vào interface `ChannelAccount`.

- [ ] **Step 3: Thêm mutation** (mirror messaging):

```ts
export function useSetChannelAutoRts() {
    const tenantId = useTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ id, auto_rts_after_print }: { id: number; auto_rts_after_print: boolean }) =>
            tenantApi(tenantId).patch(`/channel-accounts/${id}/auto-rts`, { auto_rts_after_print }).then((r) => r.data.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['channel-accounts', tenantId] }),
    });
}
```
> Khớp đúng cách `useSetChannelMessaging` lấy `tenantId`/`queryKey`/`tenantApi` (đọc trước).

- [ ] **Step 4: Switch trong `ShopCard`** — thêm props `onToggleAutoRts`, `togglingAutoRts`; render khối (sau khối messaging) khi `canManage && account.auto_rts_available`:

```tsx
{canManage && account.auto_rts_available && (
    <div style={{ marginTop: 8, display: 'flex', alignItems: 'center', gap: 8 }}>
        <RocketOutlined style={{ color: '#8c8c8c' }} />
        <Typography.Text style={{ fontSize: 13 }}>Tự động gửi đơn cho ĐVVC sau khi in</Typography.Text>
        <Tooltip title="Sau khi in tem Lazada và bấm 'Đánh dấu đã in', đơn tự chuyển sang Sẵn sàng giao trên Lazada — không cần bấm 'Đã gói & sẵn sàng bàn giao'.">
            <Switch size="small" checked={account.auto_rts_after_print} loading={togglingAutoRts} onChange={onToggleAutoRts} aria-label="Tự động RTS sau khi in" />
        </Tooltip>
    </div>
)}
```
> Import `RocketOutlined`, `Tooltip` (Tooltip đã import). Wire trong `ChannelsPage` mirror `onToggleMessaging` với mutation mới + `message.success`.

- [ ] **Step 5: Typecheck + Verify + Commit**

```bash
git add app/resources/js/lib/channels.tsx app/resources/js/pages/ChannelsPage.tsx
git commit -m "feat(ui): Lazada per-shop auto-rts-after-print toggle"
```

---

## Phase 6 — Docs

### Task 14: Cập nhật tài liệu (Definition of Done)

**Files:**
- Modify: `docs/04-channels/order-processing.md`, `docs/05-api/endpoints.md`, `docs/03-domain/fulfillment-and-printing.md`

- [ ] **Step 1:** `endpoints.md` — thêm `PATCH /channel-accounts/{id}/auto-rts`; ghi chú `pack`/`handover` trả `results[]`.
- [ ] **Step 2:** `order-processing.md` — mô tả 2 nhóm nút, popup tiến trình, validate-và-bỏ-qua, in tem cấm trộn nền tảng/ĐVVC.
- [ ] **Step 3:** `fulfillment-and-printing.md` — auto-RTS-after-print (per-shop Lazada), admin toggle `expose_technical_errors`.
- [ ] **Step 4: Commit**

```bash
git add docs/04-channels/order-processing.md docs/05-api/endpoints.md docs/03-domain/fulfillment-and-printing.md
git commit -m "docs: order bulk actions, progress, Lazada auto-rts"
```

---

## Manual Verification (FE — không có test runner JS)

1. Tab "Chờ xử lý/Đang xử lý/Chờ bàn giao": chọn lô đơn trộn trạng thái → bấm thao tác Nhóm XỬ LÝ → popup hiện từng đơn ok/skipped(lý do)/error(lý do), tiến độ chạy, "Thử lại đơn lỗi" chỉ chạy lại đơn lỗi.
2. In tem chọn trộn 2 nền tảng/ĐVVC → bị chặn có thông báo, không gọi backend.
3. Đơn đã giao/huỷ → vẫn in lại được tem/hoá đơn (Nhóm IN); đơn không có tem → bỏ qua/disable kèm tooltip.
4. Gian hàng Lazada: hiện Switch "Tự động gửi đơn cho ĐVVC sau khi in"; shop khác không hiện. Bật → in tem + đánh dấu đã in → đơn sang "Chờ bàn giao".
5. Admin `/admin/settings` nhóm Fulfillment: bật "Hiện chi tiết lỗi kỹ thuật" → popup hiện dòng `technical`; tắt → chỉ câu tiếng Việt.

## Notes
- Baseline: 7 test GHN/fulfillment fail có sẵn trên `main`; không claim "xanh toàn cục" — chỉ xác nhận không thêm fail mới ở vùng đụng.
- BE chạy trong container: dùng `docker compose exec app ./vendor/bin/pest ...` nếu chạy ngoài host lỗi (đọc README/scripts để biết cách chạy đúng môi trường).
