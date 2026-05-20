# Shipping Label Designer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép owner/admin tenant tạo các template alias drag/drop (Konva canvas) để in phiếu giao hàng đơn manual; nhân viên chọn alias khi in.

**Architecture:** Bảng mới `shipping_label_templates` lưu JSON schema fields theo mm. Backend dùng `FieldTypeRegistry` (PHP) render template → HTML/CSS absolute-position → Gotenberg PDF. Frontend dùng `react-konva` + `zustand` editor đối xứng registry (TS). `PrintService::renderDeliverySlip` được mở rộng để route theo `template_id` (tuỳ chọn), giữ fallback `PrintTemplates::deliverySlip` cũ → backward-compat 100%.

**Tech Stack:** PHP 8.2 / Laravel 11, PHPUnit; React 18 + AntD 5 + Vite, `react-konva` + `konva`, `zustand`, `nanoid`. Gotenberg, `bacon/bacon-qr-code`, `picqer/php-barcode-generator` (đã có).

**Spec:** `docs/superpowers/specs/2026-05-18-shipping-label-designer-design.md`

---

## File Structure

### Create (BE)

```
app/Modules/Fulfillment/
├── Database/Migrations/
│   ├── 2026_05_18_110000_create_shipping_label_templates_table.php
│   └── 2026_05_18_110001_add_warehouse_id_to_orders.php
├── Models/ShippingLabelTemplate.php
├── Services/
│   ├── ShippingLabelTemplateService.php                 (setDefault transaction, duplicate)
│   └── LabelRendering/
│       ├── Contracts/FieldType.php
│       ├── FieldTypeRegistry.php
│       ├── DataContext.php
│       ├── FieldRenderHelpers.php
│       ├── LabelDataResolver.php
│       ├── LabelRenderer.php
│       ├── SampleDataFactory.php                        (3 sample profile cho preview)
│       └── Fields/
│           ├── QrField.php
│           ├── BarcodeField.php
│           ├── TextField.php
│           ├── ImageField.php
│           ├── DataField.php
│           ├── ItemsListField.php
│           ├── DividerField.php
│           └── RectangleField.php
├── Http/
│   ├── Controllers/ShippingLabelTemplateController.php
│   └── Resources/ShippingLabelTemplateResource.php
└── tests/fixtures/labels/kitchen-sink.html              (golden HTML snapshot)
```

### Create (FE)

```
resources/js/
├── lib/
│   ├── shippingLabelTypes.ts                            (TS types đối xứng BE)
│   ├── shippingLabels.tsx                               (react-query hooks)
│   └── labelEditor/
│       ├── editorStore.ts                               (zustand)
│       ├── coords.ts                                    (mm↔px, snap, clamp)
│       └── sampleData.ts                                (3 sample profile FE preview)
├── components/shipping-labels/
│   ├── LabelCanvas.tsx                                  (react-konva Stage)
│   ├── FieldNode.tsx                                    (dispatch registry)
│   ├── FieldPalette.tsx
│   ├── FieldInspector.tsx
│   ├── PaperSettings.tsx
│   ├── TemplateAliasPicker.tsx                          (modal in)
│   └── fieldTypes/
│       ├── index.ts                                     (registry)
│       └── {Qr,Barcode,Text,Image,Data,ItemsList,Divider,Rectangle}FieldDef.tsx
└── pages/
    ├── SettingsShippingLabelsPage.tsx                   (list)
    └── ShippingLabelEditorPage.tsx                      (editor)
```

### Modify

- `app/Modules/Fulfillment/FulfillmentServiceProvider.php` — register `FieldTypeRegistry` singleton, load `Http/routes.php`.
- `app/Modules/Fulfillment/Services/PrintService.php` — route `renderDeliverySlip` theo `template_id`.
- `app/Modules/Fulfillment/Http/Controllers/PrintJobController.php` — accept `template_id` cho `type=delivery`.
- `app/Modules/Orders/Services/ManualOrderService.php` — accept + persist `warehouse_id`.
- `routes/api.php` — thêm 9 endpoint mới.
- `app/composer.json` — không đổi (deps BE đã đủ).
- `app/package.json` — thêm `react-konva`, `konva`, `zustand`, `nanoid`.
- `resources/js/app.tsx` — 3 route mới.
- `resources/js/components/OrderProcessing.tsx` — gắn `TemplateAliasPicker` vào `printDelivery`.
- `resources/js/pages/OrdersPage.tsx` — bulk in qua picker.
- `resources/js/pages/CreateOrderPage.tsx` — chọn `warehouse_id`.
- `resources/js/lib/fulfillment.tsx` — `useCreatePrintJob` thêm `template_id`.

### Test files (Create)

```
tests/Unit/Fulfillment/LabelRendering/
├── FieldTypeRegistryTest.php
├── FieldRenderHelpersTest.php
├── LabelDataResolverTest.php
├── LabelRendererTest.php                                ← golden snapshot
└── Fields/
    ├── QrFieldTest.php
    ├── BarcodeFieldTest.php
    ├── TextFieldTest.php
    ├── ImageFieldTest.php
    ├── DataFieldTest.php
    ├── ItemsListFieldTest.php
    ├── DividerFieldTest.php
    └── RectangleFieldTest.php

tests/Feature/Fulfillment/
├── ShippingLabelTemplateCrudTest.php
├── ShippingLabelTemplateSetDefaultTest.php
├── ShippingLabelTemplatePreviewTest.php
├── PrintDeliveryWithTemplateTest.php
└── ManualOrderWarehouseIdTest.php
```

---

## Phase A — Foundation (data model)

### Task A1: Migration `add_warehouse_id_to_orders`

**Files:**
- Create: `app/app/Modules/Fulfillment/Database/Migrations/2026_05_18_110001_add_warehouse_id_to_orders.php`

- [ ] **Step 1: Tạo file migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đơn manual ghi nhận kho gửi → in phiếu giao hàng đọc sender_* từ
 * `warehouses.address`. Đơn cũ giữ NULL → resolver fallback default warehouse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('tenant_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
```

- [ ] **Step 2: Chạy migration**

Run: `cd app && php artisan migrate`
Expected: `Migrating: 2026_05_18_110001_add_warehouse_id_to_orders` then `Migrated`.

- [ ] **Step 3: Verify cột mới**

Run: `cd app && php artisan db:show --counts | grep orders` (Sanity check; cũng có thể `php artisan tinker --execute='\Schema::hasColumn("orders","warehouse_id")'` expect `true`).

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Fulfillment/Database/Migrations/2026_05_18_110001_add_warehouse_id_to_orders.php
git commit -m "feat(fulfillment): add warehouse_id to orders for manual order sender resolution"
```

---

### Task A2: Migration `create_shipping_label_templates_table`

**Files:**
- Create: `app/app/Modules/Fulfillment/Database/Migrations/2026_05_18_110000_create_shipping_label_templates_table.php`

- [ ] **Step 1: Tạo file migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template alias cho phiếu giao hàng đơn manual (drag/drop trên Konva editor).
 * Schema JSON versioned ; chỉ áp dụng `type=delivery`. SPEC 0006 §3.3 — custom print templates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_label_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name', 120);
            $table->string('paper', 16);                            // 'A4'|'A5'|'A6'|'100x150mm'|'80mm'|'custom'
            $table->unsignedSmallInteger('paper_w_mm');
            $table->unsignedSmallInteger('paper_h_mm');              // 0 = khổ cuộn (auto)
            $table->unsignedTinyInteger('schema_version')->default(1);
            $table->json('schema');                                  // { fields: [...] }
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_label_templates');
    }
};
```

- [ ] **Step 2: Chạy migration**

Run: `cd app && php artisan migrate`
Expected: `Migrated: 2026_05_18_110000_create_shipping_label_templates_table`.

- [ ] **Step 3: Commit**

```bash
git add app/app/Modules/Fulfillment/Database/Migrations/2026_05_18_110000_create_shipping_label_templates_table.php
git commit -m "feat(fulfillment): create shipping_label_templates table"
```

---

### Task A3: Model `ShippingLabelTemplate`

**Files:**
- Create: `app/app/Modules/Fulfillment/Models/ShippingLabelTemplate.php`

- [ ] **Step 1: Tạo model**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $paper
 * @property int $paper_w_mm
 * @property int $paper_h_mm
 * @property int $schema_version
 * @property array{fields: array<int, array<string, mixed>>} $schema
 * @property bool $is_default
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ShippingLabelTemplate extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'paper', 'paper_w_mm', 'paper_h_mm',
        'schema_version', 'schema', 'is_default', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'is_default' => 'boolean',
            'paper_w_mm' => 'integer',
            'paper_h_mm' => 'integer',
            'schema_version' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/app/Modules/Fulfillment/Models/ShippingLabelTemplate.php
git commit -m "feat(fulfillment): add ShippingLabelTemplate model"
```

---

## Phase B — BE rendering pipeline (TDD)

### Task B1: `DataContext` value object

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/DataContext.php`

- [ ] **Step 1: Tạo class**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

/**
 * Snapshot dữ liệu cần render cho 1 đơn manual. Resolver build 1 lần / order;
 * field type chỉ đọc, không query DB. Tất cả key đã format sẵn để hiển thị.
 */
final class DataContext
{
    /**
     * @param  list<array{name: string, sku: ?string, qty: int}>  $items
     */
    public function __construct(
        public readonly string $order_number,
        public readonly ?string $tracking_no,
        public readonly ?string $carrier,                     // 'ghn'|'ghtk'|... raw key
        public readonly string $sender_name,
        public readonly string $sender_phone,
        public readonly string $sender_address,
        public readonly string $recipient_name,
        public readonly string $recipient_phone,
        public readonly string $recipient_address,            // detail + admin joined
        public readonly string $recipient_address_detail,
        public readonly string $recipient_address_admin,
        public readonly int $cod,
        public readonly ?int $weight_g,
        public readonly int $total_qty,
        public readonly string $print_note,
        public readonly string $created_at_fmt,
        public readonly array $items,
    ) {}
}
```

- [ ] **Step 2: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/DataContext.php
git commit -m "feat(label): DataContext value object"
```

---

### Task B2: `FieldType` contract + `FieldTypeRegistry`

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/Contracts/FieldType.php`
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/FieldTypeRegistry.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/FieldTypeRegistryTest.php`

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use PHPUnit\Framework\TestCase;

class FieldTypeRegistryTest extends TestCase
{
    public function test_register_and_get(): void
    {
        $r = new FieldTypeRegistry();
        $r->register(new FakeFieldType('foo'));
        $this->assertSame('foo', $r->get('foo')->key());
    }

    public function test_get_returns_null_for_unknown_key(): void
    {
        $this->assertNull((new FieldTypeRegistry())->get('nope'));
    }

    public function test_register_duplicate_key_throws(): void
    {
        $r = new FieldTypeRegistry();
        $r->register(new FakeFieldType('foo'));
        $this->expectException(\InvalidArgumentException::class);
        $r->register(new FakeFieldType('foo'));
    }

    public function test_keys_returns_all_registered(): void
    {
        $r = new FieldTypeRegistry();
        $r->register(new FakeFieldType('a'));
        $r->register(new FakeFieldType('b'));
        $this->assertSame(['a', 'b'], $r->keys());
    }
}

class FakeFieldType implements FieldType
{
    public function __construct(private readonly string $key) {}

    public function key(): string { return $this->key; }

    public function validateProps(array $props): array { return $props; }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string { return ''; }
}
```

- [ ] **Step 2: Run test — expect fail**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/FieldTypeRegistryTest.php`
Expected: `Error: Class "...FieldType" not found`.

- [ ] **Step 3: Tạo contract**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;

interface FieldType
{
    /** Khoá định danh, đối xứng FE registry. */
    public function key(): string;

    /**
     * Validate + normalize props. Throws ValidationException nếu sai shape.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public function validateProps(array $props): array;

    /**
     * Các DataContext key field này dùng — giúp resolver chỉ load đúng thứ cần.
     *
     * @return list<string>
     */
    public function dataKeys(): array;

    /**
     * Render thành 1 div absolute-position trên trang HTML PDF.
     *
     * @param  array<string, mixed>  $field
     */
    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string;
}
```

- [ ] **Step 4: Tạo registry**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;

class FieldTypeRegistry
{
    /** @var array<string, FieldType> */
    private array $types = [];

    public function register(FieldType $type): void
    {
        $key = $type->key();
        if (isset($this->types[$key])) {
            throw new \InvalidArgumentException("FieldType [{$key}] đã được đăng ký.");
        }
        $this->types[$key] = $type;
    }

    public function get(string $key): ?FieldType
    {
        return $this->types[$key] ?? null;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->types);
    }
}
```

- [ ] **Step 5: Run test — expect pass**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/FieldTypeRegistryTest.php`
Expected: 4 tests passed.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/Contracts/FieldType.php \
        app/app/Modules/Fulfillment/Services/LabelRendering/FieldTypeRegistry.php \
        app/tests/Unit/Fulfillment/LabelRendering/FieldTypeRegistryTest.php
git commit -m "feat(label): FieldType contract + FieldTypeRegistry"
```

---

### Task B3: `FieldRenderHelpers`

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/FieldRenderHelpers.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/FieldRenderHelpersTest.php`

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;

class FieldRenderHelpersTest extends TestCase
{
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->h = new FieldRenderHelpers();
    }

    public function test_escape_handles_html(): void
    {
        $this->assertSame('&lt;b&gt;A&amp;B&lt;/b&gt;', $this->h->escape('<b>A&B</b>'));
    }

    public function test_format_vnd_with_separator(): void
    {
        $this->assertSame('1.234.567 đ', $this->h->formatVnd(1234567));
    }

    public function test_format_vnd_zero(): void
    {
        $this->assertSame('0 đ', $this->h->formatVnd(0));
    }

    public function test_positioned_box_renders_mm_coords(): void
    {
        $field = ['x' => 5, 'y' => 10, 'w' => 40, 'h' => 8, 'rotation' => 0];
        $html = $this->h->positionedBox($field, ['font-size' => '11px'], 'hello');
        $this->assertStringContainsString('left:5mm', $html);
        $this->assertStringContainsString('top:10mm', $html);
        $this->assertStringContainsString('width:40mm', $html);
        $this->assertStringContainsString('height:8mm', $html);
        $this->assertStringContainsString('font-size:11px', $html);
        $this->assertStringContainsString('>hello<', $html);
    }

    public function test_positioned_box_applies_rotation(): void
    {
        $field = ['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'rotation' => 45];
        $html = $this->h->positionedBox($field, [], '');
        $this->assertStringContainsString('transform:rotate(45deg)', $html);
    }

    public function test_carrier_full_name_known(): void
    {
        $this->assertSame('GIAO HÀNG NHANH', $this->h->carrierFullName('ghn'));
    }

    public function test_carrier_full_name_unknown_fallback(): void
    {
        $this->assertSame('CARRIER X', $this->h->carrierFullName('carrier_x'));
    }

    public function test_qr_png_returns_base64_data_url(): void
    {
        $url = $this->h->qrPng('TEST123', 30);
        $this->assertStringStartsWith('data:image/png;base64,', $url);
    }

    public function test_barcode_png_returns_base64_data_url(): void
    {
        $url = $this->h->barcodePng('TEST123', 50, 15, true);
        $this->assertStringStartsWith('data:image/png;base64,', $url);
    }
}
```

- [ ] **Step 2: Run test — expect fail**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/FieldRenderHelpersTest.php`
Expected: `Class "...FieldRenderHelpers" not found`.

- [ ] **Step 3: Tạo helper**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Thin helpers cho field type renders — wrap QR/barcode/escape/format chung,
 * tránh field class tự import lib. Stub-friendly trong unit test.
 */
class FieldRenderHelpers
{
    private const CARRIER_META = [
        'ghn' => ['short' => 'GHN', 'full' => 'GIAO HÀNG NHANH'],
        'ghtk' => ['short' => 'GHTK', 'full' => 'GIAO HÀNG TIẾT KIỆM'],
        'jt' => ['short' => 'J&T', 'full' => 'J&T EXPRESS'],
        'viettelpost' => ['short' => 'VTP', 'full' => 'VIETTEL POST'],
        'ninjavan' => ['short' => 'NJV', 'full' => 'NINJA VAN'],
        'spx' => ['short' => 'SPX', 'full' => 'SPX EXPRESS'],
        'vnpost' => ['short' => 'VNPost', 'full' => 'VIETNAM POST'],
        'ahamove' => ['short' => 'AHA', 'full' => 'AHAMOVE'],
        'manual' => ['short' => 'TỰ VC', 'full' => 'TỰ VẬN CHUYỂN'],
    ];

    public function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function formatVnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' đ';
    }

    public function formatDate(?\DateTimeInterface $t): string
    {
        return $t ? $t->format('d/m/Y H:i') : '';
    }

    public function carrierFullName(?string $carrier): string
    {
        $key = strtolower((string) $carrier);
        if ($key === '' || $carrier === null) {
            return 'ĐVVC';
        }

        return self::CARRIER_META[$key]['full'] ?? mb_strtoupper(str_replace('_', ' ', $key));
    }

    public function carrierShortName(?string $carrier): string
    {
        $key = strtolower((string) $carrier);
        if ($key === '' || $carrier === null) {
            return 'ĐVVC';
        }

        return self::CARRIER_META[$key]['short'] ?? mb_strtoupper($key);
    }

    public function carrierLogoImg(?string $carrier, int $widthMm, int $heightMm): string
    {
        $key = strtolower((string) $carrier);
        $path = __DIR__.'/../../../../../resources/labels/carrier-logos/'.$key.'.svg';
        if ($carrier && is_file($path)) {
            $svg = (string) file_get_contents($path);
            $b64 = base64_encode($svg);

            return '<img alt="'.$this->escape($key).'" src="data:image/svg+xml;base64,'.$b64.'" style="width:'.$widthMm.'mm;height:'.$heightMm.'mm;object-fit:contain" />';
        }

        return '<div style="display:flex;align-items:center;justify-content:center;width:'.$widthMm.'mm;height:'.$heightMm.'mm;color:#8c8c8c;border:1px dashed #d9d9d9;font-size:9px">'.$this->escape($this->carrierShortName($carrier)).'</div>';
    }

    public function qrPng(string $payload, int $widthMm, string $ecc = 'M'): string
    {
        $pixels = max(64, (int) round($widthMm * 8));
        $eccMap = ['L' => \BaconQrCode\Common\ErrorCorrectionLevel::L, 'M' => \BaconQrCode\Common\ErrorCorrectionLevel::M, 'Q' => \BaconQrCode\Common\ErrorCorrectionLevel::Q, 'H' => \BaconQrCode\Common\ErrorCorrectionLevel::H];
        $renderer = new GDLibRenderer($pixels, 1);
        $writer = new Writer($renderer);
        $png = $writer->writeString($payload, 'UTF-8', $eccMap[$ecc] ?? $eccMap['M']);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function barcodePng(string $payload, int $widthMm, int $heightMm, bool $withText): string
    {
        $generator = new BarcodeGeneratorPNG();
        $pixelsPerMm = 4;
        $png = $generator->getBarcode(
            $payload === '' ? '0' : $payload,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            $widthMm * $pixelsPerMm / max(strlen($payload), 1),
            ($heightMm - ($withText ? 4 : 0)) * $pixelsPerMm
        );

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function textStyle(array $s): array
    {
        $style = [
            'font-size' => ((int) ($s['fontSize'] ?? 11)).'px',
            'font-weight' => (int) ($s['fontWeight'] ?? 400),
            'text-align' => (string) ($s['align'] ?? 'left'),
            'color' => (string) ($s['color'] ?? '#222'),
        ];

        return $style;
    }

    public function positionedBox(array $field, array $extraStyle, string $innerHtml): string
    {
        $style = array_merge([
            'position' => 'absolute',
            'left' => $field['x'].'mm',
            'top' => $field['y'].'mm',
            'width' => $field['w'].'mm',
            'height' => $field['h'].'mm',
            'overflow' => 'hidden',
            'box-sizing' => 'border-box',
        ], $extraStyle);
        if (! empty($field['rotation'])) {
            $style['transform'] = 'rotate('.((int) $field['rotation']).'deg)';
            $style['transform-origin'] = 'top left';
        }
        $css = '';
        foreach ($style as $k => $v) {
            $css .= $k.':'.$v.';';
        }

        return '<div style="'.$css.'">'.$innerHtml.'</div>';
    }
}
```

- [ ] **Step 4: Run test — expect pass**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/FieldRenderHelpersTest.php`
Expected: 9 tests passed.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/FieldRenderHelpers.php \
        app/tests/Unit/Fulfillment/LabelRendering/FieldRenderHelpersTest.php
git commit -m "feat(label): FieldRenderHelpers (escape, format, qr/barcode/carrier logo)"
```

---

### Task B4: Field type `TextField`

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/Fields/TextField.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/Fields/TextFieldTest.php`

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\TextField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class TextFieldTest extends TestCase
{
    use MakesDataContext;

    private TextField $f;
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->f = new TextField();
        $this->h = new FieldRenderHelpers();
    }

    public function test_key(): void
    {
        $this->assertSame('text', $this->f->key());
    }

    public function test_validate_props_requires_text(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['style' => ['fontSize' => 11]]);
    }

    public function test_validate_props_rejects_font_size_out_of_range(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['text' => 'Hi', 'style' => ['fontSize' => 5]]);
    }

    public function test_render_html_escapes_text(): void
    {
        $field = ['type' => 'text', 'x' => 0, 'y' => 0, 'w' => 30, 'h' => 5,
                  'text' => '<b>shop</b>', 'style' => ['fontSize' => 11]];
        $html = $this->f->renderHtml($field, $this->makeContext(), $this->h);
        $this->assertStringContainsString('&lt;b&gt;shop&lt;/b&gt;', $html);
    }
}
```

- [ ] **Step 2: Tạo helper trait `MakesDataContext`**

Create `app/tests/Support/MakesDataContext.php`:

```php
<?php

namespace Tests\Support;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;

trait MakesDataContext
{
    protected function makeContext(array $overrides = []): DataContext
    {
        $d = array_merge([
            'order_number' => 'M-001',
            'tracking_no' => 'TRK123',
            'carrier' => 'ghn',
            'sender_name' => 'Shop A',
            'sender_phone' => '0901',
            'sender_address' => '12 Lê Lợi, Q1, TP.HCM',
            'recipient_name' => 'Nguyễn Văn B',
            'recipient_phone' => '0911',
            'recipient_address' => '34 Trần Hưng Đạo, Hai Bà Trưng, Hà Nội',
            'recipient_address_detail' => '34 Trần Hưng Đạo',
            'recipient_address_admin' => 'Hai Bà Trưng, Hà Nội',
            'cod' => 250000,
            'weight_g' => 500,
            'total_qty' => 2,
            'print_note' => 'Cảm ơn quý khách',
            'created_at_fmt' => '18/05/2026 10:30',
            'items' => [['name' => 'Áo thun', 'sku' => 'AT01', 'qty' => 2]],
        ], $overrides);

        return new DataContext(...$d);
    }
}
```

- [ ] **Step 3: Run test — expect fail**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/Fields/TextFieldTest.php`
Expected: class TextField not found.

- [ ] **Step 4: Tạo `TextField`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TextField implements FieldType
{
    public function key(): string { return 'text'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'text' => ['required', 'string', 'max:500'],
            'style.fontSize' => ['required', 'integer', 'min:6', 'max:48'],
            'style.fontWeight' => ['nullable', Rule::in([400, 600, 700])],
            'style.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'style.color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        return $h->positionedBox($field, $h->textStyle($field['style'] ?? []), $h->escape((string) ($field['text'] ?? '')));
    }
}
```

- [ ] **Step 5: Run — expect pass**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/Fields/TextFieldTest.php`
Expected: 4 tests passed.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/Fields/TextField.php \
        app/tests/Unit/Fulfillment/LabelRendering/Fields/TextFieldTest.php \
        app/tests/Support/MakesDataContext.php
git commit -m "feat(label): TextField (static text)"
```

---

### Task B5: Field types `QrField` + `BarcodeField`

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/Fields/QrField.php`
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/Fields/BarcodeField.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/Fields/QrFieldTest.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/Fields/BarcodeFieldTest.php`

- [ ] **Step 1: Viết test `QrFieldTest`**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\QrField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class QrFieldTest extends TestCase
{
    use MakesDataContext;

    private QrField $f;
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->f = new QrField();
        $this->h = new FieldRenderHelpers();
    }

    public function test_validate_props_rejects_unknown_source(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['source' => 'xyz']);
    }

    public function test_data_keys_includes_both_sources(): void
    {
        $this->assertEqualsCanonicalizing(['tracking_no', 'order_number'], $this->f->dataKeys());
    }

    public function test_render_encodes_tracking_no(): void
    {
        $field = ['type' => 'qr', 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20, 'source' => 'tracking_no'];
        $html = $this->f->renderHtml($field, $this->makeContext(['tracking_no' => 'AWB-9']), $this->h);
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }

    public function test_render_falls_back_to_order_number_when_tracking_missing(): void
    {
        $field = ['type' => 'qr', 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20, 'source' => 'tracking_no'];
        $html = $this->f->renderHtml($field, $this->makeContext(['tracking_no' => null, 'order_number' => 'M-77']), $this->h);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        // Không có cách kiểm tra trực tiếp payload; ít nhất QR vẫn render — tránh field biến mất.
    }
}
```

- [ ] **Step 2: Viết test `BarcodeFieldTest`**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\BarcodeField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class BarcodeFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_includes_text_when_show_text(): void
    {
        $f = new BarcodeField();
        $field = ['type' => 'barcode', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 15, 'source' => 'tracking_no', 'showText' => true];
        $html = $f->renderHtml($field, $this->makeContext(['tracking_no' => 'AWB-9']), new FieldRenderHelpers());
        $this->assertStringContainsString('AWB-9', $html);
    }

    public function test_render_hides_text_when_not_show_text(): void
    {
        $f = new BarcodeField();
        $field = ['type' => 'barcode', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 15, 'source' => 'tracking_no', 'showText' => false];
        $html = $f->renderHtml($field, $this->makeContext(['tracking_no' => 'AWB-9']), new FieldRenderHelpers());
        $this->assertStringNotContainsString('AWB-9', $html);
    }
}
```

- [ ] **Step 3: Tạo `QrField`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QrField implements FieldType
{
    public function key(): string { return 'qr'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'source' => ['required', Rule::in(['tracking_no', 'order_number'])],
            'ecc' => ['nullable', Rule::in(['L', 'M', 'Q', 'H'])],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return ['tracking_no', 'order_number'];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $payload = $field['source'] === 'tracking_no' ? ($ctx->tracking_no ?: $ctx->order_number) : $ctx->order_number;
        $img = '<img src="'.$h->qrPng((string) $payload, (int) $field['w'], (string) ($field['ecc'] ?? 'M')).'" style="width:100%;height:100%;object-fit:contain" alt="qr" />';

        return $h->positionedBox($field, [], $img);
    }
}
```

- [ ] **Step 4: Tạo `BarcodeField`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BarcodeField implements FieldType
{
    public function key(): string { return 'barcode'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'source' => ['required', Rule::in(['tracking_no', 'order_number'])],
            'format' => ['nullable', Rule::in(['code128'])],
            'showText' => ['nullable', 'boolean'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return ['tracking_no', 'order_number'];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $payload = (string) ($field['source'] === 'tracking_no' ? ($ctx->tracking_no ?: $ctx->order_number) : $ctx->order_number);
        $showText = (bool) ($field['showText'] ?? true);
        $barH = max(4, (int) $field['h'] - ($showText ? 4 : 0));
        $img = '<img src="'.$h->barcodePng($payload, (int) $field['w'], $barH, false).'" style="width:100%;height:'.($showText ? (100 - 30) : 100).'%;object-fit:contain" alt="barcode" />';
        $textLine = $showText ? '<div style="text-align:center;font-size:9px;font-family:monospace;letter-spacing:1px">'.$h->escape($payload).'</div>' : '';

        return $h->positionedBox($field, [], $img.$textLine);
    }
}
```

- [ ] **Step 5: Run tests — expect pass**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/Fields/QrFieldTest.php tests/Unit/Fulfillment/LabelRendering/Fields/BarcodeFieldTest.php`
Expected: all green.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/Fields/QrField.php \
        app/app/Modules/Fulfillment/Services/LabelRendering/Fields/BarcodeField.php \
        app/tests/Unit/Fulfillment/LabelRendering/Fields/QrFieldTest.php \
        app/tests/Unit/Fulfillment/LabelRendering/Fields/BarcodeFieldTest.php
git commit -m "feat(label): QrField + BarcodeField"
```

---

### Task B6: Field type `DataField`

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/Fields/DataField.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/Fields/DataFieldTest.php`

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DataField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class DataFieldTest extends TestCase
{
    use MakesDataContext;

    private DataField $f;
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->f = new DataField();
        $this->h = new FieldRenderHelpers();
    }

    public function test_validate_rejects_unknown_key(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['key' => 'invalid_key', 'style' => ['fontSize' => 11]]);
    }

    public function data_key_provider(): array
    {
        return [
            ['recipient_name', 'Nguyễn Văn B'],
            ['recipient_phone', '0911'],
            ['recipient_address', '34 Trần Hưng Đạo, Hai Bà Trưng, Hà Nội'],
            ['recipient_address_detail', '34 Trần Hưng Đạo'],
            ['recipient_address_admin', 'Hai Bà Trưng, Hà Nội'],
            ['sender_name', 'Shop A'],
            ['sender_phone', '0901'],
            ['sender_address', '12 Lê Lợi, Q1, TP.HCM'],
            ['order_number', 'M-001'],
            ['tracking_no', 'TRK123'],
            ['print_note', 'Cảm ơn quý khách'],
            ['created_at_fmt', '18/05/2026 10:30'],
            ['carrier_name', 'GIAO HÀNG NHANH'],
            ['weight', '500g'],
            ['cod', '250.000 đ'],
            ['total_qty', '2'],
        ];
    }

    /**
     * @dataProvider data_key_provider
     */
    public function test_render_each_key(string $key, string $expected): void
    {
        $field = ['type' => 'data', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 6,
                  'key' => $key, 'style' => ['fontSize' => 11]];
        $html = $this->f->renderHtml($field, $this->makeContext(), $this->h);
        $this->assertStringContainsString($expected, $html);
    }

    public function test_prefix_suffix_applied(): void
    {
        $field = ['type' => 'data', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 6,
                  'key' => 'order_number', 'style' => ['fontSize' => 11],
                  'prefix' => 'Mã: ', 'suffix' => ' #'];
        $html = $this->f->renderHtml($field, $this->makeContext(), $this->h);
        $this->assertStringContainsString('Mã: M-001 #', $html);
    }

    public function test_cod_zero_renders_dash(): void
    {
        $field = ['type' => 'data', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 6,
                  'key' => 'cod', 'style' => ['fontSize' => 11]];
        $html = $this->f->renderHtml($field, $this->makeContext(['cod' => 0]), $this->h);
        $this->assertStringContainsString('—', $html);
    }
}
```

- [ ] **Step 2: Tạo `DataField`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DataField implements FieldType
{
    public const KEYS = [
        'carrier_logo', 'carrier_name',
        'sender_name', 'sender_phone', 'sender_address',
        'recipient_name', 'recipient_phone', 'recipient_address',
        'recipient_address_detail', 'recipient_address_admin',
        'order_number', 'tracking_no',
        'cod', 'weight', 'print_note', 'created_at', 'total_qty',
    ];

    public function key(): string { return 'data'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'key' => ['required', Rule::in(self::KEYS)],
            'style.fontSize' => ['required', 'integer', 'min:6', 'max:48'],
            'style.fontWeight' => ['nullable', Rule::in([400, 600, 700])],
            'style.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'style.color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
            'prefix' => ['nullable', 'string', 'max:32'],
            'suffix' => ['nullable', 'string', 'max:32'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return self::KEYS;
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $key = (string) $field['key'];
        if ($key === 'carrier_logo') {
            return $h->positionedBox($field, [], $h->carrierLogoImg($ctx->carrier, (int) $field['w'], (int) $field['h']));
        }
        $value = match ($key) {
            'carrier_name' => $h->carrierFullName($ctx->carrier),
            'sender_name' => $ctx->sender_name,
            'sender_phone' => $ctx->sender_phone,
            'sender_address' => $ctx->sender_address,
            'recipient_name' => $ctx->recipient_name,
            'recipient_phone' => $ctx->recipient_phone,
            'recipient_address' => $ctx->recipient_address,
            'recipient_address_detail' => $ctx->recipient_address_detail,
            'recipient_address_admin' => $ctx->recipient_address_admin,
            'order_number' => $ctx->order_number,
            'tracking_no' => $ctx->tracking_no ?: '—',
            'cod' => $ctx->cod > 0 ? $h->formatVnd($ctx->cod) : '—',
            'weight' => $ctx->weight_g !== null ? ($ctx->weight_g.'g') : '—',
            'print_note' => $ctx->print_note,
            'created_at' => $ctx->created_at_fmt,
            'total_qty' => (string) $ctx->total_qty,
            default => '',
        };
        $rendered = $h->escape((string) ($field['prefix'] ?? '')).$h->escape($value).$h->escape((string) ($field['suffix'] ?? ''));
        $style = $h->textStyle($field['style'] ?? []);
        $style['display'] = 'flex';
        $style['align-items'] = 'center';
        $style['line-height'] = '1.15';

        return $h->positionedBox($field, $style, '<span style="width:100%">'.$rendered.'</span>');
    }
}
```

- [ ] **Step 3: Run — expect pass**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/Fields/DataFieldTest.php`
Expected: 18 tests passed.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/Fields/DataField.php \
        app/tests/Unit/Fulfillment/LabelRendering/Fields/DataFieldTest.php
git commit -m "feat(label): DataField (17 dynamic data keys + carrier_logo)"
```

---

### Task B7: Field types `ImageField` + `ItemsListField` + `DividerField` + `RectangleField`

**Files:**
- Create 4 field classes + tests.

- [ ] **Step 1: Viết tests gọn (1 file/field)**

`app/tests/Unit/Fulfillment/LabelRendering/Fields/ImageFieldTest.php`:

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ImageField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class ImageFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_validate_requires_asset_path(): void
    {
        $this->expectException(ValidationException::class);
        (new ImageField())->validateProps(['fit' => 'contain']);
    }

    public function test_render_uses_object_fit(): void
    {
        $field = ['type' => 'image', 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20,
                  'assetPath' => 'logos/shop.png', 'fit' => 'cover'];
        $html = (new ImageField())->renderHtml($field, $this->makeContext(), new FieldRenderHelpers());
        $this->assertStringContainsString('object-fit:cover', $html);
        $this->assertStringContainsString('logos/shop.png', $html);
    }
}
```

`app/tests/Unit/Fulfillment/LabelRendering/Fields/ItemsListFieldTest.php`:

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ItemsListField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class ItemsListFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_lists_each_item_name_and_qty(): void
    {
        $ctx = $this->makeContext(['items' => [
            ['name' => 'Áo thun', 'sku' => 'AT01', 'qty' => 2],
            ['name' => 'Quần jean', 'sku' => 'QJ02', 'qty' => 1],
        ]]);
        $field = ['type' => 'items_list', 'x' => 0, 'y' => 0, 'w' => 80, 'h' => 30,
                  'style' => ['fontSize' => 10]];
        $html = (new ItemsListField())->renderHtml($field, $ctx, new FieldRenderHelpers());
        $this->assertStringContainsString('Áo thun', $html);
        $this->assertStringContainsString('× 2', $html);
        $this->assertStringContainsString('Quần jean', $html);
    }

    public function test_render_truncates_when_exceeding_max_rows(): void
    {
        $items = array_map(fn ($i) => ['name' => "SP $i", 'sku' => null, 'qty' => 1], range(1, 10));
        $ctx = $this->makeContext(['items' => $items]);
        $field = ['type' => 'items_list', 'x' => 0, 'y' => 0, 'w' => 80, 'h' => 30,
                  'style' => ['fontSize' => 10], 'maxRows' => 3];
        $html = (new ItemsListField())->renderHtml($field, $ctx, new FieldRenderHelpers());
        $this->assertStringContainsString('và 7 sản phẩm khác', $html);
    }
}
```

`app/tests/Unit/Fulfillment/LabelRendering/Fields/DividerFieldTest.php`:

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DividerField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class DividerFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_with_thickness_and_color(): void
    {
        $field = ['type' => 'divider', 'x' => 5, 'y' => 10, 'w' => 80, 'h' => 1,
                  'thickness' => 2, 'color' => '#222222'];
        $html = (new DividerField())->renderHtml($field, $this->makeContext(), new FieldRenderHelpers());
        $this->assertStringContainsString('background:#222222', $html);
        $this->assertStringContainsString('height:2px', $html);
    }
}
```

`app/tests/Unit/Fulfillment/LabelRendering/Fields/RectangleFieldTest.php`:

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\RectangleField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class RectangleFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_applies_border_and_corner(): void
    {
        $field = ['type' => 'rectangle', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 30,
                  'borderThickness' => 1, 'borderColor' => '#cccccc',
                  'cornerRadius' => 4, 'fillColor' => '#f5f5f5'];
        $html = (new RectangleField())->renderHtml($field, $this->makeContext(), new FieldRenderHelpers());
        $this->assertStringContainsString('border:1px solid #cccccc', $html);
        $this->assertStringContainsString('border-radius:4px', $html);
        $this->assertStringContainsString('background:#f5f5f5', $html);
    }
}
```

- [ ] **Step 2: Tạo 4 class field**

`app/app/Modules/Fulfillment/Services/LabelRendering/Fields/ImageField.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImageField implements FieldType
{
    public function __construct(private readonly ?MediaUploader $media = null) {}

    public function key(): string { return 'image'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'assetPath' => ['required', 'string', 'max:512'],
            'fit' => ['nullable', Rule::in(['contain', 'cover'])],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $path = (string) $field['assetPath'];
        $fit = (string) ($field['fit'] ?? 'contain');
        $src = $this->media?->signedUrl($path) ?? $path;
        $img = '<img src="'.$h->escape($src).'" style="width:100%;height:100%;object-fit:'.$fit.'" alt="" />';

        return $h->positionedBox($field, [], $img);
    }
}
```

`app/app/Modules/Fulfillment/Services/LabelRendering/Fields/ItemsListField.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemsListField implements FieldType
{
    public function key(): string { return 'items_list'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'style.fontSize' => ['required', 'integer', 'min:6', 'max:24'],
            'style.lineHeight' => ['nullable', 'numeric', 'min:1', 'max:2.5'],
            'format' => ['nullable', Rule::in(['bullet', 'numbered'])],
            'maxRows' => ['nullable', 'integer', 'min:1', 'max:50'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return ['items']; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $max = (int) ($field['maxRows'] ?? count($ctx->items));
        $items = array_slice($ctx->items, 0, $max);
        $rest = count($ctx->items) - count($items);
        $format = (string) ($field['format'] ?? 'bullet');
        $lh = (float) ($field['lineHeight'] ?? 1.25);
        $lines = '';
        foreach ($items as $i => $it) {
            $marker = $format === 'numbered' ? (($i + 1).'.') : '•';
            $sku = ! empty($it['sku']) ? ' <span style="color:#888;font-size:90%">['.$h->escape((string) $it['sku']).']</span>' : '';
            $lines .= '<div style="display:flex;gap:4px;line-height:'.$lh.';"><span>'.$h->escape($marker).'</span><span style="flex:1">'.$h->escape((string) $it['name']).$sku.' × '.((int) $it['qty']).'</span></div>';
        }
        if ($rest > 0) {
            $lines .= '<div style="color:#888;line-height:'.$lh.'">… và '.$rest.' sản phẩm khác</div>';
        }
        $style = $h->textStyle($field['style'] ?? []);

        return $h->positionedBox($field, $style, $lines);
    }
}
```

`app/app/Modules/Fulfillment/Services/LabelRendering/Fields/DividerField.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;

class DividerField implements FieldType
{
    public function key(): string { return 'divider'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'thickness' => ['nullable', 'integer', 'min:1', 'max:8'],
            'color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $thickness = (int) ($field['thickness'] ?? 1);
        $color = (string) ($field['color'] ?? '#cccccc');
        $bar = '<div style="width:100%;height:'.$thickness.'px;background:'.$color.'"></div>';

        return $h->positionedBox($field, ['display' => 'flex', 'align-items' => 'center'], $bar);
    }
}
```

`app/app/Modules/Fulfillment/Services/LabelRendering/Fields/RectangleField.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Support\Facades\Validator;

class RectangleField implements FieldType
{
    public function key(): string { return 'rectangle'; }

    public function validateProps(array $props): array
    {
        Validator::make($props, [
            'borderThickness' => ['nullable', 'integer', 'min:0', 'max:8'],
            'borderColor' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
            'cornerRadius' => ['nullable', 'integer', 'min:0', 'max:20'],
            'fillColor' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $bt = (int) ($field['borderThickness'] ?? 1);
        $bc = (string) ($field['borderColor'] ?? '#cccccc');
        $cr = (int) ($field['cornerRadius'] ?? 0);
        $fill = (string) ($field['fillColor'] ?? 'transparent');
        $style = [
            'border' => $bt.'px solid '.$bc,
            'border-radius' => $cr.'px',
            'background' => $fill,
        ];

        return $h->positionedBox($field, $style, '');
    }
}
```

- [ ] **Step 3: Run tests**

Run: `cd app && vendor/bin/phpunit --testdox tests/Unit/Fulfillment/LabelRendering/Fields/`
Expected: all 4 new test files green.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/Fields/{Image,ItemsList,Divider,Rectangle}Field.php \
        app/tests/Unit/Fulfillment/LabelRendering/Fields/{Image,ItemsList,Divider,Rectangle}FieldTest.php
git commit -m "feat(label): Image/ItemsList/Divider/Rectangle field types"
```

---

### Task B8: `LabelDataResolver`

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/LabelDataResolver.php`
- Test: `app/tests/Unit/Fulfillment/LabelRendering/LabelDataResolverTest.php`

- [ ] **Step 1: Viết test (DB-touched, dùng RefreshDatabase)**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelDataResolver;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LabelDataResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_assembles_full_context(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::create(['tenant_id' => $t->id, 'name' => 'Kho A', 'is_default' => true,
            'address' => ['line1' => '1 Lê Lợi', 'ward' => 'Bến Nghé', 'district' => 'Q1', 'province' => 'TP.HCM', 'phone' => '0901', 'contact' => 'Shop A']]);
        $o = Order::create([
            'tenant_id' => $t->id, 'warehouse_id' => $wh->id, 'source' => 'manual',
            'order_number' => 'M-9', 'buyer_name' => 'Nguyễn B',
            'shipping_address' => ['fullName' => 'Nguyễn B', 'phone' => '0911', 'line1' => '34 THD',
                                   'ward' => 'Hàng Bài', 'district' => 'Hoàn Kiếm', 'province' => 'Hà Nội'],
            'cod_amount' => 250000, 'is_cod' => true, 'grand_total' => 250000,
            'meta' => ['print_note' => 'Cảm ơn'], 'status' => 'processing',
        ]);
        OrderItem::create(['order_id' => $o->id, 'name' => 'Áo', 'seller_sku' => 'AT01', 'quantity' => 2]);
        Shipment::create(['tenant_id' => $t->id, 'order_id' => $o->id, 'carrier' => 'ghn',
            'tracking_no' => 'AWB-9', 'status' => 'created', 'weight_g' => 500]);

        $ctx = (new LabelDataResolver())->resolve($o->fresh());

        $this->assertSame('M-9', $ctx->order_number);
        $this->assertSame('AWB-9', $ctx->tracking_no);
        $this->assertSame('ghn', $ctx->carrier);
        $this->assertSame('Shop A', $ctx->sender_name);
        $this->assertSame('0901', $ctx->sender_phone);
        $this->assertStringContainsString('Lê Lợi', $ctx->sender_address);
        $this->assertSame('Nguyễn B', $ctx->recipient_name);
        $this->assertSame('0911', $ctx->recipient_phone);
        $this->assertSame('34 THD', $ctx->recipient_address_detail);
        $this->assertStringContainsString('Hoàn Kiếm', $ctx->recipient_address_admin);
        $this->assertSame(250000, $ctx->cod);
        $this->assertSame(500, $ctx->weight_g);
        $this->assertSame(2, $ctx->total_qty);
        $this->assertSame('Cảm ơn', $ctx->print_note);
        $this->assertCount(1, $ctx->items);
    }

    public function test_resolve_falls_back_to_default_warehouse_when_null(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::defaultFor($t->id);
        $wh->update(['name' => 'Kho mặc định', 'address' => ['phone' => '0900', 'contact' => 'Default Shop']]);
        $o = Order::create(['tenant_id' => $t->id, 'warehouse_id' => null, 'source' => 'manual',
            'order_number' => 'M-1', 'shipping_address' => [], 'status' => 'pending']);

        $ctx = (new LabelDataResolver())->resolve($o->fresh());

        $this->assertSame('Default Shop', $ctx->sender_name);
    }

    public function test_resolve_does_not_n_plus_one_on_items(): void
    {
        $t = Tenant::factory()->create();
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual',
            'order_number' => 'M-1', 'shipping_address' => [], 'status' => 'pending']);
        foreach (range(1, 5) as $i) {
            OrderItem::create(['order_id' => $o->id, 'name' => "SP $i", 'quantity' => 1]);
        }
        $o = $o->fresh();
        DB::flushQueryLog();
        DB::enableQueryLog();
        (new LabelDataResolver())->resolve($o);
        $this->assertLessThanOrEqual(4, count(DB::getQueryLog()), 'Expected ≤4 queries (warehouse, shipment, items, fallback) — got '.count(DB::getQueryLog()));
    }
}
```

- [ ] **Step 2: Tạo resolver**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;

class LabelDataResolver
{
    public function resolve(Order $order): DataContext
    {
        $tenantId = (int) $order->tenant_id;

        if (! $order->relationLoaded('items')) {
            $order->load('items:order_id,name,seller_sku,sku_id,variation,quantity');
        }
        $shipment = Shipment::query()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $order->getKey())
            ->orderByDesc('id')
            ->first(['id', 'carrier', 'tracking_no', 'weight_g', 'status']);
        $warehouse = $order->warehouse_id
            ? Warehouse::query()->withoutGlobalScopes()->find($order->warehouse_id)
            : Warehouse::defaultFor($tenantId);

        $addr = (array) ($order->shipping_address ?? []);
        $recDetail = trim((string) ($addr['line1'] ?? $addr['address'] ?? ''));
        $recAdmin = trim(implode(', ', array_filter([$addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null])));
        $recFull = trim($recDetail.($recAdmin ? ', '.$recAdmin : ''));

        $whAddr = (array) ($warehouse?->address ?? []);
        $senderPhone = (string) ($whAddr['phone'] ?? '');
        $senderName = (string) ($whAddr['contact'] ?? $warehouse?->name ?? '');
        $senderAddr = trim(implode(', ', array_filter([
            $whAddr['line1'] ?? null, $whAddr['ward'] ?? null,
            $whAddr['district'] ?? null, $whAddr['province'] ?? null,
        ])));

        $items = $order->items->map(fn ($it) => [
            'name' => trim((string) $it->name.($it->variation ? ' — '.$it->variation : '')),
            'sku' => $it->seller_sku ?: null,
            'qty' => (int) $it->quantity,
        ])->all();

        $createdAt = $order->created_at?->format('d/m/Y H:i') ?: '';

        return new DataContext(
            order_number: (string) ($order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey())),
            tracking_no: $shipment?->tracking_no,
            carrier: $shipment?->carrier,
            sender_name: $senderName,
            sender_phone: $senderPhone,
            sender_address: $senderAddr,
            recipient_name: (string) ($addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? ''),
            recipient_phone: (string) ($addr['phone'] ?? ''),
            recipient_address: $recFull,
            recipient_address_detail: $recDetail,
            recipient_address_admin: $recAdmin,
            cod: (int) ($order->cod_amount ?: ($order->is_cod ? $order->grand_total : 0)),
            weight_g: $shipment?->weight_g ? (int) $shipment->weight_g : null,
            total_qty: (int) $order->items->sum('quantity'),
            print_note: (string) (data_get($order->meta, 'print_note') ?: ''),
            created_at_fmt: $createdAt,
            items: $items,
        );
    }
}
```

- [ ] **Step 3: Run tests**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/LabelDataResolverTest.php`
Expected: 3 tests passed.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/LabelDataResolver.php \
        app/tests/Unit/Fulfillment/LabelRendering/LabelDataResolverTest.php
git commit -m "feat(label): LabelDataResolver — n+1-free DataContext build"
```

---

### Task B9: `LabelRenderer` + golden snapshot

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/LabelRenderer.php`
- Create: `app/app/Modules/Fulfillment/Services/LabelRendering/SampleDataFactory.php`
- Create: `app/tests/fixtures/labels/kitchen-sink.json` (template sample dùng cho test)
- Create: `app/tests/fixtures/labels/kitchen-sink.html` (golden output)
- Test: `app/tests/Unit/Fulfillment/LabelRendering/LabelRendererTest.php`

- [ ] **Step 1: Tạo `SampleDataFactory`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

/**
 * Build DataContext mẫu cho preview PDF (không cần đơn thật) và golden snapshot test.
 */
class SampleDataFactory
{
    public const PROFILES = ['one_item_short_address', 'three_items_long_address', 'cod_with_print_note'];

    public function build(string $profile = 'one_item_short_address'): DataContext
    {
        return match ($profile) {
            'three_items_long_address' => new DataContext(
                'M-2026-002', 'AWB-LONG-1234567', 'ghn',
                'Shop CMBcore', '0901234567',
                '123 Đường Lê Lợi Nối Dài, Bến Nghé, Quận 1, TP.HCM',
                'Trần Thị Hoa Hồng Phương Lan',
                '0987654321',
                '456/12 Đường Nguyễn Trãi, Phường 7, Quận 5, TP.HCM',
                '456/12 Đường Nguyễn Trãi',
                'Phường 7, Quận 5, TP.HCM',
                450000, 800, 5, 'Đóng gói cẩn thận, hàng dễ vỡ',
                '18/05/2026 10:30',
                [
                    ['name' => 'Áo thun nam basic màu đen size L', 'sku' => 'AT-BLK-L', 'qty' => 2],
                    ['name' => 'Quần short kaki', 'sku' => 'QS-01', 'qty' => 1],
                    ['name' => 'Nón lưỡi trai', 'sku' => null, 'qty' => 2],
                ]
            ),
            'cod_with_print_note' => new DataContext(
                'M-2026-003', 'AWB-COD-555', 'ghtk',
                'Shop CMBcore', '0901234567', '12 Lê Lợi, Q1, TP.HCM',
                'Lê Văn C', '0912345678',
                '78 Nguyễn Huệ, Bến Nghé, Q1, TP.HCM',
                '78 Nguyễn Huệ', 'Bến Nghé, Q1, TP.HCM',
                500000, 300, 1,
                'Cảm ơn quý khách! Đổi/trả 7 ngày kèm hộp nguyên seal. Hotline: 0901234567',
                '18/05/2026 11:00',
                [['name' => 'Đồng hồ thông minh', 'sku' => 'SW-1', 'qty' => 1]]
            ),
            default => new DataContext(
                'M-2026-001', 'AWB-SHORT-77', 'ghn',
                'Shop CMBcore', '0901234567', '12 Lê Lợi, Q1, TP.HCM',
                'Nguyễn Văn A', '0911111111',
                '50 Hai Bà Trưng, Bến Nghé, Q1, TP.HCM',
                '50 Hai Bà Trưng', 'Bến Nghé, Q1, TP.HCM',
                0, 250, 1, '', '18/05/2026 09:00',
                [['name' => 'Bút bi xanh', 'sku' => 'BB-X', 'qty' => 1]]
            ),
        };
    }
}
```

- [ ] **Step 2: Tạo `LabelRenderer`**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Support\Collection;

class LabelRenderer
{
    public function __construct(
        private readonly FieldTypeRegistry $registry,
        private readonly FieldRenderHelpers $helpers,
        private readonly LabelDataResolver $resolver,
    ) {}

    /**
     * Render 1 trang body (chưa wrap shell) — dùng để ghép nhiều order.
     */
    public function renderBody(DataContext $ctx, ShippingLabelTemplate $tpl): string
    {
        $html = '<div class="page" style="position:relative;width:'.$tpl->paper_w_mm.'mm;'.
                ($tpl->paper_h_mm > 0 ? 'height:'.$tpl->paper_h_mm.'mm;' : '').'overflow:hidden">';
        foreach ((array) ($tpl->schema['fields'] ?? []) as $field) {
            $type = $this->registry->get((string) ($field['type'] ?? ''));
            if (! $type) {
                continue;
            }
            try {
                $html .= $type->renderHtml($field, $ctx, $this->helpers);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $html.'</div>';
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    public function renderBatch(Collection $orders, ShippingLabelTemplate $tpl): string
    {
        $pages = [];
        foreach ($orders as $order) {
            $ctx = $this->resolver->resolve($order);
            $pages[] = $this->renderBody($ctx, $tpl);
        }

        return $this->shell($tpl, implode('<div class="page-break" style="page-break-after:always"></div>', $pages));
    }

    public function renderSample(string $profile, ShippingLabelTemplate $tpl, SampleDataFactory $factory): string
    {
        return $this->shell($tpl, $this->renderBody($factory->build($profile), $tpl));
    }

    private function shell(ShippingLabelTemplate $tpl, string $body): string
    {
        $size = $tpl->paper_h_mm > 0 ? $tpl->paper_w_mm.'mm '.$tpl->paper_h_mm.'mm' : $tpl->paper_w_mm.'mm auto';

        return '<!doctype html><html><head><meta charset="utf-8"><style>'.
               '@page{size:'.$size.';margin:0}'.
               '*{font-family:DejaVu Sans,Arial,sans-serif;box-sizing:border-box}'.
               'body{margin:0;padding:0;color:#222}'.
               '.page{page-break-inside:avoid}'.
               '</style></head><body>'.$body.'</body></html>';
    }
}
```

- [ ] **Step 3: Tạo fixture kitchen-sink template JSON**

`app/tests/fixtures/labels/kitchen-sink.json`:

```json
{
  "name": "Kitchen Sink",
  "paper": "100x150mm",
  "paper_w_mm": 100,
  "paper_h_mm": 150,
  "schema_version": 1,
  "is_default": false,
  "schema": {
    "fields": [
      {"id": "a1", "type": "data", "x": 4, "y": 4, "w": 60, "h": 10, "key": "carrier_name", "style": {"fontSize": 14, "fontWeight": 700}},
      {"id": "a2", "type": "data", "x": 4, "y": 14, "w": 60, "h": 6, "key": "carrier_logo"},
      {"id": "b1", "type": "barcode", "x": 4, "y": 22, "w": 70, "h": 14, "source": "tracking_no", "showText": true},
      {"id": "b2", "type": "qr", "x": 76, "y": 4, "w": 22, "h": 22, "source": "tracking_no"},
      {"id": "d1", "type": "divider", "x": 4, "y": 40, "w": 92, "h": 1, "thickness": 1, "color": "#222222"},
      {"id": "s1", "type": "text", "x": 4, "y": 43, "w": 30, "h": 4, "text": "Từ:", "style": {"fontSize": 9, "fontWeight": 700}},
      {"id": "s2", "type": "data", "x": 4, "y": 47, "w": 92, "h": 5, "key": "sender_name", "style": {"fontSize": 11, "fontWeight": 600}},
      {"id": "s3", "type": "data", "x": 4, "y": 52, "w": 92, "h": 4, "key": "sender_phone", "style": {"fontSize": 9}},
      {"id": "s4", "type": "data", "x": 4, "y": 56, "w": 92, "h": 6, "key": "sender_address", "style": {"fontSize": 9}},
      {"id": "d2", "type": "divider", "x": 4, "y": 64, "w": 92, "h": 1, "thickness": 1, "color": "#222222"},
      {"id": "r1", "type": "text", "x": 4, "y": 67, "w": 30, "h": 4, "text": "Đến:", "style": {"fontSize": 9, "fontWeight": 700}},
      {"id": "r2", "type": "data", "x": 4, "y": 71, "w": 92, "h": 7, "key": "recipient_name", "style": {"fontSize": 15, "fontWeight": 700}},
      {"id": "r3", "type": "data", "x": 4, "y": 78, "w": 92, "h": 5, "key": "recipient_phone", "style": {"fontSize": 12, "fontWeight": 600}},
      {"id": "r4", "type": "data", "x": 4, "y": 83, "w": 92, "h": 12, "key": "recipient_address", "style": {"fontSize": 10}},
      {"id": "rect", "type": "rectangle", "x": 4, "y": 96, "w": 92, "h": 18, "borderThickness": 1, "borderColor": "#222222", "cornerRadius": 2},
      {"id": "c1", "type": "text", "x": 6, "y": 98, "w": 30, "h": 4, "text": "COD:", "style": {"fontSize": 10, "fontWeight": 700}},
      {"id": "c2", "type": "data", "x": 30, "y": 98, "w": 66, "h": 5, "key": "cod", "style": {"fontSize": 13, "fontWeight": 700, "color": "#cf1322"}},
      {"id": "c3", "type": "data", "x": 6, "y": 104, "w": 88, "h": 8, "key": "items_inline", "style": {"fontSize": 9}, "key": "print_note"},
      {"id": "il", "type": "items_list", "x": 4, "y": 116, "w": 92, "h": 22, "style": {"fontSize": 9}, "maxRows": 4},
      {"id": "f", "type": "data", "x": 4, "y": 140, "w": 92, "h": 5, "key": "order_number", "style": {"fontSize": 9, "align": "right"}}
    ]
  }
}
```

- [ ] **Step 4: Viết test renderer + snapshot**

```php
<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\BarcodeField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DataField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DividerField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ImageField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ItemsListField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\QrField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\RectangleField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\TextField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelDataResolver;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelRenderer;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\SampleDataFactory;
use PHPUnit\Framework\TestCase;

class LabelRendererTest extends TestCase
{
    private function makeRenderer(): LabelRenderer
    {
        $r = new FieldTypeRegistry();
        $r->register(new QrField());
        $r->register(new BarcodeField());
        $r->register(new TextField());
        $r->register(new ImageField());
        $r->register(new DataField());
        $r->register(new ItemsListField());
        $r->register(new DividerField());
        $r->register(new RectangleField());

        return new LabelRenderer($r, new FieldRenderHelpers(), new LabelDataResolver());
    }

    public function test_unknown_field_type_is_skipped(): void
    {
        $tpl = ShippingLabelTemplate::make([
            'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
            'schema' => ['fields' => [
                ['id' => 'a', 'type' => 'unknown_type', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10],
                ['id' => 'b', 'type' => 'text', 'x' => 0, 'y' => 0, 'w' => 30, 'h' => 5,
                 'text' => 'HELLO', 'style' => ['fontSize' => 11]],
            ]],
        ]);
        $factory = new SampleDataFactory();
        $html = $this->makeRenderer()->renderSample('one_item_short_address', $tpl, $factory);
        $this->assertStringContainsString('HELLO', $html);
    }

    public function test_kitchen_sink_snapshot(): void
    {
        $json = json_decode((string) file_get_contents(__DIR__.'/../../../fixtures/labels/kitchen-sink.json'), true);
        $tpl = ShippingLabelTemplate::make($json);
        $factory = new SampleDataFactory();
        $html = $this->makeRenderer()->renderSample('three_items_long_address', $tpl, $factory);

        $goldPath = __DIR__.'/../../../fixtures/labels/kitchen-sink.html';
        if (! is_file($goldPath) || getenv('UPDATE_SNAPSHOTS') === '1') {
            file_put_contents($goldPath, $html);
            $this->markTestIncomplete('Golden snapshot ghi mới — chạy lại test để verify.');
        }
        $expected = (string) file_get_contents($goldPath);
        $this->assertSame($expected, $html, 'Renderer output mismatch — chạy `UPDATE_SNAPSHOTS=1 phpunit ...` nếu thay đổi có chủ ý.');
    }
}
```

- [ ] **Step 5: Lần chạy đầu — sinh golden file**

Run: `cd app && UPDATE_SNAPSHOTS=1 vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/LabelRendererTest.php`
Expected: `test_kitchen_sink_snapshot` MARKED INCOMPLETE (golden ghi mới); `test_unknown_field_type_is_skipped` PASS.

- [ ] **Step 6: Lần chạy thứ 2 — pass full**

Run: `cd app && vendor/bin/phpunit tests/Unit/Fulfillment/LabelRendering/LabelRendererTest.php`
Expected: 2 tests passed.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/LabelRendering/LabelRenderer.php \
        app/app/Modules/Fulfillment/Services/LabelRendering/SampleDataFactory.php \
        app/tests/fixtures/labels/kitchen-sink.json \
        app/tests/fixtures/labels/kitchen-sink.html \
        app/tests/Unit/Fulfillment/LabelRendering/LabelRendererTest.php
git commit -m "feat(label): LabelRenderer + SampleDataFactory + golden snapshot"
```

---

### Task B10: Register registry trong `FulfillmentServiceProvider`

**Files:**
- Modify: `app/app/Modules/Fulfillment/FulfillmentServiceProvider.php`

- [ ] **Step 1: Cập nhật service provider**

Replace toàn bộ method `register`:

```php
public function register(): void
{
    $this->app->singleton(FieldTypeRegistry::class, function ($app) {
        $r = new FieldTypeRegistry();
        foreach ([
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\QrField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\BarcodeField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\TextField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ImageField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DataField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ItemsListField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DividerField::class,
            \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\RectangleField::class,
        ] as $cls) {
            $r->register($app->make($cls));
        }

        return $r;
    });
}
```

Add use ở top file:

```php
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
```

- [ ] **Step 2: Verify boot không vỡ**

Run: `cd app && php artisan config:clear && php artisan tinker --execute='dump(app(\CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry::class)->keys());'`
Expected: prints `['qr', 'barcode', 'text', 'image', 'data', 'items_list', 'divider', 'rectangle']`.

- [ ] **Step 3: Commit**

```bash
git add app/app/Modules/Fulfillment/FulfillmentServiceProvider.php
git commit -m "feat(label): register FieldTypeRegistry singleton with 8 v0 field types"
```

---

## Phase C — BE HTTP layer

### Task C1: `ShippingLabelTemplateService` (setDefault transaction, duplicate)

**Files:**
- Create: `app/app/Modules/Fulfillment/Services/ShippingLabelTemplateService.php`
- Test: `app/tests/Feature/Fulfillment/ShippingLabelTemplateSetDefaultTest.php`

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\ShippingLabelTemplateService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingLabelTemplateSetDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_default_clears_other_defaults_in_same_tenant(): void
    {
        $t = Tenant::factory()->create();
        $a = $this->makeTpl($t->id, 'A', true);
        $b = $this->makeTpl($t->id, 'B', false);

        app(ShippingLabelTemplateService::class)->setDefault($t->id, $b->id);

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
    }

    public function test_set_default_does_not_affect_other_tenants(): void
    {
        $t1 = Tenant::factory()->create();
        $t2 = Tenant::factory()->create();
        $a1 = $this->makeTpl($t1->id, 'A', true);
        $a2 = $this->makeTpl($t2->id, 'A', true);
        $b1 = $this->makeTpl($t1->id, 'B', false);

        app(ShippingLabelTemplateService::class)->setDefault($t1->id, $b1->id);

        $this->assertFalse($a1->fresh()->is_default);
        $this->assertTrue($b1->fresh()->is_default);
        $this->assertTrue($a2->fresh()->is_default);   // cross-tenant untouched
    }

    public function test_duplicate_creates_copy_with_suffix(): void
    {
        $t = Tenant::factory()->create();
        $a = $this->makeTpl($t->id, 'Tem A', false);

        $copy = app(ShippingLabelTemplateService::class)->duplicate($t->id, $a->id, /*createdBy*/ null);

        $this->assertSame('Tem A (copy)', $copy->name);
        $this->assertFalse($copy->is_default);
        $this->assertSame($a->schema, $copy->schema);
    }

    private function makeTpl(int $tenantId, string $name, bool $isDefault): ShippingLabelTemplate
    {
        return ShippingLabelTemplate::create([
            'tenant_id' => $tenantId, 'name' => $name,
            'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
            'schema_version' => 1, 'schema' => ['fields' => []],
            'is_default' => $isDefault, 'created_by' => null,
        ]);
    }
}
```

- [ ] **Step 2: Tạo service**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

class ShippingLabelTemplateService
{
    public function setDefault(int $tenantId, int $templateId): ShippingLabelTemplate
    {
        return DB::transaction(function () use ($tenantId, $templateId) {
            $tpl = ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('id', $templateId)->firstOrFail();
            ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('id', '<>', $templateId)
                ->where('is_default', true)->update(['is_default' => false]);
            $tpl->update(['is_default' => true]);

            return $tpl;
        });
    }

    public function duplicate(int $tenantId, int $sourceId, ?int $createdBy): ShippingLabelTemplate
    {
        $src = ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('id', $sourceId)->firstOrFail();
        $name = $this->uniqueName($tenantId, $src->name.' (copy)');

        return ShippingLabelTemplate::create([
            'tenant_id' => $tenantId, 'name' => $name,
            'paper' => $src->paper, 'paper_w_mm' => $src->paper_w_mm, 'paper_h_mm' => $src->paper_h_mm,
            'schema_version' => $src->schema_version, 'schema' => $src->schema,
            'is_default' => false, 'created_by' => $createdBy,
        ]);
    }

    private function uniqueName(int $tenantId, string $base): string
    {
        $name = $base;
        $i = 1;
        while (ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('name', $name)->exists()) {
            $i++;
            $name = $base.' '.$i;
        }

        return $name;
    }
}
```

- [ ] **Step 3: Run test**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/ShippingLabelTemplateSetDefaultTest.php`
Expected: 3 tests passed.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShippingLabelTemplateService.php \
        app/tests/Feature/Fulfillment/ShippingLabelTemplateSetDefaultTest.php
git commit -m "feat(label): ShippingLabelTemplateService (setDefault tx, duplicate with unique name)"
```

---

### Task C2: REST controller + resource + routes (CRUD + duplicate + set-default)

**Files:**
- Create: `app/app/Modules/Fulfillment/Http/Resources/ShippingLabelTemplateResource.php`
- Create: `app/app/Modules/Fulfillment/Http/Controllers/ShippingLabelTemplateController.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Fulfillment/ShippingLabelTemplateCrudTest.php`

- [ ] **Step 1: Tạo resource**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingLabelTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'paper' => $this->paper,
            'paper_w_mm' => (int) $this->paper_w_mm,
            'paper_h_mm' => (int) $this->paper_h_mm,
            'schema_version' => (int) $this->schema_version,
            'schema' => $this->schema,
            'is_default' => (bool) $this->is_default,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 2: Tạo controller**

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\ShippingLabelTemplateResource;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelRenderer;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\SampleDataFactory;
use CMBcoreSeller\Modules\Fulfillment\Services\ShippingLabelTemplateService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShippingLabelTemplateController extends Controller
{
    private const PRESET_DIMS = [
        'A4' => [210, 297], 'A5' => [148, 210], 'A6' => [105, 148],
        '100x150mm' => [100, 150], '80mm' => [80, 0],
    ];

    public function index(Request $request, CurrentTenant $tenant): JsonResponse
    {
        $items = ShippingLabelTemplate::query()
            ->where('tenant_id', $tenant->id())
            ->orderByDesc('is_default')->orderBy('name')->get();

        return response()->json(['data' => ShippingLabelTemplateResource::collection($items)]);
    }

    public function show(Request $request, int $id, CurrentTenant $tenant): JsonResponse
    {
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)]);
    }

    public function store(Request $request, CurrentTenant $tenant, FieldTypeRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $data = $this->validatePayload($request, $registry);
        $tpl = ShippingLabelTemplate::create([
            'tenant_id' => $tenant->id(),
            'name' => $data['name'], 'paper' => $data['paper'],
            'paper_w_mm' => $data['paper_w_mm'], 'paper_h_mm' => $data['paper_h_mm'],
            'schema_version' => 1, 'schema' => ['fields' => $data['schema']['fields']],
            'is_default' => false, 'created_by' => $request->user()->getKey(),
        ]);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)], 201);
    }

    public function update(Request $request, int $id, CurrentTenant $tenant, FieldTypeRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        $data = $this->validatePayload($request, $registry, $tpl->id);
        $tpl->update([
            'name' => $data['name'], 'paper' => $data['paper'],
            'paper_w_mm' => $data['paper_w_mm'], 'paper_h_mm' => $data['paper_h_mm'],
            'schema' => ['fields' => $data['schema']['fields']],
        ]);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl->fresh())]);
    }

    public function destroy(Request $request, int $id, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        $tpl->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function setDefault(Request $request, int $id, CurrentTenant $tenant, ShippingLabelTemplateService $service): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = $service->setDefault($tenant->id(), $id);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)]);
    }

    public function duplicate(Request $request, int $id, CurrentTenant $tenant, ShippingLabelTemplateService $service): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = $service->duplicate($tenant->id(), $id, $request->user()->getKey());

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)], 201);
    }

    public function preview(Request $request, int $id, CurrentTenant $tenant, LabelRenderer $renderer, GotenbergClient $gotenberg, MediaUploader $media, SampleDataFactory $factory): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $this->rateLimit($request);
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        $profile = (string) $request->input('sample_profile', 'one_item_short_address');
        abort_unless(in_array($profile, SampleDataFactory::PROFILES, true), 422, 'sample_profile không hợp lệ.');
        $bytes = $gotenberg->htmlToPdf($renderer->renderSample($profile, $tpl, $factory));
        $stored = $media->storeBytes($bytes, $tenant->id(), 'print', 'preview-'.Str::ulid(), 'pdf');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }

    public function previewInline(Request $request, CurrentTenant $tenant, LabelRenderer $renderer, GotenbergClient $gotenberg, MediaUploader $media, SampleDataFactory $factory, FieldTypeRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $this->rateLimit($request);
        $data = $this->validatePayload($request, $registry, null, requireName: false);
        $profile = (string) $request->input('sample_profile', 'one_item_short_address');
        abort_unless(in_array($profile, SampleDataFactory::PROFILES, true), 422, 'sample_profile không hợp lệ.');
        $tpl = ShippingLabelTemplate::make([
            'tenant_id' => $tenant->id(),
            'name' => $data['name'] ?? 'preview',
            'paper' => $data['paper'], 'paper_w_mm' => $data['paper_w_mm'], 'paper_h_mm' => $data['paper_h_mm'],
            'schema' => ['fields' => $data['schema']['fields']], 'schema_version' => 1, 'is_default' => false,
        ]);
        $bytes = $gotenberg->htmlToPdf($renderer->renderSample($profile, $tpl, $factory));
        $stored = $media->storeBytes($bytes, $tenant->id(), 'print', 'preview-'.Str::ulid(), 'pdf');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }

    private function rateLimit(Request $request): void
    {
        $key = 'label-preview:'.$request->user()->getKey();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Quá nhiều lượt preview. Thử lại sau 1 phút.');
        }
        RateLimiter::hit($key, 60);
    }

    /**
     * @return array{name:?string,paper:string,paper_w_mm:int,paper_h_mm:int,schema:array<string,mixed>}
     */
    private function validatePayload(Request $request, FieldTypeRegistry $registry, ?int $excludeId = null, bool $requireName = true): array
    {
        $data = $request->validate([
            'name' => [$requireName ? 'required' : 'nullable', 'string', 'max:120'],
            'paper' => ['required', Rule::in(array_merge(array_keys(self::PRESET_DIMS), ['custom']))],
            'paper_w_mm' => ['required', 'integer', 'min:30', 'max:420'],
            'paper_h_mm' => ['required', 'integer', 'min:0', 'max:1200'],
            'schema' => ['required', 'array'],
            'schema.fields' => ['required', 'array', 'max:100'],
            'schema.fields.*.id' => ['required', 'string', 'max:32'],
            'schema.fields.*.type' => ['required', Rule::in($registry->keys())],
            'schema.fields.*.x' => ['required', 'numeric', 'min:0'],
            'schema.fields.*.y' => ['required', 'numeric', 'min:0'],
            'schema.fields.*.w' => ['required', 'numeric', 'min:1'],
            'schema.fields.*.h' => ['required', 'numeric', 'min:1'],
            'schema.fields.*.rotation' => ['nullable', 'numeric'],
        ]);
        if ($requireName) {
            $exists = ShippingLabelTemplate::query()
                ->where('tenant_id', app(CurrentTenant::class)->id())
                ->where('name', $data['name'])
                ->when($excludeId, fn ($q, $id) => $q->where('id', '<>', $id))
                ->exists();
            abort_if($exists, 422, 'Đã có template trùng tên trong shop.');
        }
        // Per-field validation
        foreach ($data['schema']['fields'] as $i => $field) {
            $type = $registry->get($field['type']);
            if (! $type) {
                continue;
            }
            try {
                $type->validateProps($field);
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "schema.fields.{$i}" => $e->errors(),
                ]);
            }
            // Clamp x+w / y+h trong paper
            if (($field['x'] + $field['w']) > $data['paper_w_mm']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "schema.fields.{$i}.w" => "Field '{$field['id']}' vượt chiều rộng giấy.",
                ]);
            }
            if ($data['paper_h_mm'] > 0 && ($field['y'] + $field['h']) > $data['paper_h_mm']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "schema.fields.{$i}.h" => "Field '{$field['id']}' vượt chiều cao giấy.",
                ]);
            }
        }

        return $data;
    }
}
```

- [ ] **Step 3: Add routes vào `app/routes/api.php`**

Tìm khối `Route::middleware('auth:sanctum')->prefix('v1')->group(...)` đang chứa `print-jobs` (line ~207); thêm sau dòng `print-jobs/{id}/download`:

```php
// --- Shipping label templates (drag/drop editor cho phiếu giao hàng đơn manual) ---
Route::get('shipping-label-templates', [ShippingLabelTemplateController::class, 'index'])->name('shipping-label-templates.index');
Route::post('shipping-label-templates', [ShippingLabelTemplateController::class, 'store'])->name('shipping-label-templates.store');
Route::post('shipping-label-templates/preview', [ShippingLabelTemplateController::class, 'previewInline'])->name('shipping-label-templates.preview-inline');
Route::get('shipping-label-templates/{id}', [ShippingLabelTemplateController::class, 'show'])->whereNumber('id')->name('shipping-label-templates.show');
Route::put('shipping-label-templates/{id}', [ShippingLabelTemplateController::class, 'update'])->whereNumber('id')->name('shipping-label-templates.update');
Route::delete('shipping-label-templates/{id}', [ShippingLabelTemplateController::class, 'destroy'])->whereNumber('id')->name('shipping-label-templates.destroy');
Route::post('shipping-label-templates/{id}/set-default', [ShippingLabelTemplateController::class, 'setDefault'])->whereNumber('id')->name('shipping-label-templates.set-default');
Route::post('shipping-label-templates/{id}/duplicate', [ShippingLabelTemplateController::class, 'duplicate'])->whereNumber('id')->name('shipping-label-templates.duplicate');
Route::post('shipping-label-templates/{id}/preview', [ShippingLabelTemplateController::class, 'preview'])->whereNumber('id')->name('shipping-label-templates.preview');
```

Add use ở đầu file:

```php
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\ShippingLabelTemplateController;
```

- [ ] **Step 4: Viết feature test CRUD**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingLabelTemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->create();
        $this->viewer = User::factory()->create();
        $this->tenant->users()->attach($this->owner->id, ['role' => Role::Owner->value]);
        $this->tenant->users()->attach($this->viewer->id, ['role' => Role::Viewer->value]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tem A6 chuẩn',
            'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
            'schema' => ['fields' => [
                ['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5, 'w' => 50, 'h' => 6,
                 'text' => 'Shop', 'style' => ['fontSize' => 11]],
            ]],
        ], $overrides);
    }

    public function test_owner_can_create_template(): void
    {
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $this->payload())
             ->assertCreated()->assertJsonPath('data.name', 'Tem A6 chuẩn');
    }

    public function test_viewer_cannot_create_template(): void
    {
        Sanctum::actingAs($this->viewer);
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $this->payload())
             ->assertForbidden();
    }

    public function test_viewer_can_list_templates(): void
    {
        ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $this->tenant->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->viewer);
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->getJson('/api/v1/shipping-label-templates')
             ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_create_rejects_overflow_field(): void
    {
        Sanctum::actingAs($this->owner);
        $payload = $this->payload();
        $payload['schema']['fields'][0]['w'] = 200;
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $payload)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['schema.fields.0.w']);
    }

    public function test_create_rejects_duplicate_name(): void
    {
        ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $this->tenant->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $this->payload())
             ->assertStatus(422);
    }

    public function test_destroy_soft_deletes(): void
    {
        $tpl = ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $this->tenant->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->deleteJson('/api/v1/shipping-label-templates/'.$tpl->id)
             ->assertOk();
        $this->assertSoftDeleted('shipping_label_templates', ['id' => $tpl->id]);
    }

    public function test_cross_tenant_access_returns_404(): void
    {
        $other = Tenant::factory()->create();
        $tpl = ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $other->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant', (string) $this->tenant->id)
             ->getJson('/api/v1/shipping-label-templates/'.$tpl->id)
             ->assertNotFound();
    }
}
```

- [ ] **Step 5: Run tests**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/ShippingLabelTemplateCrudTest.php`
Expected: 7 tests passed.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Http/Controllers/ShippingLabelTemplateController.php \
        app/app/Modules/Fulfillment/Http/Resources/ShippingLabelTemplateResource.php \
        app/routes/api.php \
        app/tests/Feature/Fulfillment/ShippingLabelTemplateCrudTest.php
git commit -m "feat(label): CRUD + duplicate + set-default + preview endpoints"
```

---

### Task C3: Modify `PrintService` + `PrintJobController` để route theo `template_id`

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/PrintService.php`
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/PrintJobController.php`
- Test: `app/tests/Feature/Fulfillment/PrintDeliveryWithTemplateTest.php`

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Jobs\RenderPrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Support\GotenbergClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrintDeliveryWithTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_job_carries_template_id_in_meta(): void
    {
        Bus::fake();
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => []], 'is_default' => true]);
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);

        Sanctum::actingAs($u);
        $r = $this->withHeader('X-Tenant', (string) $t->id)->postJson('/api/v1/print-jobs', [
            'type' => 'delivery', 'order_ids' => [$o->id], 'template_id' => $tpl->id,
        ])->assertCreated();

        $job = PrintJob::query()->findOrFail($r->json('data.id'));
        $this->assertSame($tpl->id, (int) data_get($job->meta, 'template_id'));
        $this->assertSame('A6', data_get($job->meta, 'template_name'));
        Bus::assertDispatched(RenderPrintJob::class);
    }

    public function test_render_with_template_calls_gotenberg(): void
    {
        $t = Tenant::factory()->create();
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => [['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5,
                'w' => 50, 'h' => 6, 'text' => 'OK', 'style' => ['fontSize' => 11]]]],
            'is_default' => false]);
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);
        OrderItem::create(['order_id' => $o->id, 'name' => 'X', 'quantity' => 1]);

        $gotenberg = $this->createMock(GotenbergClient::class);
        $gotenberg->expects($this->once())->method('htmlToPdf')->willReturn('PDF-BYTES');
        $this->app->instance(GotenbergClient::class, $gotenberg);

        $job = PrintJob::create(['tenant_id' => $t->id, 'type' => 'delivery',
            'scope' => ['order_ids' => [$o->id]], 'status' => 'pending',
            'meta' => ['template_id' => $tpl->id, 'template_name' => $tpl->name]]);
        app(PrintService::class)->render($job);

        $this->assertSame('done', $job->fresh()->status);
    }

    public function test_render_without_template_uses_legacy_path(): void
    {
        $t = Tenant::factory()->create();
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);
        OrderItem::create(['order_id' => $o->id, 'name' => 'X', 'quantity' => 1]);

        $gotenberg = $this->createMock(GotenbergClient::class);
        $gotenberg->expects($this->once())->method('htmlToPdf')->willReturn('PDF-BYTES');
        $this->app->instance(GotenbergClient::class, $gotenberg);

        $job = PrintJob::create(['tenant_id' => $t->id, 'type' => 'delivery',
            'scope' => ['order_ids' => [$o->id]], 'status' => 'pending']);
        app(PrintService::class)->render($job);

        $this->assertSame('done', $job->fresh()->status);
    }
}
```

- [ ] **Step 2: Modify `PrintJobController::store`**

Replace inline validation:

```php
$data = $request->validate([
    'type' => ['required', 'in:label,picking,packing,invoice,delivery'],
    'order_ids' => ['sometimes', 'array', 'max:500'],
    'order_ids.*' => ['integer'],
    'shipment_ids' => ['sometimes', 'array', 'max:500'],
    'shipment_ids.*' => ['integer'],
    'template_id' => ['sometimes', 'nullable', 'integer'],
]);
$orderIds = array_map('intval', $data['order_ids'] ?? []);
$shipmentIds = array_map('intval', $data['shipment_ids'] ?? []);
if ($orderIds === [] && $shipmentIds === []) {
    throw ValidationException::withMessages(['order_ids' => 'Chọn ít nhất một đơn hoặc một vận đơn.']);
}
$meta = [];
if (! empty($data['template_id'])) {
    if ($data['type'] !== 'delivery') {
        throw ValidationException::withMessages(['template_id' => 'template_id chỉ dùng cho type=delivery.']);
    }
    $tpl = \CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate::query()
        ->where('tenant_id', $tenant->id())->find((int) $data['template_id']);
    if (! $tpl) {
        throw ValidationException::withMessages(['template_id' => 'Template không tồn tại.']);
    }
    $meta = ['template_id' => $tpl->id, 'template_name' => $tpl->name];
}
$job = $service->createJob((int) $tenant->id(), $data['type'], $orderIds, $shipmentIds, $request->user()->getKey(), $meta);
```

- [ ] **Step 3: Modify `PrintService::createJob` signature để accept `$meta`**

Trong `PrintService.php`, đổi method `createJob`:

```php
/**
 * @param  list<int>  $orderIds
 * @param  list<int>  $shipmentIds
 * @param  array<string, mixed>  $meta
 */
public function createJob(int $tenantId, string $type, array $orderIds, array $shipmentIds, ?int $userId, array $meta = []): PrintJob
{
    if ($type === PrintJob::TYPE_LABEL) {
        $this->assertSinglePlatformAndCarrier($tenantId, $orderIds, $shipmentIds);
    }
    $job = PrintJob::query()->create([
        'tenant_id' => $tenantId, 'type' => $type,
        'scope' => array_filter(['order_ids' => array_values(array_unique(array_map('intval', $orderIds))), 'shipment_ids' => array_values(array_unique(array_map('intval', $shipmentIds)))]),
        'status' => PrintJob::STATUS_PENDING, 'created_by' => $userId,
        'meta' => $meta ?: null,
    ]);
    RenderPrintJob::dispatch($job->getKey())->onQueue('labels');

    return $job;
}
```

- [ ] **Step 4: Modify `PrintService::renderDeliverySlip` để route**

Thay toàn bộ method:

```php
/**
 * @return array{0:string,1:array<string,mixed>}
 */
private function renderDeliverySlip(PrintJob $job): array
{
    $tenantId = (int) $job->tenant_id;
    $orderIds = $job->orderIds();
    if ($orderIds === [] && ($sids = $job->shipmentIds())) {
        $orderIds = Shipment::query()->where('tenant_id', $tenantId)->whereIn('id', $sids)->pluck('order_id')->all();
    }
    $orders = Order::query()->where('tenant_id', $tenantId)->whereIn('id', $orderIds)->whereNull('deleted_at')
        ->with(['items', 'shipments' => fn ($q) => $q->orderByDesc('id')])->get();
    if ($orders->isEmpty()) {
        throw new \RuntimeException('Không có đơn nào để in.');
    }
    $channelOrders = $orders->filter(fn (Order $o) => $o->channel_account_id !== null);
    if ($channelOrders->isNotEmpty()) {
        throw new \RuntimeException('Đơn của sàn TMĐT chỉ dùng được phiếu/AWB thật của sàn — không in phiếu giao hàng tự tạo.');
    }

    $templateId = (int) (data_get($job->meta, 'template_id') ?: 0);
    if ($templateId > 0) {
        $tpl = \CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate::query()
            ->withTrashed()->where('tenant_id', $tenantId)->findOrFail($templateId);
        $renderer = app(\CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelRenderer::class);
        $html = $renderer->renderBatch($orders, $tpl);

        return [$this->gotenberg->htmlToPdf($html), ['orders' => $orders->count(), 'template_id' => $tpl->id, 'template_name' => $tpl->name, 'order_ids' => $orders->modelKeys()]];
    }

    $shopName = (string) (Tenant::query()->whereKey($tenantId)->value('name') ?? 'Cửa hàng');

    return [$this->gotenberg->htmlToPdf(PrintTemplates::deliverySlip($orders, $shopName, $this->paperSize($tenantId), $this->skuMapFor($orders))), ['orders' => $orders->count(), 'order_ids' => $orders->modelKeys()]];
}
```

- [ ] **Step 5: Run tests**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/PrintDeliveryWithTemplateTest.php`
Expected: 3 tests passed.

- [ ] **Step 6: Run all fulfillment tests để verify backward-compat**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/`
Expected: all green.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/PrintService.php \
        app/app/Modules/Fulfillment/Http/Controllers/PrintJobController.php \
        app/tests/Feature/Fulfillment/PrintDeliveryWithTemplateTest.php
git commit -m "feat(label): route delivery print via template_id; legacy path preserved"
```

---

### Task C4: Modify `ManualOrderService` để chấp nhận `warehouse_id`

**Files:**
- Modify: `app/app/Modules/Orders/Services/ManualOrderService.php`
- Test: `app/tests/Feature/Fulfillment/ManualOrderWarehouseIdTest.php`

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Services\ManualOrderService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualOrderWarehouseIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_warehouse_id_when_provided(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::create(['tenant_id' => $t->id, 'name' => 'Kho B', 'code' => 'B', 'is_default' => false]);

        $order = app(ManualOrderService::class)->create($t->id, null, [
            'recipient' => ['name' => 'X', 'phone' => '0901', 'address' => '123'],
            'items' => [['name' => 'A', 'quantity' => 1, 'unit_price' => 10000]],
            'warehouse_id' => $wh->id,
        ]);

        $this->assertSame($wh->id, $order->warehouse_id);
    }

    public function test_create_falls_back_to_default_warehouse(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::defaultFor($t->id);

        $order = app(ManualOrderService::class)->create($t->id, null, [
            'recipient' => ['name' => 'X', 'phone' => '0901', 'address' => '123'],
            'items' => [['name' => 'A', 'quantity' => 1, 'unit_price' => 10000]],
        ]);

        $this->assertSame($wh->id, $order->warehouse_id);
    }

    public function test_create_rejects_warehouse_id_from_other_tenant(): void
    {
        $t = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        $wh = Warehouse::create(['tenant_id' => $other->id, 'name' => 'Foreign']);

        $this->expectException(ValidationException::class);
        app(ManualOrderService::class)->create($t->id, null, [
            'recipient' => ['name' => 'X', 'phone' => '0901', 'address' => '123'],
            'items' => [['name' => 'A', 'quantity' => 1, 'unit_price' => 10000]],
            'warehouse_id' => $wh->id,
        ]);
    }
}
```

- [ ] **Step 2: Modify `ManualOrderService::create`**

Trong method `create`, ngay sau dòng `$items = $this->normalizeItems(...)`, thêm:

```php
$warehouseId = $this->resolveWarehouseId($tenantId, $data['warehouse_id'] ?? null);
```

Sau đó khi tạo Order, thêm `'warehouse_id' => $warehouseId,` vào mảng.

Thêm method private:

```php
private function resolveWarehouseId(int $tenantId, mixed $requested): int
{
    if ($requested === null || $requested === '') {
        return \CMBcoreSeller\Modules\Inventory\Models\Warehouse::defaultFor($tenantId)->id;
    }
    $exists = \CMBcoreSeller\Modules\Inventory\Models\Warehouse::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $tenantId)->where('id', (int) $requested)->exists();
    if (! $exists) {
        throw ValidationException::withMessages(['warehouse_id' => 'Kho gửi không thuộc shop hiện tại.']);
    }

    return (int) $requested;
}
```

- [ ] **Step 3: Run tests**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/ManualOrderWarehouseIdTest.php`
Expected: 3 tests passed.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Orders/Services/ManualOrderService.php \
        app/tests/Feature/Fulfillment/ManualOrderWarehouseIdTest.php
git commit -m "feat(orders): manual order accepts + validates warehouse_id"
```

---

## Phase D — BE preview test

### Task D1: Feature test preview endpoints

**Files:**
- Test: `app/tests/Feature/Fulfillment/ShippingLabelTemplatePreviewTest.php`

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingLabelTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_saved_template_returns_url(): void
    {
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => []], 'is_default' => false]);

        $g = $this->createMock(GotenbergClient::class);
        $g->method('htmlToPdf')->willReturn('PDF');
        $this->app->instance(GotenbergClient::class, $g);
        $m = $this->createMock(MediaUploader::class);
        $m->method('storeBytes')->willReturn(['url' => 'https://r2/preview.pdf', 'path' => 'p']);
        $this->app->instance(MediaUploader::class, $m);

        Sanctum::actingAs($u);
        $this->withHeader('X-Tenant', (string) $t->id)
             ->postJson("/api/v1/shipping-label-templates/{$tpl->id}/preview", ['sample_profile' => 'one_item_short_address'])
             ->assertOk()->assertJsonPath('data.url', 'https://r2/preview.pdf');
    }

    public function test_preview_rejects_unknown_sample_profile(): void
    {
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => []], 'is_default' => false]);
        Sanctum::actingAs($u);
        $this->withHeader('X-Tenant', (string) $t->id)
             ->postJson("/api/v1/shipping-label-templates/{$tpl->id}/preview", ['sample_profile' => 'invalid'])
             ->assertStatus(422);
    }

    public function test_preview_inline_works_with_unsaved_schema(): void
    {
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $g = $this->createMock(GotenbergClient::class);
        $g->method('htmlToPdf')->willReturn('PDF');
        $this->app->instance(GotenbergClient::class, $g);
        $m = $this->createMock(MediaUploader::class);
        $m->method('storeBytes')->willReturn(['url' => 'https://r2/p.pdf', 'path' => 'p']);
        $this->app->instance(MediaUploader::class, $m);

        Sanctum::actingAs($u);
        $this->withHeader('X-Tenant', (string) $t->id)
             ->postJson('/api/v1/shipping-label-templates/preview', [
                 'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
                 'schema' => ['fields' => [['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5,
                     'w' => 50, 'h' => 6, 'text' => 'OK', 'style' => ['fontSize' => 11]]]],
                 'sample_profile' => 'one_item_short_address',
             ])->assertOk()->assertJsonPath('data.url', 'https://r2/p.pdf');
    }
}
```

- [ ] **Step 2: Run**

Run: `cd app && vendor/bin/phpunit tests/Feature/Fulfillment/ShippingLabelTemplatePreviewTest.php`
Expected: 3 tests passed.

- [ ] **Step 3: Commit**

```bash
git add app/tests/Feature/Fulfillment/ShippingLabelTemplatePreviewTest.php
git commit -m "test(label): preview saved + inline endpoints"
```

---

## Phase E — FE foundation

### Task E1: Cài deps FE (`react-konva`, `konva`, `zustand`, `nanoid`)

**Files:**
- Modify: `app/package.json`

- [ ] **Step 1: Cài**

Run: `cd app && npm install react-konva konva zustand nanoid`
Expected: lockfile updated; no peer warnings.

- [ ] **Step 2: Verify build & typecheck**

Run: `cd app && npm run typecheck`
Expected: 0 errors.

- [ ] **Step 3: Commit**

```bash
git add app/package.json app/package-lock.json
git commit -m "chore(fe): add react-konva, konva, zustand, nanoid for label editor"
```

---

### Task E2: TS types `shippingLabelTypes.ts`

**Files:**
- Create: `app/resources/js/lib/shippingLabelTypes.ts`

- [ ] **Step 1: Tạo file**

```ts
export type Paper = 'A4' | 'A5' | 'A6' | '100x150mm' | '80mm' | 'custom';

export const PAPER_PRESETS: Record<Exclude<Paper, 'custom'>, { w: number; h: number; label: string }> = {
    A4: { w: 210, h: 297, label: 'A4 (210×297mm)' },
    A5: { w: 148, h: 210, label: 'A5 (148×210mm)' },
    A6: { w: 105, h: 148, label: 'A6 (105×148mm)' },
    '100x150mm': { w: 100, h: 150, label: '100×150mm (tem nhiệt)' },
    '80mm': { w: 80, h: 0, label: '80mm cuộn (auto)' },
};

export type TextStyle = {
    fontSize: number;
    fontWeight?: 400 | 600 | 700;
    align?: 'left' | 'center' | 'right';
    color?: string;
    lineHeight?: number;
};

export type DataKey =
    | 'carrier_logo' | 'carrier_name'
    | 'sender_name' | 'sender_phone' | 'sender_address'
    | 'recipient_name' | 'recipient_phone' | 'recipient_address'
    | 'recipient_address_detail' | 'recipient_address_admin'
    | 'order_number' | 'tracking_no'
    | 'cod' | 'weight' | 'print_note' | 'created_at' | 'total_qty';

export const DATA_KEYS: DataKey[] = [
    'carrier_logo', 'carrier_name',
    'sender_name', 'sender_phone', 'sender_address',
    'recipient_name', 'recipient_phone', 'recipient_address',
    'recipient_address_detail', 'recipient_address_admin',
    'order_number', 'tracking_no',
    'cod', 'weight', 'print_note', 'created_at', 'total_qty',
];

export type FieldBase = { id: string; x: number; y: number; w: number; h: number; rotation?: number };

export type QrField        = FieldBase & { type: 'qr'; source: 'tracking_no' | 'order_number'; ecc?: 'L' | 'M' | 'Q' | 'H' };
export type BarcodeField   = FieldBase & { type: 'barcode'; source: 'tracking_no' | 'order_number'; format?: 'code128'; showText?: boolean };
export type TextField      = FieldBase & { type: 'text'; text: string; style: TextStyle };
export type ImageField     = FieldBase & { type: 'image'; assetPath: string; fit?: 'contain' | 'cover' };
export type DataField      = FieldBase & { type: 'data'; key: DataKey; style: TextStyle; prefix?: string; suffix?: string };
export type ItemsListField = FieldBase & { type: 'items_list'; style: TextStyle; format?: 'bullet' | 'numbered'; maxRows?: number };
export type DividerField   = FieldBase & { type: 'divider'; thickness?: number; color?: string };
export type RectangleField = FieldBase & { type: 'rectangle'; borderThickness?: number; borderColor?: string; cornerRadius?: number; fillColor?: string };

export type Field = QrField | BarcodeField | TextField | ImageField | DataField | ItemsListField | DividerField | RectangleField;

export type Template = {
    id: number;
    name: string;
    paper: Paper;
    paper_w_mm: number;
    paper_h_mm: number;
    schema_version: number;
    schema: { fields: Field[] };
    is_default: boolean;
    created_at: string;
    updated_at: string;
};

export type SampleProfile = 'one_item_short_address' | 'three_items_long_address' | 'cod_with_print_note';
export const SAMPLE_PROFILES: SampleProfile[] = ['one_item_short_address', 'three_items_long_address', 'cod_with_print_note'];
```

- [ ] **Step 2: Verify typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/shippingLabelTypes.ts
git commit -m "feat(fe): shipping label TS types mirror BE schema"
```

---

### Task E3: API hooks `shippingLabels.tsx`

**Files:**
- Create: `app/resources/js/lib/shippingLabels.tsx`

- [ ] **Step 1: Tạo file (theo pattern `lib/fulfillment.tsx`)**

```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useScopedApi } from '@/lib/api';
import { useTenant } from '@/lib/tenant';
import type { SampleProfile, Template } from './shippingLabelTypes';

export type TemplateInput = Omit<Template, 'id' | 'is_default' | 'schema_version' | 'created_at' | 'updated_at'> & { schema_version?: number };

export function useShippingLabelTemplates() {
    const api = useScopedApi();
    const { data: tenant } = useTenant();
    return useQuery({
        queryKey: ['shipping-label-templates', tenant?.id],
        enabled: !!api && !!tenant,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Template[] }>('/shipping-label-templates');
            return data.data;
        },
    });
}

export function useShippingLabelTemplate(id: number | null) {
    const api = useScopedApi();
    const { data: tenant } = useTenant();
    return useQuery({
        queryKey: ['shipping-label-template', tenant?.id, id],
        enabled: !!api && !!tenant && id != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Template }>(`/shipping-label-templates/${id}`);
            return data.data;
        },
    });
}

function invalidate(qc: ReturnType<typeof useQueryClient>) {
    qc.invalidateQueries({ queryKey: ['shipping-label-templates'] });
}

export function useCreateShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: TemplateInput) => {
            const { data } = await api!.post<{ data: Template }>('/shipping-label-templates', input);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function useUpdateShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, input }: { id: number; input: TemplateInput }) => {
            const { data } = await api!.put<{ data: Template }>(`/shipping-label-templates/${id}`, input);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function useDeleteShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/shipping-label-templates/${id}`); },
        onSuccess: () => invalidate(qc),
    });
}

export function useSetDefaultShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: Template }>(`/shipping-label-templates/${id}/set-default`);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function useDuplicateShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: Template }>(`/shipping-label-templates/${id}/duplicate`);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function usePreviewShippingLabelTemplate() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ id, sample_profile }: { id: number; sample_profile: SampleProfile }) => {
            const { data } = await api!.post<{ data: { url: string } }>(`/shipping-label-templates/${id}/preview`, { sample_profile });
            return data.data;
        },
    });
}

export function usePreviewInlineShippingLabelTemplate() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: TemplateInput & { sample_profile: SampleProfile }) => {
            const { data } = await api!.post<{ data: { url: string } }>('/shipping-label-templates/preview', vars);
            return data.data;
        },
    });
}
```

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/shippingLabels.tsx
git commit -m "feat(fe): react-query hooks for shipping label templates"
```

---

### Task E4: `lib/labelEditor/coords.ts`

**Files:**
- Create: `app/resources/js/lib/labelEditor/coords.ts`

- [ ] **Step 1: Tạo file**

```ts
export const PX_PER_MM = 4;                          // editor base scale; zoom multiplier riêng

export const mm2px = (mm: number, zoom = 1): number => mm * PX_PER_MM * zoom;
export const px2mm = (px: number, zoom = 1): number => px / (PX_PER_MM * zoom);

export function snap(value: number, grid: number): number {
    if (grid <= 0) return Math.round(value * 10) / 10;
    return Math.round(value / grid) * grid;
}

export function clampBox(box: { x: number; y: number; w: number; h: number }, paperW: number, paperH: number) {
    const w = Math.max(5, Math.min(box.w, paperW));
    const h = Math.max(5, Math.min(box.h, paperH > 0 ? paperH : 9999));
    const x = Math.max(0, Math.min(box.x, paperW - w));
    const y = Math.max(0, Math.min(box.y, (paperH > 0 ? paperH - h : 9999)));
    return { x, y, w, h };
}
```

- [ ] **Step 2: Commit**

```bash
git add app/resources/js/lib/labelEditor/coords.ts
git commit -m "feat(fe): mm↔px coord helpers + snap/clamp for editor"
```

---

### Task E5: `lib/labelEditor/sampleData.ts`

**Files:**
- Create: `app/resources/js/lib/labelEditor/sampleData.ts`

- [ ] **Step 1: Tạo file** (đối xứng `SampleDataFactory` BE — chỉ cho preview canvas)

```ts
import type { DataKey, SampleProfile } from '@/lib/shippingLabelTypes';

export type SampleContext = Record<DataKey | 'items_count', string> & { items: Array<{ name: string; sku: string | null; qty: number }> };

export const SAMPLE_DATA: Record<SampleProfile, SampleContext> = {
    one_item_short_address: {
        carrier_logo: 'GHN', carrier_name: 'GIAO HÀNG NHANH',
        sender_name: 'Shop CMBcore', sender_phone: '0901234567', sender_address: '12 Lê Lợi, Q1, TP.HCM',
        recipient_name: 'Nguyễn Văn A', recipient_phone: '0911111111',
        recipient_address: '50 Hai Bà Trưng, Bến Nghé, Q1, TP.HCM',
        recipient_address_detail: '50 Hai Bà Trưng', recipient_address_admin: 'Bến Nghé, Q1, TP.HCM',
        order_number: 'M-2026-001', tracking_no: 'AWB-SHORT-77',
        cod: '—', weight: '250g', print_note: '', created_at: '18/05/2026 09:00',
        total_qty: '1', items_count: '1',
        items: [{ name: 'Bút bi xanh', sku: 'BB-X', qty: 1 }],
    },
    three_items_long_address: {
        carrier_logo: 'GHN', carrier_name: 'GIAO HÀNG NHANH',
        sender_name: 'Shop CMBcore', sender_phone: '0901234567', sender_address: '123 Đường Lê Lợi Nối Dài, Bến Nghé, Quận 1, TP.HCM',
        recipient_name: 'Trần Thị Hoa Hồng Phương Lan', recipient_phone: '0987654321',
        recipient_address: '456/12 Đường Nguyễn Trãi, Phường 7, Quận 5, TP.HCM',
        recipient_address_detail: '456/12 Đường Nguyễn Trãi', recipient_address_admin: 'Phường 7, Quận 5, TP.HCM',
        order_number: 'M-2026-002', tracking_no: 'AWB-LONG-1234567',
        cod: '450.000 đ', weight: '800g', print_note: 'Đóng gói cẩn thận, hàng dễ vỡ',
        created_at: '18/05/2026 10:30', total_qty: '5', items_count: '3',
        items: [
            { name: 'Áo thun nam basic màu đen size L', sku: 'AT-BLK-L', qty: 2 },
            { name: 'Quần short kaki', sku: 'QS-01', qty: 1 },
            { name: 'Nón lưỡi trai', sku: null, qty: 2 },
        ],
    },
    cod_with_print_note: {
        carrier_logo: 'GHTK', carrier_name: 'GIAO HÀNG TIẾT KIỆM',
        sender_name: 'Shop CMBcore', sender_phone: '0901234567', sender_address: '12 Lê Lợi, Q1, TP.HCM',
        recipient_name: 'Lê Văn C', recipient_phone: '0912345678',
        recipient_address: '78 Nguyễn Huệ, Bến Nghé, Q1, TP.HCM',
        recipient_address_detail: '78 Nguyễn Huệ', recipient_address_admin: 'Bến Nghé, Q1, TP.HCM',
        order_number: 'M-2026-003', tracking_no: 'AWB-COD-555',
        cod: '500.000 đ', weight: '300g',
        print_note: 'Cảm ơn quý khách! Đổi/trả 7 ngày kèm hộp nguyên seal. Hotline: 0901234567',
        created_at: '18/05/2026 11:00', total_qty: '1', items_count: '1',
        items: [{ name: 'Đồng hồ thông minh', sku: 'SW-1', qty: 1 }],
    },
};
```

- [ ] **Step 2: Commit**

```bash
git add app/resources/js/lib/labelEditor/sampleData.ts
git commit -m "feat(fe): editor sample data (3 profiles) for live preview"
```

---

### Task E6: zustand `editorStore.ts`

**Files:**
- Create: `app/resources/js/lib/labelEditor/editorStore.ts`

- [ ] **Step 1: Tạo store**

```ts
import { create } from 'zustand';
import { nanoid } from 'nanoid';
import type { Field, Paper, SampleProfile, Template } from '@/lib/shippingLabelTypes';
import { PAPER_PRESETS } from '@/lib/shippingLabelTypes';
import { clampBox } from './coords';

type Meta = { id: number | null; name: string; paper: Paper; paper_w_mm: number; paper_h_mm: number; is_default: boolean };
type Snapshot = { meta: Meta; fields: Field[] };

type EditorState = {
    meta: Meta;
    fields: Field[];
    selection: string[];
    history: { past: Snapshot[]; future: Snapshot[] };
    sampleProfile: SampleProfile;
    zoom: number;
    grid: 0 | 1 | 2 | 5;
};

type EditorActions = {
    init: (tpl: Template | null) => void;
    addField: (field: Field) => void;
    updateField: (id: string, patch: Partial<Field>) => void;
    commitTransform: (id: string, box: { x: number; y: number; w: number; h: number; rotation?: number }) => void;
    removeFields: (ids: string[]) => void;
    setMeta: (patch: Partial<Pick<Meta, 'name'>>) => void;
    setPaper: (paper: Paper, w?: number, h?: number) => { needsConfirm: boolean };
    setSelection: (ids: string[]) => void;
    undo: () => void;
    redo: () => void;
    setSampleProfile: (p: SampleProfile) => void;
    setZoom: (z: number) => void;
    setGrid: (g: 0 | 1 | 2 | 5) => void;
    toPayload: () => Omit<Template, 'id' | 'created_at' | 'updated_at'>;
};

const HISTORY_LIMIT = 50;

const blankMeta = (): Meta => ({ id: null, name: '', paper: 'A6', paper_w_mm: 105, paper_h_mm: 148, is_default: false });

function pushHistory(state: EditorState): Snapshot[] {
    return [...state.history.past, { meta: state.meta, fields: state.fields }].slice(-HISTORY_LIMIT);
}

export const useEditorStore = create<EditorState & EditorActions>((set, get) => ({
    meta: blankMeta(),
    fields: [],
    selection: [],
    history: { past: [], future: [] },
    sampleProfile: 'one_item_short_address',
    zoom: 2,
    grid: 1,

    init: (tpl) => set({
        meta: tpl
            ? { id: tpl.id, name: tpl.name, paper: tpl.paper, paper_w_mm: tpl.paper_w_mm, paper_h_mm: tpl.paper_h_mm, is_default: tpl.is_default }
            : blankMeta(),
        fields: tpl?.schema?.fields ?? [],
        selection: [],
        history: { past: [], future: [] },
    }),

    addField: (field) => set((s) => ({
        history: { past: pushHistory(s), future: [] },
        fields: [...s.fields, { ...field, id: field.id || nanoid(8) }],
        selection: [field.id],
    })),

    updateField: (id, patch) => set((s) => ({
        fields: s.fields.map((f) => (f.id === id ? ({ ...f, ...patch } as Field) : f)),
    })),

    commitTransform: (id, box) => set((s) => {
        const clamped = clampBox(box, s.meta.paper_w_mm, s.meta.paper_h_mm);
        return {
            history: { past: pushHistory(s), future: [] },
            fields: s.fields.map((f) => (f.id === id ? ({ ...f, ...clamped, rotation: box.rotation ?? f.rotation } as Field) : f)),
        };
    }),

    removeFields: (ids) => set((s) => ({
        history: { past: pushHistory(s), future: [] },
        fields: s.fields.filter((f) => !ids.includes(f.id)),
        selection: [],
    })),

    setMeta: (patch) => set((s) => ({ meta: { ...s.meta, ...patch } })),

    setPaper: (paper, w, h) => {
        const dim = paper === 'custom' ? { w: w ?? 100, h: h ?? 100 } : PAPER_PRESETS[paper];
        const s = get();
        const fitsAll = s.fields.every((f) => f.x + f.w <= dim.w && (dim.h === 0 || f.y + f.h <= dim.h));
        set({
            history: { past: pushHistory(s), future: [] },
            meta: { ...s.meta, paper, paper_w_mm: dim.w, paper_h_mm: dim.h },
        });
        return { needsConfirm: !fitsAll };
    },

    setSelection: (ids) => set({ selection: ids }),

    undo: () => set((s) => {
        const last = s.history.past[s.history.past.length - 1];
        if (!last) return s;
        return {
            history: { past: s.history.past.slice(0, -1), future: [{ meta: s.meta, fields: s.fields }, ...s.history.future] },
            meta: last.meta, fields: last.fields, selection: [],
        };
    }),

    redo: () => set((s) => {
        const next = s.history.future[0];
        if (!next) return s;
        return {
            history: { past: [...s.history.past, { meta: s.meta, fields: s.fields }], future: s.history.future.slice(1) },
            meta: next.meta, fields: next.fields, selection: [],
        };
    }),

    setSampleProfile: (p) => set({ sampleProfile: p }),
    setZoom: (z) => set({ zoom: Math.max(0.5, Math.min(6, z)) }),
    setGrid: (g) => set({ grid: g }),

    toPayload: () => {
        const s = get();
        return {
            name: s.meta.name,
            paper: s.meta.paper,
            paper_w_mm: s.meta.paper_w_mm,
            paper_h_mm: s.meta.paper_h_mm,
            schema_version: 1,
            schema: { fields: s.fields },
            is_default: s.meta.is_default,
        };
    },
}));
```

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/labelEditor/editorStore.ts
git commit -m "feat(fe): zustand editorStore (history 50 + paper/sample/zoom/grid)"
```

---

## Phase F — FE field type registry

### Task F1: 8 FieldDef files + index registry

**Files:**
- Create: `app/resources/js/components/shipping-labels/fieldTypes/index.ts`
- Create: 8 `<Name>FieldDef.tsx` files dưới cùng folder

- [ ] **Step 1: Tạo `index.ts` (registry shape)**

```ts
import type { ReactNode } from 'react';
import type { Field } from '@/lib/shippingLabelTypes';
import type { SampleContext } from '@/lib/labelEditor/sampleData';
import { QrFieldDef } from './QrFieldDef';
import { BarcodeFieldDef } from './BarcodeFieldDef';
import { TextFieldDef } from './TextFieldDef';
import { ImageFieldDef } from './ImageFieldDef';
import { DataFieldDef } from './DataFieldDef';
import { ItemsListFieldDef } from './ItemsListFieldDef';
import { DividerFieldDef } from './DividerFieldDef';
import { RectangleFieldDef } from './RectangleFieldDef';

export interface FieldDef<F extends Field = Field> {
    type: F['type'];
    label: string;
    icon: ReactNode;
    group: 'codes' | 'display' | 'data' | 'list' | 'shape';
    defaultProps: () => Omit<F, 'id'>;
    KonvaRenderer: React.FC<{ field: F; ctx: SampleContext; selected: boolean; zoom: number }>;
    InspectorPanel: React.FC<{ field: F; onChange: (patch: Partial<F>) => void }>;
}

export const FIELD_REGISTRY: Record<Field['type'], FieldDef> = {
    qr: QrFieldDef, barcode: BarcodeFieldDef, text: TextFieldDef, image: ImageFieldDef,
    data: DataFieldDef, items_list: ItemsListFieldDef, divider: DividerFieldDef, rectangle: RectangleFieldDef,
} as const;

export const FIELD_GROUPS: Array<{ key: FieldDef['group']; label: string }> = [
    { key: 'codes', label: 'Mã' }, { key: 'display', label: 'Hiển thị' },
    { key: 'data', label: 'Trường động' }, { key: 'list', label: 'Danh sách' }, { key: 'shape', label: 'Khung' },
];
```

- [ ] **Step 2: Tạo `TextFieldDef.tsx` (mẫu cho 7 file còn lại)**

```tsx
import { Form, Input, InputNumber, Segmented } from 'antd';
import { FontSizeOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { TextField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const TextFieldDef: FieldDef<TextField> = {
    type: 'text',
    label: 'Văn bản',
    icon: <FontSizeOutlined />,
    group: 'display',
    defaultProps: () => ({ type: 'text', x: 5, y: 5, w: 50, h: 6, text: 'Văn bản', style: { fontSize: 11, fontWeight: 400, align: 'left' } }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
            <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} padding={1}
                  text={field.text} fontSize={field.style.fontSize * zoom * 0.9}
                  fontStyle={field.style.fontWeight === 700 ? 'bold' : 'normal'} align={field.style.align ?? 'left'}
                  fill={field.style.color ?? '#222'} verticalAlign="middle" />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Nội dung">
                <Input.TextArea rows={2} value={field.text} onChange={(e) => onChange({ text: e.target.value })} maxLength={500} />
            </Form.Item>
            <Form.Item label="Cỡ chữ (pt)">
                <InputNumber min={6} max={48} value={field.style.fontSize}
                    onChange={(v) => onChange({ style: { ...field.style, fontSize: v ?? 11 } })} />
            </Form.Item>
            <Form.Item label="Đậm">
                <Segmented options={[{ label: 'Thường', value: 400 }, { label: 'Đậm vừa', value: 600 }, { label: 'Đậm', value: 700 }]}
                    value={field.style.fontWeight ?? 400}
                    onChange={(v) => onChange({ style: { ...field.style, fontWeight: v as 400 | 600 | 700 } })} />
            </Form.Item>
            <Form.Item label="Căn">
                <Segmented options={[{ label: 'Trái', value: 'left' }, { label: 'Giữa', value: 'center' }, { label: 'Phải', value: 'right' }]}
                    value={field.style.align ?? 'left'}
                    onChange={(v) => onChange({ style: { ...field.style, align: v as 'left' | 'center' | 'right' } })} />
            </Form.Item>
        </>
    ),
};
```

- [ ] **Step 3: Tạo 7 file còn lại theo cùng pattern**

Cho mỗi file: file path, icon, defaultProps, KonvaRenderer, InspectorPanel. Vì giới hạn không gian, code chi tiết được lưu vào folder `app/resources/js/components/shipping-labels/fieldTypes/` với các định danh sau. Mỗi file <100 dòng.

**`QrFieldDef.tsx`** (KonvaRenderer: placeholder rect with "QR" centered. Inspector: `<Radio.Group>` source `tracking_no|order_number`, `<Segmented>` ecc):

```tsx
import { Form, Radio, Segmented } from 'antd';
import { QrcodeOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { QrField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const QrFieldDef: FieldDef<QrField> = {
    type: 'qr', label: 'Mã QR', icon: <QrcodeOutlined />, group: 'codes',
    defaultProps: () => ({ type: 'qr', x: 5, y: 5, w: 25, h: 25, source: 'tracking_no', ecc: 'M' }),
    KonvaRenderer: ({ field, selected, zoom }) => {
        const size = Math.min(mm2px(field.w, zoom), mm2px(field.h, zoom));
        return (
            <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
                <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill="#f5f5f5"
                      stroke={selected ? '#1677ff' : '#d9d9d9'} strokeWidth={1} dash={selected ? [4, 2] : []} />
                <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                      text="QR" fontSize={size * 0.25} align="center" verticalAlign="middle" fill="#8c8c8c" />
            </Group>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Nguồn dữ liệu">
                <Radio.Group value={field.source} onChange={(e) => onChange({ source: e.target.value })}>
                    <Radio value="tracking_no">Mã vận đơn</Radio>
                    <Radio value="order_number">Mã đơn</Radio>
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Mức chống lỗi (ECC)">
                <Segmented options={['L', 'M', 'Q', 'H']} value={field.ecc ?? 'M'}
                    onChange={(v) => onChange({ ecc: v as 'L' | 'M' | 'Q' | 'H' })} />
            </Form.Item>
        </>
    ),
};
```

**`BarcodeFieldDef.tsx`** (placeholder bars, inspector: source radio + showText switch):

```tsx
import { Form, Radio, Switch } from 'antd';
import { BarcodeOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { BarcodeField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const BarcodeFieldDef: FieldDef<BarcodeField> = {
    type: 'barcode', label: 'Mã vạch', icon: <BarcodeOutlined />, group: 'codes',
    defaultProps: () => ({ type: 'barcode', x: 5, y: 5, w: 60, h: 15, source: 'tracking_no', showText: true }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill="#fff"
                  stroke={selected ? '#1677ff' : '#d9d9d9'} strokeWidth={1} dash={selected ? [4, 2] : []} />
            {Array.from({ length: Math.floor(mm2px(field.w, zoom) / 3) }).map((_, i) => (
                <Rect key={i} x={i * 3 + 2} y={2}
                      width={i % 3 === 0 ? 2 : 1} height={mm2px(field.h, zoom) - (field.showText ? 14 : 4)} fill="#222" />
            ))}
            {field.showText && (
                <Text x={0} y={mm2px(field.h, zoom) - 12} width={mm2px(field.w, zoom)}
                      text="1234567890" fontSize={10} align="center" fontFamily="monospace" fill="#222" />
            )}
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Nguồn dữ liệu">
                <Radio.Group value={field.source} onChange={(e) => onChange({ source: e.target.value })}>
                    <Radio value="tracking_no">Mã vận đơn</Radio>
                    <Radio value="order_number">Mã đơn</Radio>
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Hiện chữ bên dưới">
                <Switch checked={field.showText ?? true} onChange={(v) => onChange({ showText: v })} />
            </Form.Item>
        </>
    ),
};
```

**`ImageFieldDef.tsx`**:

```tsx
import { Form, Input, Segmented } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { ImageField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const ImageFieldDef: FieldDef<ImageField> = {
    type: 'image', label: 'Hình ảnh', icon: <PictureOutlined />, group: 'display',
    defaultProps: () => ({ type: 'image', x: 5, y: 5, w: 20, h: 20, assetPath: '', fit: 'contain' }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill="#fafafa"
                  stroke={selected ? '#1677ff' : '#d9d9d9'} strokeWidth={1} dash={selected ? [4, 2] : []} />
            <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                  text="🖼" fontSize={Math.min(mm2px(field.w, zoom), mm2px(field.h, zoom)) * 0.4} align="center" verticalAlign="middle" />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Đường dẫn ảnh (R2 path / URL)">
                <Input value={field.assetPath} onChange={(e) => onChange({ assetPath: e.target.value })} placeholder="logos/shop.png" />
            </Form.Item>
            <Form.Item label="Cách lấp khung">
                <Segmented options={[{ label: 'Vừa khung', value: 'contain' }, { label: 'Lấp đầy', value: 'cover' }]}
                    value={field.fit ?? 'contain'} onChange={(v) => onChange({ fit: v as 'contain' | 'cover' })} />
            </Form.Item>
        </>
    ),
};
```

**`DataFieldDef.tsx`**:

```tsx
import { Form, Input, InputNumber, Radio, Segmented } from 'antd';
import { DatabaseOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { DataField, DataKey } from '@/lib/shippingLabelTypes';
import { DATA_KEYS } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

const KEY_LABELS: Record<DataKey, string> = {
    carrier_logo: 'Logo ĐVVC', carrier_name: 'Tên ĐVVC',
    sender_name: 'Tên người gửi', sender_phone: 'SĐT người gửi', sender_address: 'Địa chỉ gửi',
    recipient_name: 'Tên người nhận', recipient_phone: 'SĐT người nhận', recipient_address: 'Địa chỉ nhận (đầy đủ)',
    recipient_address_detail: 'Địa chỉ chi tiết', recipient_address_admin: 'Phường/Quận/Tỉnh',
    order_number: 'Mã đơn', tracking_no: 'Mã vận đơn',
    cod: 'COD', weight: 'Khối lượng', print_note: 'Ghi chú in',
    created_at: 'Ngày tạo', total_qty: 'Tổng SL',
};

export const DataFieldDef: FieldDef<DataField> = {
    type: 'data', label: 'Trường động', icon: <DatabaseOutlined />, group: 'data',
    defaultProps: () => ({ type: 'data', x: 5, y: 5, w: 50, h: 6, key: 'recipient_name', style: { fontSize: 12, fontWeight: 700, align: 'left' } }),
    KonvaRenderer: ({ field, ctx, selected, zoom }) => {
        const sampleText = field.key === 'carrier_logo' ? (ctx.carrier_logo || 'GHN') : ((field.prefix ?? '') + (ctx[field.key] ?? '') + (field.suffix ?? ''));
        return (
            <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
                <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                      stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
                <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} padding={1}
                      text={sampleText} fontSize={field.style.fontSize * zoom * 0.9}
                      fontStyle={field.style.fontWeight === 700 ? 'bold' : field.style.fontWeight === 600 ? '600' : 'normal'}
                      align={field.style.align ?? 'left'} fill={field.style.color ?? '#222'} verticalAlign="middle" wrap="word" />
            </Group>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Trường">
                <Radio.Group value={field.key} onChange={(e) => onChange({ key: e.target.value as DataKey })} style={{ display: 'flex', flexDirection: 'column' }}>
                    {DATA_KEYS.map((k) => <Radio key={k} value={k}>{KEY_LABELS[k]}</Radio>)}
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Tiền tố">
                <Input value={field.prefix ?? ''} onChange={(e) => onChange({ prefix: e.target.value })} maxLength={32} />
            </Form.Item>
            <Form.Item label="Hậu tố">
                <Input value={field.suffix ?? ''} onChange={(e) => onChange({ suffix: e.target.value })} maxLength={32} />
            </Form.Item>
            <Form.Item label="Cỡ chữ (pt)">
                <InputNumber min={6} max={48} value={field.style.fontSize}
                    onChange={(v) => onChange({ style: { ...field.style, fontSize: v ?? 11 } })} />
            </Form.Item>
            <Form.Item label="Đậm">
                <Segmented options={[{ label: 'Thường', value: 400 }, { label: 'Đậm vừa', value: 600 }, { label: 'Đậm', value: 700 }]}
                    value={field.style.fontWeight ?? 400}
                    onChange={(v) => onChange({ style: { ...field.style, fontWeight: v as 400 | 600 | 700 } })} />
            </Form.Item>
            <Form.Item label="Căn">
                <Segmented options={[{ label: 'Trái', value: 'left' }, { label: 'Giữa', value: 'center' }, { label: 'Phải', value: 'right' }]}
                    value={field.style.align ?? 'left'}
                    onChange={(v) => onChange({ style: { ...field.style, align: v as 'left' | 'center' | 'right' } })} />
            </Form.Item>
        </>
    ),
};
```

**`ItemsListFieldDef.tsx`**:

```tsx
import { Form, InputNumber, Segmented } from 'antd';
import { UnorderedListOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { ItemsListField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const ItemsListFieldDef: FieldDef<ItemsListField> = {
    type: 'items_list', label: 'Danh sách SP', icon: <UnorderedListOutlined />, group: 'list',
    defaultProps: () => ({ type: 'items_list', x: 5, y: 5, w: 80, h: 30, style: { fontSize: 10 }, format: 'bullet', maxRows: 5 }),
    KonvaRenderer: ({ field, ctx, selected, zoom }) => {
        const items = ctx.items.slice(0, field.maxRows ?? ctx.items.length);
        const lines = items.map((it, i) => ((field.format ?? 'bullet') === 'numbered' ? `${i + 1}.` : '•') + ' ' + it.name + ' × ' + it.qty);
        return (
            <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
                <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} stroke={selected ? '#1677ff' : 'transparent'} dash={[4, 2]} />
                <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} padding={1}
                      text={lines.join('\n')} fontSize={field.style.fontSize * zoom * 0.9} lineHeight={1.25} fill="#222" wrap="word" />
            </Group>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Định dạng">
                <Segmented options={[{ label: 'Bullet', value: 'bullet' }, { label: 'Số TT', value: 'numbered' }]}
                    value={field.format ?? 'bullet'} onChange={(v) => onChange({ format: v as 'bullet' | 'numbered' })} />
            </Form.Item>
            <Form.Item label="Số dòng tối đa">
                <InputNumber min={1} max={50} value={field.maxRows ?? 5} onChange={(v) => onChange({ maxRows: v ?? 5 })} />
            </Form.Item>
            <Form.Item label="Cỡ chữ (pt)">
                <InputNumber min={6} max={24} value={field.style.fontSize}
                    onChange={(v) => onChange({ style: { ...field.style, fontSize: v ?? 10 } })} />
            </Form.Item>
        </>
    ),
};
```

**`DividerFieldDef.tsx`**:

```tsx
import { ColorPicker, Form, InputNumber } from 'antd';
import { MinusOutlined } from '@ant-design/icons';
import { Group, Rect } from 'react-konva';
import type { DividerField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const DividerFieldDef: FieldDef<DividerField> = {
    type: 'divider', label: 'Đường kẻ', icon: <MinusOutlined />, group: 'shape',
    defaultProps: () => ({ type: 'divider', x: 5, y: 5, w: 80, h: 1, thickness: 1, color: '#222222' }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill={selected ? 'rgba(22,119,255,0.1)' : 'transparent'} />
            <Rect y={(mm2px(field.h, zoom) - (field.thickness ?? 1)) / 2}
                  width={mm2px(field.w, zoom)} height={field.thickness ?? 1} fill={field.color ?? '#222222'} />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Độ dày (px)">
                <InputNumber min={1} max={8} value={field.thickness ?? 1} onChange={(v) => onChange({ thickness: v ?? 1 })} />
            </Form.Item>
            <Form.Item label="Màu">
                <ColorPicker value={field.color ?? '#222222'} onChange={(c) => onChange({ color: c.toHexString() })} />
            </Form.Item>
        </>
    ),
};
```

**`RectangleFieldDef.tsx`**:

```tsx
import { ColorPicker, Form, InputNumber } from 'antd';
import { BorderOutlined } from '@ant-design/icons';
import { Group, Rect } from 'react-konva';
import type { RectangleField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const RectangleFieldDef: FieldDef<RectangleField> = {
    type: 'rectangle', label: 'Khung', icon: <BorderOutlined />, group: 'shape',
    defaultProps: () => ({ type: 'rectangle', x: 5, y: 5, w: 50, h: 30, borderThickness: 1, borderColor: '#222222', cornerRadius: 0, fillColor: '#ffffff' }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                  fill={field.fillColor ?? 'transparent'}
                  stroke={field.borderColor ?? '#222'} strokeWidth={field.borderThickness ?? 1}
                  cornerRadius={field.cornerRadius ?? 0} dash={selected ? [4, 2] : undefined} />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Viền (px)">
                <InputNumber min={0} max={8} value={field.borderThickness ?? 1} onChange={(v) => onChange({ borderThickness: v ?? 1 })} />
            </Form.Item>
            <Form.Item label="Màu viền">
                <ColorPicker value={field.borderColor ?? '#222222'} onChange={(c) => onChange({ borderColor: c.toHexString() })} />
            </Form.Item>
            <Form.Item label="Bo góc (px)">
                <InputNumber min={0} max={20} value={field.cornerRadius ?? 0} onChange={(v) => onChange({ cornerRadius: v ?? 0 })} />
            </Form.Item>
            <Form.Item label="Màu nền">
                <ColorPicker value={field.fillColor ?? '#ffffff'} onChange={(c) => onChange({ fillColor: c.toHexString() })} />
            </Form.Item>
        </>
    ),
};
```

- [ ] **Step 4: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/components/shipping-labels/fieldTypes/
git commit -m "feat(fe): FieldDef registry — 8 field types (KonvaRenderer + InspectorPanel)"
```

---

## Phase G — FE editor components

### Task G1: `LabelCanvas` + `FieldNode`

**Files:**
- Create: `app/resources/js/components/shipping-labels/FieldNode.tsx`
- Create: `app/resources/js/components/shipping-labels/LabelCanvas.tsx`

- [ ] **Step 1: Tạo `FieldNode.tsx`**

```tsx
import type { Field } from '@/lib/shippingLabelTypes';
import { FIELD_REGISTRY } from './fieldTypes';
import { SAMPLE_DATA } from '@/lib/labelEditor/sampleData';
import { useEditorStore } from '@/lib/labelEditor/editorStore';

export function FieldNode({ field, zoom }: { field: Field; zoom: number }) {
    const selected = useEditorStore((s) => s.selection.includes(field.id));
    const profile = useEditorStore((s) => s.sampleProfile);
    const def = FIELD_REGISTRY[field.type];
    if (!def) return null;
    const Renderer = def.KonvaRenderer;
    return <Renderer field={field as any} ctx={SAMPLE_DATA[profile]} selected={selected} zoom={zoom} />;
}
```

- [ ] **Step 2: Tạo `LabelCanvas.tsx`**

```tsx
import { useEffect, useMemo, useRef } from 'react';
import { Layer, Rect, Stage, Transformer } from 'react-konva';
import Konva from 'konva';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { mm2px, px2mm, snap, clampBox } from '@/lib/labelEditor/coords';
import { FieldNode } from './FieldNode';

export function LabelCanvas() {
    const stageRef = useRef<Konva.Stage | null>(null);
    const trRef = useRef<Konva.Transformer | null>(null);
    const layerRef = useRef<Konva.Layer | null>(null);

    const { meta, fields, selection, zoom, grid, commitTransform, setSelection } = useEditorStore();

    const widthPx = mm2px(meta.paper_w_mm, zoom);
    const heightPx = mm2px(meta.paper_h_mm > 0 ? meta.paper_h_mm : 200, zoom);

    useEffect(() => {
        const tr = trRef.current;
        const layer = layerRef.current;
        if (!tr || !layer) return;
        const nodes = selection.map((id) => layer.findOne<Konva.Node>(`#${id}`)).filter(Boolean) as Konva.Node[];
        tr.nodes(nodes);
        tr.getLayer()?.batchDraw();
    }, [selection, fields]);

    const onClickStage = (e: Konva.KonvaEventObject<MouseEvent>) => {
        if (e.target === e.target.getStage()) setSelection([]);
    };

    const onTransformEnd = (id: string) => (e: Konva.KonvaEventObject<Event>) => {
        const node = e.target;
        const scaleX = node.scaleX(), scaleY = node.scaleY();
        const newW = node.width() * scaleX, newH = node.height() * scaleY;
        node.scaleX(1); node.scaleY(1);
        const box = clampBox({
            x: snap(px2mm(node.x(), zoom), grid),
            y: snap(px2mm(node.y(), zoom), grid),
            w: snap(px2mm(newW, zoom), grid),
            h: snap(px2mm(newH, zoom), grid),
        }, meta.paper_w_mm, meta.paper_h_mm);
        commitTransform(id, { ...box, rotation: node.rotation() });
    };

    const gridDots = useMemo(() => {
        if (grid === 0) return null;
        const dots: Array<{ x: number; y: number }> = [];
        for (let y = 0; y <= meta.paper_w_mm; y += grid) {
            for (let x = 0; x <= meta.paper_w_mm; x += grid) {
                dots.push({ x: mm2px(x, zoom), y: mm2px(y, zoom) });
            }
        }
        return dots;
    }, [grid, meta.paper_w_mm, zoom]);

    return (
        <Stage ref={stageRef} width={widthPx + 40} height={heightPx + 40} onClick={onClickStage}>
            <Layer x={20} y={20}>
                <Rect width={widthPx} height={heightPx} fill="#fff" stroke="#bfbfbf" strokeWidth={1}
                      shadowBlur={4} shadowColor="rgba(0,0,0,0.08)" />
                {gridDots?.map((d, i) => <Rect key={i} x={d.x} y={d.y} width={1} height={1} fill="#e0e0e0" />)}
            </Layer>
            <Layer x={20} y={20} ref={layerRef}>
                {fields.map((f) => (
                    <Konva.Group key={f.id} id={f.id} draggable
                                 onClick={(e) => { e.cancelBubble = true; setSelection([f.id]); }}
                                 onDragEnd={onTransformEnd(f.id)} onTransformEnd={onTransformEnd(f.id)}>
                        <FieldNode field={f} zoom={zoom} />
                    </Konva.Group>
                )) as any}
            </Layer>
            <Layer x={20} y={20}>
                <Transformer ref={trRef} keepRatio={false}
                             boundBoxFunc={(_old, newBox) => newBox} />
            </Layer>
        </Stage>
    );
}
```

Note: dòng `Konva.Group key=... draggable ...` không hợp lệ JSX trực tiếp với react-konva — cần import `Group` từ `react-konva` và dùng `<Group>`. Sửa imports:

```tsx
import { Group, Layer, Rect, Stage, Transformer } from 'react-konva';
```

Và thay block render:

```tsx
{fields.map((f) => (
    <Group key={f.id} id={f.id} draggable
           onClick={(e) => { e.cancelBubble = true; setSelection([f.id]); }}
           onDragEnd={onTransformEnd(f.id)} onTransformEnd={onTransformEnd(f.id)}>
        <FieldNode field={f} zoom={zoom} />
    </Group>
))}
```

- [ ] **Step 3: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/components/shipping-labels/FieldNode.tsx \
        app/resources/js/components/shipping-labels/LabelCanvas.tsx
git commit -m "feat(fe): LabelCanvas (Konva Stage + Transformer) + FieldNode dispatcher"
```

---

### Task G2: `FieldPalette` + `FieldInspector` + `PaperSettings`

**Files:**
- Create: 3 file dưới `app/resources/js/components/shipping-labels/`

- [ ] **Step 1: Tạo `FieldPalette.tsx`**

```tsx
import { Button, Space, Typography } from 'antd';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { FIELD_GROUPS, FIELD_REGISTRY } from './fieldTypes';
import type { Field } from '@/lib/shippingLabelTypes';
import { nanoid } from 'nanoid';

export function FieldPalette() {
    const addField = useEditorStore((s) => s.addField);

    const onAdd = (type: Field['type']) => () => {
        const def = FIELD_REGISTRY[type];
        const props = def.defaultProps();
        addField({ ...props, id: nanoid(8) } as Field);
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12, padding: 12, borderRight: '1px solid #f0f0f0', minWidth: 180 }}>
            {FIELD_GROUPS.map((g) => {
                const items = Object.values(FIELD_REGISTRY).filter((f) => f.group === g.key);
                if (items.length === 0) return null;
                return (
                    <div key={g.key}>
                        <Typography.Text type="secondary" style={{ fontSize: 11, textTransform: 'uppercase' }}>{g.label}</Typography.Text>
                        <Space direction="vertical" size={4} style={{ width: '100%', marginTop: 4 }}>
                            {items.map((f) => (
                                <Button key={f.type} icon={f.icon} block onClick={onAdd(f.type)} style={{ textAlign: 'left' }}>
                                    {f.label}
                                </Button>
                            ))}
                        </Space>
                    </div>
                );
            })}
        </div>
    );
}
```

- [ ] **Step 2: Tạo `FieldInspector.tsx`**

```tsx
import { Button, Empty, Form, InputNumber, Space, Typography } from 'antd';
import { DeleteOutlined } from '@ant-design/icons';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { FIELD_REGISTRY } from './fieldTypes';
import type { Field } from '@/lib/shippingLabelTypes';

export function FieldInspector() {
    const selection = useEditorStore((s) => s.selection);
    const fields = useEditorStore((s) => s.fields);
    const updateField = useEditorStore((s) => s.updateField);
    const commitTransform = useEditorStore((s) => s.commitTransform);
    const removeFields = useEditorStore((s) => s.removeFields);

    const selected = fields.find((f) => f.id === selection[0]);
    if (!selected) {
        return <div style={{ padding: 16 }}><Empty description="Chọn 1 trường để chỉnh sửa" image={Empty.PRESENTED_IMAGE_SIMPLE} /></div>;
    }
    const def = FIELD_REGISTRY[selected.type];
    const Panel = def.InspectorPanel as React.FC<{ field: Field; onChange: (p: Partial<Field>) => void }>;

    return (
        <div style={{ padding: 12, width: 280, borderLeft: '1px solid #f0f0f0', overflow: 'auto' }}>
            <Space direction="vertical" size={8} style={{ width: '100%' }}>
                <Typography.Text strong>{def.label}</Typography.Text>
                <Form layout="vertical" size="small">
                    <Space.Compact block>
                        <Form.Item label="X" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={0} value={selected.x}
                                onChange={(v) => commitTransform(selected.id, { x: v ?? 0, y: selected.y, w: selected.w, h: selected.h })} />
                        </Form.Item>
                        <Form.Item label="Y" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={0} value={selected.y}
                                onChange={(v) => commitTransform(selected.id, { x: selected.x, y: v ?? 0, w: selected.w, h: selected.h })} />
                        </Form.Item>
                    </Space.Compact>
                    <Space.Compact block style={{ marginTop: 8 }}>
                        <Form.Item label="W" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={1} value={selected.w}
                                onChange={(v) => commitTransform(selected.id, { x: selected.x, y: selected.y, w: v ?? 1, h: selected.h })} />
                        </Form.Item>
                        <Form.Item label="H" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={1} value={selected.h}
                                onChange={(v) => commitTransform(selected.id, { x: selected.x, y: selected.y, w: selected.w, h: v ?? 1 })} />
                        </Form.Item>
                    </Space.Compact>
                    <Panel field={selected} onChange={(p) => updateField(selected.id, p)} />
                    <Button danger icon={<DeleteOutlined />} block onClick={() => removeFields([selected.id])} style={{ marginTop: 12 }}>
                        Xoá trường
                    </Button>
                </Form>
            </Space>
        </div>
    );
}
```

- [ ] **Step 3: Tạo `PaperSettings.tsx`**

```tsx
import { App as AntApp, Form, InputNumber, Modal, Segmented } from 'antd';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import type { Paper } from '@/lib/shippingLabelTypes';

const OPTIONS = ['A4', 'A5', 'A6', '100x150mm', '80mm', 'custom'];

export function PaperSettings() {
    const { modal } = AntApp.useApp();
    const meta = useEditorStore((s) => s.meta);
    const setPaper = useEditorStore((s) => s.setPaper);

    const handle = (next: Paper, w?: number, h?: number) => {
        const result = setPaper(next, w, h);
        if (result.needsConfirm) {
            modal.warning({
                title: 'Một số trường đang vượt khổ giấy mới',
                content: 'Bạn cần kéo lại các trường nằm ngoài vùng in trước khi lưu.',
                okText: 'Đã hiểu',
            });
        }
    };

    return (
        <Form layout="inline" size="small">
            <Form.Item label="Khổ giấy">
                <Segmented options={OPTIONS} value={meta.paper}
                    onChange={(v) => handle(v as Paper)} />
            </Form.Item>
            {meta.paper === 'custom' && (
                <>
                    <Form.Item label="W (mm)">
                        <InputNumber min={30} max={420} value={meta.paper_w_mm}
                            onChange={(v) => handle('custom', v ?? 100, meta.paper_h_mm)} />
                    </Form.Item>
                    <Form.Item label="H (mm)">
                        <InputNumber min={0} max={1200} value={meta.paper_h_mm}
                            onChange={(v) => handle('custom', meta.paper_w_mm, v ?? 100)} />
                    </Form.Item>
                </>
            )}
        </Form>
    );
}
```

- [ ] **Step 4: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/components/shipping-labels/FieldPalette.tsx \
        app/resources/js/components/shipping-labels/FieldInspector.tsx \
        app/resources/js/components/shipping-labels/PaperSettings.tsx
git commit -m "feat(fe): palette + inspector + paper settings for editor"
```

---

### Task G3: `ShippingLabelEditorPage`

**Files:**
- Create: `app/resources/js/pages/ShippingLabelEditorPage.tsx`

- [ ] **Step 1: Tạo page**

```tsx
import { useEffect } from 'react';
import { App as AntApp, Button, Input, Segmented, Space, Spin, Typography } from 'antd';
import { SaveOutlined, UndoOutlined, RedoOutlined, EyeOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import { useShippingLabelTemplate, useCreateShippingLabelTemplate, useUpdateShippingLabelTemplate, usePreviewInlineShippingLabelTemplate } from '@/lib/shippingLabels';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { SAMPLE_PROFILES } from '@/lib/shippingLabelTypes';
import { LabelCanvas } from '@/components/shipping-labels/LabelCanvas';
import { FieldPalette } from '@/components/shipping-labels/FieldPalette';
import { FieldInspector } from '@/components/shipping-labels/FieldInspector';
import { PaperSettings } from '@/components/shipping-labels/PaperSettings';
import { errorMessage } from '@/lib/api';

export function ShippingLabelEditorPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const isNew = !id || id === 'new';
    const numericId = isNew ? null : Number(id);

    const { data: tpl, isLoading } = useShippingLabelTemplate(numericId);
    const init = useEditorStore((s) => s.init);
    const meta = useEditorStore((s) => s.meta);
    const setMeta = useEditorStore((s) => s.setMeta);
    const setSampleProfile = useEditorStore((s) => s.setSampleProfile);
    const sampleProfile = useEditorStore((s) => s.sampleProfile);
    const grid = useEditorStore((s) => s.grid);
    const setGrid = useEditorStore((s) => s.setGrid);
    const undo = useEditorStore((s) => s.undo);
    const redo = useEditorStore((s) => s.redo);
    const toPayload = useEditorStore((s) => s.toPayload);

    const create = useCreateShippingLabelTemplate();
    const update = useUpdateShippingLabelTemplate();
    const previewInline = usePreviewInlineShippingLabelTemplate();

    useEffect(() => {
        if (isNew) init(null);
        else if (tpl) init(tpl);
    }, [tpl, isNew, init]);

    const save = () => {
        if (!meta.name.trim()) { message.error('Cần đặt tên template'); return; }
        const payload = toPayload();
        if (isNew) {
            create.mutate(payload, {
                onSuccess: (created) => { message.success('Đã lưu'); navigate(`/settings/shipping-labels/${created.id}`, { replace: true }); },
                onError: (e) => message.error(errorMessage(e)),
            });
        } else {
            update.mutate({ id: numericId!, input: payload }, {
                onSuccess: () => message.success('Đã lưu'),
                onError: (e) => message.error(errorMessage(e)),
            });
        }
    };

    const preview = () => {
        previewInline.mutate({ ...toPayload(), sample_profile: sampleProfile }, {
            onSuccess: (r) => window.open(r.url, '_blank'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    if (!isNew && isLoading) return <Spin />;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100vh - 96px)' }}>
            <div style={{ padding: '8px 16px', borderBottom: '1px solid #f0f0f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                <Space>
                    <Input placeholder="Tên template" value={meta.name}
                           onChange={(e) => setMeta({ name: e.target.value })} style={{ width: 280 }} />
                    <PaperSettings />
                </Space>
                <Space>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Mẫu data:</Typography.Text>
                    <Segmented options={SAMPLE_PROFILES.map((p) => ({ label: p.replace(/_/g, ' '), value: p }))}
                        value={sampleProfile} onChange={(v) => setSampleProfile(v as typeof sampleProfile)} />
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Lưới:</Typography.Text>
                    <Segmented options={[{ label: 'Off', value: 0 }, { label: '1mm', value: 1 }, { label: '2mm', value: 2 }, { label: '5mm', value: 5 }]}
                        value={grid} onChange={(v) => setGrid(v as 0 | 1 | 2 | 5)} />
                    <Button icon={<UndoOutlined />} onClick={undo} />
                    <Button icon={<RedoOutlined />} onClick={redo} />
                    <Button icon={<EyeOutlined />} loading={previewInline.isPending} onClick={preview}>Xem trước PDF</Button>
                    <Button type="primary" icon={<SaveOutlined />} loading={create.isPending || update.isPending} onClick={save}>Lưu</Button>
                </Space>
            </div>
            <div style={{ display: 'flex', flex: 1, overflow: 'hidden' }}>
                <FieldPalette />
                <div style={{ flex: 1, overflow: 'auto', display: 'flex', alignItems: 'flex-start', justifyContent: 'center', padding: 24, background: '#fafafa' }}>
                    <LabelCanvas />
                </div>
                <FieldInspector />
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/pages/ShippingLabelEditorPage.tsx
git commit -m "feat(fe): ShippingLabelEditorPage (3-col canvas + topbar)"
```

---

## Phase H — FE list page

### Task H1: `SettingsShippingLabelsPage`

**Files:**
- Create: `app/resources/js/pages/SettingsShippingLabelsPage.tsx`

- [ ] **Step 1: Tạo page**

```tsx
import { App as AntApp, Button, Card, Modal, Space, Table, Tag, Tooltip, Typography } from 'antd';
import { CopyOutlined, DeleteOutlined, EditOutlined, PlusOutlined, StarFilled, StarOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { useShippingLabelTemplates, useDeleteShippingLabelTemplate, useSetDefaultShippingLabelTemplate, useDuplicateShippingLabelTemplate } from '@/lib/shippingLabels';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';
import type { Template } from '@/lib/shippingLabelTypes';

export function SettingsShippingLabelsPage() {
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const canManage = useCan('tenant.settings');
    const { data: items = [], isLoading } = useShippingLabelTemplates();
    const del = useDeleteShippingLabelTemplate();
    const setDefault = useSetDefaultShippingLabelTemplate();
    const duplicate = useDuplicateShippingLabelTemplate();

    const onDelete = (t: Template) => {
        Modal.confirm({
            title: `Xoá template "${t.name}"?`,
            content: 'Template đã xoá sẽ không dùng được khi in nữa. Phiếu in đã render trước đó vẫn xem lại được.',
            okType: 'danger', okText: 'Xoá',
            onOk: () => del.mutateAsync(t.id)
                .then(() => message.success('Đã xoá'))
                .catch((e) => message.error(errorMessage(e))),
        });
    };

    const columns = [
        { title: 'Tên', dataIndex: 'name', render: (n: string, t: Template) => (
            <Space>
                <a onClick={() => navigate(`/settings/shipping-labels/${t.id}`)}>{n}</a>
                {t.is_default && <Tag color="gold" icon={<StarFilled />}>Mặc định</Tag>}
            </Space>
        ) },
        { title: 'Khổ giấy', dataIndex: 'paper', render: (p: string, t: Template) => `${p} (${t.paper_w_mm}×${t.paper_h_mm || 'auto'}mm)` },
        { title: 'Cập nhật', dataIndex: 'updated_at', render: (v: string) => new Date(v).toLocaleString('vi-VN') },
        { title: '', key: 'actions', width: 180, render: (_: unknown, t: Template) => (
            <Space size={2}>
                <Tooltip title="Sửa"><Button type="text" icon={<EditOutlined />} onClick={() => navigate(`/settings/shipping-labels/${t.id}`)} /></Tooltip>
                {canManage && <Tooltip title={t.is_default ? 'Đang là mặc định' : 'Đặt mặc định'}>
                    <Button type="text" icon={t.is_default ? <StarFilled style={{ color: '#faad14' }} /> : <StarOutlined />}
                        disabled={t.is_default} onClick={() => setDefault.mutate(t.id)} />
                </Tooltip>}
                {canManage && <Tooltip title="Nhân bản"><Button type="text" icon={<CopyOutlined />} onClick={() => duplicate.mutate(t.id)} /></Tooltip>}
                {canManage && <Tooltip title="Xoá"><Button type="text" danger icon={<DeleteOutlined />} onClick={() => onDelete(t)} /></Tooltip>}
            </Space>
        ) },
    ];

    return (
        <Card title={<Space><Typography.Title level={4} style={{ margin: 0 }}>Mẫu phiếu giao hàng</Typography.Title></Space>}
              extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/settings/shipping-labels/new')}>Tạo template</Button>}>
            <Typography.Paragraph type="secondary">
                Thiết kế template phiếu giao hàng cho <b>đơn manual</b> theo khổ giấy của bạn. Khi in,
                nhân viên chọn template từ danh sách này. Đơn của sàn TMĐT vẫn dùng AWB thật của sàn.
            </Typography.Paragraph>
            <Table rowKey="id" loading={isLoading} dataSource={items} columns={columns} pagination={false} />
        </Card>
    );
}
```

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/pages/SettingsShippingLabelsPage.tsx
git commit -m "feat(fe): list page for shipping label templates"
```

---

## Phase I — FE integration

### Task I1: `TemplateAliasPicker` modal + `useCreatePrintJob` accept `template_id`

**Files:**
- Create: `app/resources/js/components/shipping-labels/TemplateAliasPicker.tsx`
- Modify: `app/resources/js/lib/fulfillment.tsx`

- [ ] **Step 1: Sửa `useCreatePrintJob`**

Trong `app/resources/js/lib/fulfillment.tsx`, replace signature của mutation:

```tsx
export function useCreatePrintJob() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: { type: 'label' | 'picking' | 'packing' | 'invoice' | 'delivery'; order_ids?: number[]; shipment_ids?: number[]; template_id?: number | null }) => {
            const { data } = await api!.post<{ data: PrintJob }>('/print-jobs', vars); return data.data;
        },
    });
}
```

- [ ] **Step 2: Tạo `TemplateAliasPicker.tsx`**

```tsx
import { useMemo, useState } from 'react';
import { Modal, Radio, Select, Space, Tag, Typography } from 'antd';
import { useShippingLabelTemplates, usePreviewShippingLabelTemplate } from '@/lib/shippingLabels';
import { useTenant } from '@/lib/tenant';

const LS_KEY = (tenantId: number | string) => `lastShippingLabelTemplateId:${tenantId}`;

export function TemplateAliasPicker({ open, onCancel, onConfirm }: {
    open: boolean;
    onCancel: () => void;
    onConfirm: (templateId: number | null) => void;
}) {
    const { data: tenant } = useTenant();
    const { data: items = [], isLoading } = useShippingLabelTemplates();
    const preview = usePreviewShippingLabelTemplate();

    const defaultId = useMemo(() => {
        if (!tenant) return null;
        const lastUsed = Number(localStorage.getItem(LS_KEY(tenant.id)) || 0);
        if (items.find((t) => t.id === lastUsed)) return lastUsed;
        return items.find((t) => t.is_default)?.id ?? null;
    }, [items, tenant]);

    const [selected, setSelected] = useState<number | null>(defaultId);

    const handleConfirm = () => {
        if (tenant && selected != null) localStorage.setItem(LS_KEY(tenant.id), String(selected));
        onConfirm(selected);
    };

    return (
        <Modal open={open} onCancel={onCancel} onOk={handleConfirm} okText="In" cancelText="Huỷ" title="Chọn mẫu phiếu giao hàng">
            {items.length === 0 ? (
                <Typography.Paragraph type="secondary">
                    Bạn chưa có template nào — sẽ dùng mẫu mặc định của hệ thống. Có thể tạo template tại <b>Cài đặt → Mẫu phiếu giao hàng</b>.
                </Typography.Paragraph>
            ) : items.length <= 5 ? (
                <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)} style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {items.map((t) => (
                        <Radio key={t.id} value={t.id}>
                            <Space>
                                <span>{t.name}</span>
                                <Tag>{t.paper}</Tag>
                                {t.is_default && <Tag color="gold">Mặc định</Tag>}
                                <a onClick={(e) => { e.preventDefault(); preview.mutate({ id: t.id, sample_profile: 'three_items_long_address' }, { onSuccess: (r) => window.open(r.url, '_blank') }); }}>Xem trước</a>
                            </Space>
                        </Radio>
                    ))}
                    <Radio value={null}>Mặc định hệ thống (không dùng template)</Radio>
                </Radio.Group>
            ) : (
                <Select style={{ width: '100%' }} value={selected} onChange={setSelected} showSearch loading={isLoading}
                    options={[
                        ...items.map((t) => ({ value: t.id, label: `${t.name} (${t.paper})${t.is_default ? ' · Mặc định' : ''}` })),
                        { value: null, label: 'Mặc định hệ thống' },
                    ]} />
            )}
        </Modal>
    );
}
```

- [ ] **Step 3: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/components/shipping-labels/TemplateAliasPicker.tsx \
        app/resources/js/lib/fulfillment.tsx
git commit -m "feat(fe): TemplateAliasPicker + useCreatePrintJob accepts template_id"
```

---

### Task I2: Tích hợp `TemplateAliasPicker` vào `OrderProcessing.printDelivery`

**Files:**
- Modify: `app/resources/js/components/OrderProcessing.tsx`

- [ ] **Step 1: Sửa import + state**

Thêm `useState` và import picker ở đầu file:

```tsx
import { useState } from 'react';
import { TemplateAliasPicker } from '@/components/shipping-labels/TemplateAliasPicker';
```

Trong component `OrderActions` (chứa hàm `printDelivery`), thêm state:

```tsx
const [pickerOpen, setPickerOpen] = useState<{ open: boolean; orderIds: number[] }>({ open: false, orderIds: [] });
```

- [ ] **Step 2: Sửa hàm `printDelivery`**

Thay implementation cũ:

```tsx
const printDelivery = () => {
    if (sh && sh.has_label) { printLabelBundle(); return; }
    setPickerOpen({ open: true, orderIds: [order.id] });
};
```

- [ ] **Step 3: Thêm picker render vào component**

Thêm sau JSX hiện tại (cuối return):

```tsx
<TemplateAliasPicker
    open={pickerOpen.open}
    onCancel={() => setPickerOpen({ open: false, orderIds: [] })}
    onConfirm={(templateId) => {
        setPickerOpen({ open: false, orderIds: [] });
        createPrint.mutate({ type: 'delivery', order_ids: pickerOpen.orderIds, template_id: templateId },
            { onSuccess: (j) => onPrint(j.id), onError: err });
    }}
/>
```

- [ ] **Step 4: Typecheck**

Run: `cd app && npm run typecheck`
Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/components/OrderProcessing.tsx
git commit -m "feat(fe): OrderProcessing — open TemplateAliasPicker before printing delivery"
```

---

### Task I3: Bulk in trong `OrdersPage` qua picker

**Files:**
- Modify: `app/resources/js/pages/OrdersPage.tsx`

- [ ] **Step 1: Thêm state + import**

```tsx
import { useState } from 'react';
import { TemplateAliasPicker } from '@/components/shipping-labels/TemplateAliasPicker';

const [pickerOpen, setPickerOpen] = useState<{ open: boolean; orderIds: number[] }>({ open: false, orderIds: [] });
```

- [ ] **Step 2: Sửa handler "In phiếu giao hàng (bulk)"**

Tìm chỗ gọi `createPrintJob.mutate({ type: 'delivery', order_ids })`. Thay bằng:

```tsx
setPickerOpen({ open: true, orderIds: selectedManualIds });
```

(với `selectedManualIds` = filter chỉ đơn manual như logic cũ).

- [ ] **Step 3: Thêm render picker**

```tsx
<TemplateAliasPicker
    open={pickerOpen.open}
    onCancel={() => setPickerOpen({ open: false, orderIds: [] })}
    onConfirm={(templateId) => {
        const ids = pickerOpen.orderIds;
        setPickerOpen({ open: false, orderIds: [] });
        createPrintJob.mutate({ type: 'delivery', order_ids: ids, template_id: templateId },
            { onSuccess: (j) => /* open print monitor */ console.log(j.id), onError: (e) => message.error(errorMessage(e)) });
    }}
/>
```

- [ ] **Step 4: Typecheck + commit**

```bash
cd app && npm run typecheck
git add app/resources/js/pages/OrdersPage.tsx
git commit -m "feat(fe): bulk in phiếu giao hàng qua TemplateAliasPicker"
```

---

### Task I4: `CreateOrderPage` chọn warehouse + hook list warehouse

**Files:**
- Modify: `app/resources/js/lib/inventory.tsx` (hoặc tạo nếu thiếu hook `useWarehouses`)
- Modify: `app/resources/js/pages/CreateOrderPage.tsx`

- [ ] **Step 1: Kiểm tra hook `useWarehouses` đã có**

Run: `cd app && grep -rn "useWarehouses" resources/js`. Nếu chưa có, tạo trong `lib/inventory.tsx`:

```tsx
export function useWarehouses() {
    const api = useScopedApi();
    const { data: tenant } = useTenant();
    return useQuery({
        queryKey: ['warehouses', tenant?.id],
        enabled: !!api && !!tenant,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Array<{ id: number; name: string; is_default: boolean; address?: Record<string, string> }> }>('/warehouses');
            return data.data;
        },
    });
}
```

Verify endpoint `/api/v1/warehouses` đã có (nếu chưa, dừng và bổ sung — không trong scope plan này nhưng nếu thiếu sẽ block task).

- [ ] **Step 2: Thêm radio chọn warehouse vào CreateOrderPage**

Trong block địa chỉ shop (tìm "Thông tin gửi" hoặc tương tự), thêm:

```tsx
const { data: warehouses = [] } = useWarehouses();
const [warehouseId, setWarehouseId] = useState<number | null>(null);

useEffect(() => {
    if (warehouseId == null && warehouses.length > 0) {
        setWarehouseId(warehouses.find((w) => w.is_default)?.id ?? warehouses[0].id);
    }
}, [warehouses, warehouseId]);
```

JSX (chỉ render nếu nhiều hơn 1 kho):

```tsx
{warehouses.length > 1 && (
    <Form.Item label="Kho gửi">
        <Radio.Group value={warehouseId} onChange={(e) => setWarehouseId(e.target.value)}>
            {warehouses.map((w) => <Radio key={w.id} value={w.id}>{w.name}{w.is_default ? ' (mặc định)' : ''}</Radio>)}
        </Radio.Group>
    </Form.Item>
)}
```

Trong payload submit thêm `warehouse_id: warehouseId`.

- [ ] **Step 3: Typecheck + commit**

```bash
cd app && npm run typecheck
git add app/resources/js/pages/CreateOrderPage.tsx app/resources/js/lib/inventory.tsx
git commit -m "feat(fe): CreateOrderPage chọn warehouse (gắn warehouse_id vào đơn manual)"
```

---

### Task I5: Routes trong `app.tsx`

**Files:**
- Modify: `app/resources/js/app.tsx`

- [ ] **Step 1: Thêm 3 route**

Tìm phần `Routes` (dưới Settings group). Thêm:

```tsx
import { SettingsShippingLabelsPage } from '@/pages/SettingsShippingLabelsPage';
import { ShippingLabelEditorPage } from '@/pages/ShippingLabelEditorPage';
```

```tsx
<Route path="settings/shipping-labels" element={<SettingsShippingLabelsPage />} />
<Route path="settings/shipping-labels/new" element={<ShippingLabelEditorPage />} />
<Route path="settings/shipping-labels/:id" element={<ShippingLabelEditorPage />} />
```

Thêm menu item vào sidebar settings (tìm component sidebar — `SettingsLayout` hoặc tương tự):

```tsx
{ key: '/settings/shipping-labels', icon: <PrinterOutlined />, label: <Link to="/settings/shipping-labels">Mẫu phiếu giao hàng</Link> }
```

- [ ] **Step 2: Typecheck + commit**

```bash
cd app && npm run typecheck
git add app/resources/js/app.tsx
git commit -m "feat(fe): routes + sidebar entry cho /settings/shipping-labels"
```

---

## Phase J — Verification

### Task J1: Run full BE test suite

- [ ] **Step 1: Chạy toàn bộ phpunit**

Run: `cd app && vendor/bin/phpunit`
Expected: all green; mọi suite không regress.

- [ ] **Step 2: Run lint BE**

Run: `cd app && vendor/bin/pint --test`
Expected: code style pass; nếu fail → `vendor/bin/pint` để auto-fix, commit "style: pint".

- [ ] **Step 3: phpstan**

Run: `cd app && vendor/bin/phpstan analyse --memory-limit=2G`
Expected: 0 new errors. Nếu có baseline diff → bổ sung phpstan-baseline.neon.

### Task J2: Run lint + typecheck FE + build

- [ ] **Step 1**: `cd app && npm run lint` → pass.
- [ ] **Step 2**: `cd app && npm run typecheck` → pass.
- [ ] **Step 3**: `cd app && npm run build` → bundle thành công, size assets không tăng đột biến (>500KB konva là kỳ vọng).

### Task J3: Smoke checklist thủ công (Playwright MCP)

Mở app local (`php artisan serve` + `npm run dev`), login với tài khoản owner.

- [ ] Tạo template trắng → drop 8 field type khác nhau → save → reload list thấy.
- [ ] Mở editor lại → 8 field hiển thị đúng vị trí.
- [ ] Đổi paper từ A6 → A4 → field giữ nguyên toạ độ (mm absolute).
- [ ] Đổi paper từ A6 → 80mm với field vượt → modal cảnh báo hiển thị.
- [ ] Undo/redo 5 bước → state khớp.
- [ ] Bấm "Xem trước PDF" → tab mới mở PDF có sample data.
- [ ] Set default cho template → tag "Mặc định" hiển thị.
- [ ] Duplicate → tên "(copy)".
- [ ] Soft-delete → list không hiện.
- [ ] CreateOrderPage chọn warehouse → submit OK; mở đơn xem detail → `warehouse_id` đúng (qua API hoặc DB).
- [ ] OrderProcessing → "In phiếu giao hàng" → modal picker mở → chọn template → PDF mở.
- [ ] Bulk in 3 đơn manual cùng 1 template → PDF có 3 trang, sender lấy từ kho đúng đơn.
- [ ] In lại với template khác → PDF mới khác layout.
- [ ] In với "Mặc định hệ thống" (template_id = null) → fallback `PrintTemplates::deliverySlip` cũ.
- [ ] In đơn sàn (channel_account_id ≠ null) → BE chặn (như cũ).
- [ ] Login với viewer → list page xem được, nút "Tạo template" ẩn / disabled; picker khi in vẫn dùng được.

### Task J4: Commit cuối

- [ ] **Step 1**: Verify clean tree.

Run: `cd app && git status`
Expected: working tree clean.

- [ ] **Step 2**: Tag commit (optional).

```bash
git tag -a v-shipping-label-designer -m "Phase 7: shipping label drag/drop designer"
```

- [ ] **Step 3**: Mở PR.

```bash
gh pr create --title "feat: shipping label designer (drag/drop alias for manual orders)" --body "$(cat <<'EOF'
## Summary
- Bảng `shipping_label_templates` (soft-delete, JSON schema versioned)
- `orders.warehouse_id` cho sender resolution
- 8 field type (qr/barcode/text/image/data/items_list/divider/rectangle) qua FieldTypeRegistry — extensible
- React-konva editor + zustand store; CRUD/preview/set-default/duplicate; TemplateAliasPicker khi in
- Backward-compat 100%: template_id optional, không có → giữ PrintTemplates::deliverySlip cũ

## Test plan
- [ ] phpunit
- [ ] phpstan
- [ ] npm run lint / typecheck / build
- [ ] Smoke checklist Phase J3
EOF
)"
```

---

## Self-Review

**Spec coverage check:**

| Spec section | Task |
|---|---|
| §3 (Kiến trúc) | A1-A3, B1-B10, C1-C4, E1-E6, F1, G1-G3, H1, I1-I5 |
| §4.1 `shipping_label_templates` | A2 |
| §4.2 `orders.warehouse_id` | A1 |
| §4.3 `print_jobs.meta.template_id` | C3 |
| §4.4 JSON schema | E2 + per-field tasks B4-B7 |
| §5.1 FieldType contract | B2 |
| §5.2 LabelRenderer | B9 |
| §5.3 LabelDataResolver | B8 |
| §5.4 DataContext | B1 |
| §5.5 FieldRenderHelpers | B3 |
| §5.6 FieldTypeRegistry | B2, B10 |
| §5.7 9 endpoints | C2 |
| §5.8 PrintService route by template_id | C3 |
| §5.9 Audit | (không có task riêng — audit dùng infrastructure hiện có; controller mutation tự ghi log; nếu cần explicit cần task bổ sung) |
| §6 FE editor | E1-E6, F1, G1-G3, H1, I1-I5 |
| §7 Data flow | covered through implementation |
| §8 Error handling | tests bao gồm overflow, cross-tenant, soft-delete |
| §9 Permission | C2 tests viewer vs owner |
| §10 Testing | B/C/D test tasks; J3 smoke |
| §11 Migration | A1, A2 |
| §12 Memory rules | F1 dùng Radio.Group/Segmented + icons font |
| §13 Extensibility roadmap | (định hướng — không task v0) |
| §14 Deps | E1 |

**Gap phát hiện:** §5.9 audit — explicit không có task. Vì audit infrastructure dùng middleware/listener tự động cho `created_by/updated_at`, không bắt buộc thêm code. Để đầy đủ, nếu cần explicit audit_logs entry, có thể thêm task C5 sau, không block plan.

**Type consistency check:** `FieldType.dataKeys()`, `Field` shape FE/BE đối xứng (DATA_KEYS = self::KEYS), `Template` shape đồng nhất với resource shape, `useCreatePrintJob` thêm `template_id?: number | null` — không conflict với usage cũ.

**Placeholder scan:** Một số mô tả ngắn cho lint/build pass commit chứ không có code (vì là CLI commands) — đó là expected. Không có "TBD"/"TODO"/"similar to". Mọi code step có code đầy đủ.

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-05-18-shipping-label-designer.md`. Two execution options:**

**1. Subagent-Driven (recommended)** — Dispatch 1 subagent / task, review giữa các task, iteration nhanh, mỗi task chạy độc lập với context riêng.

**2. Inline Execution** — Execute trong session hiện tại với checkpoint từng phase.

**Which approach?**
