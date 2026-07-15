# Chỉnh sửa hàng loạt bản nháp đăng sàn — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép sửa nhiều bản nháp đăng sàn (`listing_drafts`) **cùng nền tảng** cùng lúc trong 1 bảng — tiêu đề/mô tả/ngành hàng/thương hiệu/thuộc tính/khối lượng-kích thước/vận chuyển/giá-mã SKU con — với nút "áp dụng cho tất cả" cho các trường dùng chung, validate rõ theo từng ô, rồi Lưu hoặc Lưu & đẩy thẳng.

**Architecture:** 2 endpoint backend mới (`GET`/`PUT /listings/bulk`) xử lý nhiều nháp **độc lập từng phần tử** (lỗi 1 nháp không chặn nháp khác — theo đúng pattern `MarketplaceCloneService::bulkCloneToShops`). FE có trang bảng mới `BulkListingEditPage.tsx`, tái dùng gần như mọi component con đã có ở `ListingDraftEditorPage.tsx` (`CategoryPicker`, `AttributeForm`, `ShippingSection`/`TikTokShipping`/`ShopeeShipping`, `RichTextEditor`, `PushProgressModal`). Điểm vào từ `ListingDraftsTable.tsx` (màn "Chờ đẩy lên sàn"), truyền danh sách đã chọn qua router state.

**Tech Stack:** Laravel 11 (PHP 8.3), React 18 + TypeScript + Ant Design + TanStack Query, PHPUnit, Pint, PHPStan (Larastan level 5), ESLint + tsc.

## Global Constraints

- Lệnh PHP/Node chạy từ `app/` (không phải repo root).
- Namespace `CMBcoreSeller\` → `app/app/`.
- Tích hợp sàn: core/service KHÔNG được biết tên sàn qua `if ($provider === 'tiktok')` — chỉ tra `config('integrations.listing_limits.<provider>')` (data-driven, không phải logic rẽ nhánh theo tên sàn) và gọi qua interface `ListingValidator`/`PublisherRegistry` sẵn có.
- Mọi query model tenant-scoped tự động qua `BelongsToTenant` — không cần thêm điều kiện `tenant_id` thủ công.
- `validation_errors` lưu dạng `array<string,string>` (field → message) — khi `json_encode` ra JSON **object**, KHÔNG phải array. FE phải map đúng kiểu `Record<string,string>` (xem Task 1 — bug hiện tại đang coi nó là `string[]`).
- Route `listings/bulk` không đụng độ `listings/{id}` nhờ `whereNumber('id')` đã áp cho route động — không cần quan tâm thứ tự khai báo.
- Không có JS test runner trong repo — mọi thay đổi FE verify bằng `npm run typecheck && npm run lint && npm run build` + chạy `composer dev` thao tác tay qua trình duyệt (memory `test-verify-baseline`).
- UI: icon dùng `@ant-design/icons` (không emoji); hạn chế `<Select>` khi tập lựa chọn nhỏ (dùng `Radio`/`Checkbox`).
- Test backend baseline: `php artisan test` CHƯA green toàn cục trên `main` — chỉ chạy test liên quan Products/Integrations khi verify (memory `test-verify-baseline`).
- Mọi endpoint mới phải thêm dòng vào `docs/05-api/endpoints.md` (CLAUDE.md).

---

## File Structure

**Backend (sửa):**
- `app/app/Modules/Products/Services/ListingDraftService.php` — thêm `bulkUpdate()`.
- `app/app/Modules/Products/Http/Controllers/ListingDraftController.php` — thêm `bulkShow()`, `bulkUpdate()`.
- `app/app/Modules/Products/Http/Controllers/ListingTaxonomyController.php` — `listingLimits()` bổ sung `title_min_length`/`title_max_length`.
- `app/app/Integrations/Channels/Shopee/ShopeeListingValidator.php` — thêm giới hạn độ dài tiêu đề (hiện thiếu hẳn).
- `app/app/Integrations/Channels/TikTok/TikTokListingValidator.php` — đổi 25/255 hard-code sang đọc `config()`.
- `app/app/Integrations/Channels/Lazada/LazadaListingValidator.php` — đổi 255 hard-code sang đọc `config()`.
- `app/config/integrations.php` — `listing_limits.*` thêm `title_min_length`/`title_max_length`.
- `app/routes/api.php` — 2 route mới trong block "Listing drafts".

**Backend (tạo mới):**
- `app/app/Modules/Products/Http/Requests/BulkUpdateListingDraftRequest.php`

**Backend (test, tạo mới):**
- `app/tests/Feature/Products/BulkListingDraftTest.php`

**Backend (test, sửa):**
- `app/tests/Unit/Integrations/Shopee/ShopeeValidatorTest.php`
- `app/tests/Unit/Integrations/TikTok/TikTokValidatorTest.php`
- `app/tests/Unit/Integrations/Lazada/LazadaValidatorTest.php`

**Frontend (sửa):**
- `app/resources/js/features/products/api.ts` — sửa type `validation_errors`, thêm type/hàm bulk.
- `app/resources/js/features/products/hooks.ts` — thêm `useListingsBulk`, `useBulkUpdateListings`.
- `app/resources/js/pages/marketplace/ListingDraftEditorPage.tsx` — sửa render `validation_errors` (object, không phải array).
- `app/resources/js/features/products/ListingDraftsTable.tsx` — mở rộng chọn dòng + nút "Chỉnh sửa hàng loạt".
- `app/resources/js/routes/appRoutes.tsx` — thêm route `marketplace/listings/bulk-edit`.

**Frontend (tạo mới):**
- `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`

**Docs (sửa):**
- `docs/05-api/endpoints.md`

---

## Task 1: Sửa lỗi hiển thị `validation_errors` (object bị coi là array)

Backend lưu `validation_errors` dạng `{"categoryId": "...", "skus.0.warehouse_id": "..."}` (map field→message). Khi JSON hóa, đây là **object**, không phải mảng chuỗi. FE hiện khai type `string[]` và gọi `.length` trên nó — với 1 object thuần, `.length` luôn là `undefined`, nên điều kiện `validationErrors.length > 0` LUÔN false ⇒ banner lỗi ở trang soạn nháp đơn lẻ (`ListingDraftEditorPage.tsx:279`) không bao giờ hiện, kể cả khi có lỗi thật. Bug này phải sửa trước vì trang bảng hàng loạt (Task 10) cần đọc đúng shape này để hiện lỗi theo từng ô.

**Files:**
- Modify: `app/resources/js/features/products/api.ts:66` (interface `ListingDraft`)
- Modify: `app/resources/js/pages/marketplace/ListingDraftEditorPage.tsx:216,279-282`
- Test: không có JS test runner — verify thủ công (bước 3).

**Interfaces:**
- Produces: `ListingDraft.validation_errors: Record<string, string>` — dùng lại ở Task 5/10 cho bảng hàng loạt.

- [ ] **Bước 1: Sửa type trong `api.ts`**

Trong `app/resources/js/features/products/api.ts`, tìm dòng 66:

```ts
    validation_errors: string[];
```

Đổi thành:

```ts
    /** Map field → message, vd {"categoryId": "Phải chọn danh mục lá"}. KHÔNG phải mảng chuỗi. */
    validation_errors: Record<string, string>;
```

- [ ] **Bước 2: Sửa render trong `ListingDraftEditorPage.tsx`**

Dòng 216, đổi:

```ts
    const validationErrors = listing?.validation_errors ?? [];
```

thành:

```ts
    const validationErrors = Object.entries(listing?.validation_errors ?? {});
```

Dòng 279-282, đổi:

```tsx
            {validationErrors.length > 0 && (
                <Alert type="warning" showIcon style={{ marginBottom: 16 }} message="Cần sửa các lỗi sau trước khi đẩy lên sàn"
                    description={<List size="small" dataSource={validationErrors} renderItem={(err) => <List.Item style={{ padding: '2px 0', border: 'none' }}>{err}</List.Item>} />} />
            )}
```

thành:

```tsx
            {validationErrors.length > 0 && (
                <Alert type="warning" showIcon style={{ marginBottom: 16 }} message="Cần sửa các lỗi sau trước khi đẩy lên sàn"
                    description={<List size="small" dataSource={validationErrors} renderItem={([field, msg]) => <List.Item style={{ padding: '2px 0', border: 'none' }}>{msg} <Typography.Text type="secondary">({field})</Typography.Text></List.Item>} />} />
            )}
```

- [ ] **Bước 3: Verify thủ công**

```bash
cd app
npm run typecheck
```

Kỳ vọng: không lỗi type. Sau đó chạy `composer dev`, mở 1 bản nháp TikTok đang thiếu `category_id`/`warehouse_id` (hoặc tạo mới 1 nháp rồi vào sửa) ở `/marketplace/listings/{id}/edit`, xác nhận banner vàng "Cần sửa các lỗi sau trước khi đẩy lên sàn" **hiện ra** kèm đúng message + tên field — trước đây banner này không bao giờ hiện.

- [ ] **Bước 4: Commit**

```bash
git add app/resources/js/features/products/api.ts app/resources/js/pages/marketplace/ListingDraftEditorPage.tsx
git commit -m "fix(products): validation_errors là object field->message, không phải mảng chuỗi

Banner lỗi ở trang soạn nháp không bao giờ hiện vì .length trên object
luôn undefined. Sửa type + render bằng Object.entries — nền tảng để
trang chỉnh sửa hàng loạt (SPEC 2026-07-15) hiện lỗi đúng theo từng ô."
```

---

## Task 2: Giới hạn độ dài tiêu đề đọc từ config (thêm Shopee, refactor TikTok/Lazada)

`ShopeeListingValidator` hiện KHÔNG kiểm tra độ dài tiêu đề (chỉ kiểm tra rỗng) — lỗ hổng thật, không riêng gì tính năng hàng loạt. `TikTokListingValidator`/`LazadaListingValidator` đã có giới hạn nhưng hard-code (25/255, 255) — đổi sang đọc `config()` để đồng bộ với số hiển thị ở FE (`listing-limits` endpoint) và cấu hình được qua env như `max_images` đã làm, tránh lệch giữa số hiển thị và số validator thực sự áp dụng.

**Files:**
- Modify: `app/config/integrations.php:242-246`
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeListingValidator.php:29-31`
- Modify: `app/app/Integrations/Channels/TikTok/TikTokListingValidator.php:35-38`
- Modify: `app/app/Integrations/Channels/Lazada/LazadaListingValidator.php:28-32`
- Test: `app/tests/Unit/Integrations/Shopee/ShopeeValidatorTest.php`, `app/tests/Unit/Integrations/TikTok/TikTokValidatorTest.php`, `app/tests/Unit/Integrations/Lazada/LazadaValidatorTest.php`

**Interfaces:**
- Produces: `config('integrations.listing_limits.<provider>.title_min_length')` / `.title_max_length` — dùng ở Task 4 cho `ListingTaxonomyController::listingLimits()`.

- [ ] **Bước 1: Viết test thất bại cho Shopee (title quá dài)**

Thêm vào cuối `app/tests/Unit/Integrations/Shopee/ShopeeValidatorTest.php` (trước dấu `}` đóng class ở dòng 125):

```php
    public function test_flags_title_exceeding_max_length(): void
    {
        $draft = new ListingDraftDTO(
            title: str_repeat('a', 101),
            description: 'x',
            categoryId: '100012',
            brandId: '0',
            attributes: [],
            media: [new MediaRefDTO('img-1', 'image_id')],
            skus: [['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []]],
            logistics: [
                'channels' => [['logistics_channel_id' => 1, 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE']],
            ],
        );

        $errors = (new ShopeeListingValidator)->validate($draft);

        $this->assertArrayHasKey('title', $errors);
    }
```

- [ ] **Bước 2: Chạy test, xác nhận FAIL**

```bash
cd app
php artisan test --filter=test_flags_title_exceeding_max_length
```

Kỳ vọng: FAIL — `title` key không tồn tại trong `$errors` (validator hiện chưa kiểm tra độ dài).

- [ ] **Bước 3: Thêm `title_min_length`/`title_max_length` vào config**

Trong `app/config/integrations.php`, tìm khối (dòng 242-246):

```php
    'listing_limits' => [
        'shopee' => ['max_images' => (int) env('SHOPEE_MAX_IMAGES', 9), 'max_videos' => (int) env('SHOPEE_MAX_VIDEOS', 1)],
        'tiktok' => ['max_images' => (int) env('TIKTOK_MAX_IMAGES', 9), 'max_videos' => (int) env('TIKTOK_MAX_VIDEOS', 1)],
        'lazada' => ['max_images' => (int) env('LAZADA_MAX_IMAGES', 8), 'max_videos' => (int) env('LAZADA_MAX_VIDEOS', 1)],
    ],
```

Đổi thành:

```php
    'listing_limits' => [
        'shopee' => [
            'max_images' => (int) env('SHOPEE_MAX_IMAGES', 9),
            'max_videos' => (int) env('SHOPEE_MAX_VIDEOS', 1),
            // Shopee KHÔNG công bố số cố định trong tài liệu Open Platform (phụ thuộc
            // shop/ngành hàng qua API get_item_limit riêng) — dùng số tĩnh cấu hình được.
            'title_min_length' => (int) env('SHOPEE_TITLE_MIN_LENGTH', 0),
            'title_max_length' => (int) env('SHOPEE_TITLE_MAX_LENGTH', 100),
        ],
        'tiktok' => [
            'max_images' => (int) env('TIKTOK_MAX_IMAGES', 9),
            'max_videos' => (int) env('TIKTOK_MAX_VIDEOS', 1),
            'title_min_length' => (int) env('TIKTOK_TITLE_MIN_LENGTH', 25),
            'title_max_length' => (int) env('TIKTOK_TITLE_MAX_LENGTH', 255),
        ],
        'lazada' => [
            'max_images' => (int) env('LAZADA_MAX_IMAGES', 8),
            'max_videos' => (int) env('LAZADA_MAX_VIDEOS', 1),
            'title_min_length' => (int) env('LAZADA_TITLE_MIN_LENGTH', 0),
            'title_max_length' => (int) env('LAZADA_TITLE_MAX_LENGTH', 255),
        ],
    ],
```

- [ ] **Bước 4: Sửa `ShopeeListingValidator`**

Trong `app/app/Integrations/Channels/Shopee/ShopeeListingValidator.php`, đổi dòng 29-31:

```php
        if (trim($d->title) === '') {
            $e['title'] = 'Tên bắt buộc';
        }
```

thành:

```php
        $titleMax = (int) config('integrations.listing_limits.shopee.title_max_length', 100);
        if (trim($d->title) === '') {
            $e['title'] = 'Tên bắt buộc';
        } elseif (mb_strlen($d->title) > $titleMax) {
            $e['title'] = "Tên tối đa $titleMax ký tự";
        }
```

Cũng cập nhật docblock ở đầu file (dòng 13-20) thêm dòng `- name ≤ title_max_length (config, mặc định 100)`.

- [ ] **Bước 5: Chạy lại test, xác nhận PASS**

```bash
php artisan test --filter=ShopeeValidatorTest
```

Kỳ vọng: tất cả PASS (kể cả test mới + các test cũ `test_passes_valid_draft` với title ngắn).

- [ ] **Bước 6: Refactor TikTok sang đọc config + test**

Trong `app/app/Integrations/Channels/TikTok/TikTokListingValidator.php`, đổi dòng 35-38:

```php
        $titleLen = mb_strlen($draft->title);
        if ($titleLen < 25 || $titleLen > 255) {
            $errors['title'] = 'VN: tiêu đề 25–255 ký tự';
        }
```

thành:

```php
        $titleMin = (int) config('integrations.listing_limits.tiktok.title_min_length', 25);
        $titleMax = (int) config('integrations.listing_limits.tiktok.title_max_length', 255);
        $titleLen = mb_strlen($draft->title);
        if ($titleLen < $titleMin || $titleLen > $titleMax) {
            $errors['title'] = "VN: tiêu đề {$titleMin}–{$titleMax} ký tự";
        }
```

Thêm test xác nhận config override hoạt động — thêm vào cuối `app/tests/Unit/Integrations/TikTok/TikTokValidatorTest.php` (trước dấu `}` đóng class):

```php
    public function test_title_length_reads_from_config(): void
    {
        config(['integrations.listing_limits.tiktok.title_min_length' => 5]);
        config(['integrations.listing_limits.tiktok.title_max_length' => 10]);

        $draft = new ListingDraftDTO(
            title: 'Áo thun', // 7 ký tự — hợp lệ với 5-10, KHÔNG hợp lệ với mặc định 25-255
            description: 'desc',
            categoryId: '600001',
            brandId: '700001',
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [['seller_sku' => 'S1', 'price' => 1000, 'stock' => 1, 'sale_props' => [], 'warehouse_id' => 'WH1']],
            logistics: ['package_weight' => 0.5],
        );

        $errors = (new TikTokListingValidator)->validate($draft);

        $this->assertArrayNotHasKey('title', $errors);
    }
```

- [ ] **Bước 7: Chạy test TikTok, xác nhận PASS**

```bash
php artisan test --filter=TikTokValidatorTest
```

- [ ] **Bước 8: Refactor Lazada sang đọc config + test**

Trong `app/app/Integrations/Channels/Lazada/LazadaListingValidator.php`, đổi dòng 28-32:

```php
        if (trim($d->title) === '') {
            $e['title'] = 'Tên sản phẩm bắt buộc';
        } elseif (mb_strlen($d->title) > 255) {
            $e['title'] = 'Tên tối đa 255 ký tự';
        }
```

thành:

```php
        $titleMax = (int) config('integrations.listing_limits.lazada.title_max_length', 255);
        if (trim($d->title) === '') {
            $e['title'] = 'Tên sản phẩm bắt buộc';
        } elseif (mb_strlen($d->title) > $titleMax) {
            $e['title'] = "Tên tối đa $titleMax ký tự";
        }
```

Thêm test vào cuối `app/tests/Unit/Integrations/Lazada/LazadaValidatorTest.php` (trước dấu `}` đóng class):

```php
    public function test_title_exceeding_configured_max_is_flagged(): void
    {
        config(['integrations.listing_limits.lazada.title_max_length' => 10]);

        $draft = new ListingDraftDTO(
            title: str_repeat('a', 11),
            description: '<p>x</p>',
            categoryId: '3',
            brandId: '40516',
            attributes: ['warranty_type' => 'No Warranty'],
            media: [new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg', 'cdn_url')],
            skus: [[
                'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]],
            logistics: [],
        );

        $errors = (new LazadaListingValidator)->validate($draft);

        $this->assertArrayHasKey('title', $errors);
    }
```

- [ ] **Bước 9: Chạy toàn bộ 3 test suite validator, xác nhận PASS**

```bash
php artisan test --filter=ShopeeValidatorTest
php artisan test --filter=TikTokValidatorTest
php artisan test --filter=LazadaValidatorTest
```

Kỳ vọng: tất cả PASS, không test cũ nào vỡ.

- [ ] **Bước 10: Pint + PHPStan**

```bash
vendor/bin/pint --test app/Integrations/Channels/Shopee/ShopeeListingValidator.php app/Integrations/Channels/TikTok/TikTokListingValidator.php app/Integrations/Channels/Lazada/LazadaListingValidator.php app/config/integrations.php
vendor/bin/phpstan analyse app/Integrations/Channels/Shopee/ShopeeListingValidator.php app/Integrations/Channels/TikTok/TikTokListingValidator.php app/Integrations/Channels/Lazada/LazadaListingValidator.php
```

Kỳ vọng: cả 2 lệnh pass (chạy `vendor/bin/pint` không có `--test` để auto-fix nếu fail).

- [ ] **Bước 11: Commit**

```bash
git add app/config/integrations.php app/app/Integrations/Channels/Shopee/ShopeeListingValidator.php app/app/Integrations/Channels/TikTok/TikTokListingValidator.php app/app/Integrations/Channels/Lazada/LazadaListingValidator.php app/tests/Unit/Integrations
git commit -m "fix(integrations): Shopee thiếu giới hạn độ dài tiêu đề + đồng bộ config 3 sàn

ShopeeListingValidator trước đây chỉ kiểm tra title rỗng, không có max-length
— thêm giới hạn cấu hình được (mặc định 100, Shopee không công bố số cố định
trong tài liệu Open Platform). TikTok/Lazada đổi từ hard-code 25/255, 255
sang đọc config, đồng bộ với số hiển thị ở FE (listing-limits endpoint)."
```

---

## Task 3: `ListingDraftService::bulkUpdate()`

**Files:**
- Modify: `app/app/Modules/Products/Services/ListingDraftService.php` (thêm method mới, đặt sau `update()` ở dòng ~216)
- Test: `app/tests/Feature/Products/BulkListingDraftTest.php` (tạo mới)

**Interfaces:**
- Consumes: `ListingDraftService::update(int $id, array $data): ListingDraft` (đã có, dòng 106).
- Produces: `ListingDraftService::bulkUpdate(array $items): array` trả `list<array{id:int, status:string, validation_errors:array|null}>` — dùng ở Task 4 (`ListingDraftController::bulkUpdate`).

- [ ] **Bước 1: Viết test thất bại**

Tạo `app/tests/Feature/Products/BulkListingDraftTest.php`:

```php
<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\Product;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkListingDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $account = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada',
            'external_shop_id' => 'shop',
            'shop_name' => 'Shop',
            'shop_region' => 'VN',
            'status' => 'active',
            'access_token' => 'tok',
        ]);
        $this->accountId = (int) $account->getKey();
    }

    private function makeDraft(string $name): ListingDraft
    {
        $product = Product::create(['tenant_id' => $this->tenant->getKey(), 'name' => $name]);
        $product->skus()->create([
            'tenant_id' => $this->tenant->getKey(),
            'sku_code' => 'SKU-'.$name,
            'name' => $name,
            'base_unit' => 'cái',
            'cost_price' => 20000,
            'cost_method' => Sku::COST_AVERAGE,
            'ref_sale_price' => 35000,
        ]);

        return app(ListingDraftService::class)->createDraft((int) $product->getKey(), $this->accountId, 'lazada');
    }

    public function test_bulk_update_saves_each_item_independently(): void
    {
        $draft1 = $this->makeDraft('Áo 1');
        $draft2 = $this->makeDraft('Áo 2');

        $items = [
            [
                'id' => $draft1->getKey(),
                'category_id' => '3',
                'brand_id' => '40516',
                'skus' => [[
                    'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                    'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
                ]],
            ],
            [
                // draft2 KHÔNG gửi category_id ⇒ vẫn thiếu ⇒ phải ở lại 'draft' kèm lỗi.
                'id' => $draft2->getKey(),
                'skus' => [[
                    'seller_sku' => 'S2', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                ]],
            ],
        ];

        $results = app(ListingDraftService::class)->bulkUpdate($items);

        $this->assertCount(2, $results);

        $r1 = collect($results)->firstWhere('id', $draft1->getKey());
        $this->assertSame('ready', $r1['status']);
        $this->assertNull($r1['validation_errors']);

        $r2 = collect($results)->firstWhere('id', $draft2->getKey());
        $this->assertSame('draft', $r2['status']);
        $this->assertArrayHasKey('categoryId', $r2['validation_errors']);
    }

    public function test_bulk_update_one_item_missing_does_not_abort_others(): void
    {
        $draft1 = $this->makeDraft('Áo 1');

        $items = [
            ['id' => 999999, 'category_id' => '3'],
            ['id' => $draft1->getKey(), 'category_id' => '3', 'brand_id' => '40516', 'skus' => [[
                'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]]],
        ];

        $results = app(ListingDraftService::class)->bulkUpdate($items);

        $this->assertCount(2, $results);
        $failed = collect($results)->firstWhere('id', 999999);
        $this->assertSame('error', $failed['status']);
        $ok = collect($results)->firstWhere('id', $draft1->getKey());
        $this->assertSame('ready', $ok['status']);
    }
}
```

- [ ] **Bước 2: Chạy test, xác nhận FAIL**

```bash
cd app
php artisan test --filter=BulkListingDraftTest
```

Kỳ vọng: FAIL — `Call to undefined method ListingDraftService::bulkUpdate()`.

- [ ] **Bước 3: Cài đặt `bulkUpdate()`**

Trong `app/app/Modules/Products/Services/ListingDraftService.php`, thêm method sau `update()` (ngay sau dấu `}` đóng method ở dòng 216, trước method `cloneToChannel`):

```php
    /**
     * Lưu nhiều nháp cùng lúc — mỗi item xử lý ĐỘC LẬP (try/catch), lỗi 1 nháp
     * không chặn các nháp còn lại. Dùng cho màn "Chỉnh sửa hàng loạt" (SPEC
     * 2026-07-15). Cùng chữ ký field với {@see self::update()}, thêm khóa 'id'.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return list<array{id:int, status:string, validation_errors:array<string,string>|null}>
     */
    public function bulkUpdate(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            try {
                $draft = $this->update($id, $item);
                $out[] = ['id' => $id, 'status' => $draft->status, 'validation_errors' => $draft->validation_errors];
            } catch (\Throwable $e) {
                $out[] = ['id' => $id, 'status' => 'error', 'validation_errors' => ['_error' => $e->getMessage()]];
            }
        }

        return $out;
    }
```

- [ ] **Bước 4: Chạy lại test, xác nhận PASS**

```bash
php artisan test --filter=BulkListingDraftTest
```

- [ ] **Bước 5: Pint + PHPStan**

```bash
vendor/bin/pint --test app/Modules/Products/Services/ListingDraftService.php
vendor/bin/phpstan analyse app/Modules/Products/Services/ListingDraftService.php
```

- [ ] **Bước 6: Commit**

```bash
git add app/app/Modules/Products/Services/ListingDraftService.php app/tests/Feature/Products/BulkListingDraftTest.php
git commit -m "feat(products): ListingDraftService::bulkUpdate() lưu nhiều nháp độc lập

Mỗi item try/catch riêng — 1 nháp lỗi không chặn các nháp khác, theo đúng
pattern MarketplaceCloneService::bulkCloneToShops đã có. Nền tảng cho
endpoint PUT /listings/bulk (SPEC 2026-07-15)."
```

---

## Task 4: Endpoint `GET`/`PUT /listings/bulk`

**Files:**
- Create: `app/app/Modules/Products/Http/Requests/BulkUpdateListingDraftRequest.php`
- Modify: `app/app/Modules/Products/Http/Controllers/ListingDraftController.php`
- Modify: `app/routes/api.php` (block "Listing drafts", ~dòng 300-308)
- Modify: `docs/05-api/endpoints.md` (~dòng 494-499, khối `/api/v1/listings/*`)
- Test: `app/tests/Feature/Products/BulkListingDraftTest.php` (thêm test HTTP)

**Interfaces:**
- Consumes: `ListingDraftService::bulkUpdate()` (Task 3).
- Produces: `GET /api/v1/listings/bulk?ids=1,2,3` → `{ data: ListingDraftResource[] }`; `PUT /api/v1/listings/bulk` body `{ items: [...] }` → `{ data: list<{id,status,validation_errors}> }` — dùng ở Task 5 (FE `api.ts`).

- [ ] **Bước 1: Viết test HTTP thất bại**

Thêm vào cuối `app/tests/Feature/Products/BulkListingDraftTest.php` (trước dấu `}` đóng class):

```php
    public function test_get_bulk_returns_full_drafts_for_selected_ids(): void
    {
        $draft1 = $this->makeDraft('Áo 1');
        $draft2 = $this->makeDraft('Áo 2');

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson('/api/v1/listings/bulk?ids='.$draft1->getKey().','.$draft2->getKey());

        $res->assertOk();
        $this->assertCount(2, $res->json('data'));
        $this->assertNotEmpty($res->json('data.0.skus'));
    }

    public function test_get_bulk_rejects_mixed_providers(): void
    {
        $draft1 = $this->makeDraft('Áo 1');

        $tiktokAccount = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => 'shop-tt',
            'shop_name' => 'Shop TikTok',
            'shop_region' => 'VN',
            'status' => 'active',
            'access_token' => 'tok-tt',
        ]);
        $product2 = Product::create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Áo 2']);
        $draft2 = app(ListingDraftService::class)->createDraft((int) $product2->getKey(), (int) $tiktokAccount->getKey(), 'tiktok');

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson('/api/v1/listings/bulk?ids='.$draft1->getKey().','.$draft2->getKey());

        $res->assertStatus(422);
    }

    public function test_put_bulk_saves_each_item_and_returns_status(): void
    {
        $draft1 = $this->makeDraft('Áo 1');

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson('/api/v1/listings/bulk', [
                'items' => [[
                    'id' => $draft1->getKey(),
                    'category_id' => '3',
                    'brand_id' => '40516',
                    'skus' => [[
                        'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                        'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
                    ]],
                ]],
            ]);

        $res->assertOk();
        $this->assertSame('ready', $res->json('data.0.status'));
    }
```

- [ ] **Bước 2: Chạy test, xác nhận FAIL**

```bash
cd app
php artisan test --filter=BulkListingDraftTest
```

Kỳ vọng: các test HTTP mới FAIL với 404 (route chưa tồn tại).

- [ ] **Bước 3: Tạo `BulkUpdateListingDraftRequest`**

Tạo `app/app/Modules/Products/Http/Requests/BulkUpdateListingDraftRequest.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk-editing nhiều listing draft cùng lúc (SPEC 2026-07-15). Mỗi
 * item lặp lại đúng field của {@see UpdateListingDraftRequest} + khóa 'id'.
 */
class BulkUpdateListingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer'],
            'items.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.description' => ['sometimes', 'nullable', 'string'],
            'items.*.video_url' => ['sometimes', 'nullable', 'string'],
            'items.*.category_id' => ['sometimes', 'nullable', 'string'],
            'items.*.brand_id' => ['sometimes', 'nullable', 'string'],
            'items.*.attributes' => ['sometimes', 'array'],
            'items.*.media_refs' => ['sometimes', 'array'],
            'items.*.logistics' => ['sometimes', 'array'],
            'items.*.skus' => ['sometimes', 'array'],
        ];
    }
}
```

- [ ] **Bước 4: Thêm 2 method vào `ListingDraftController`**

Trong `app/app/Modules/Products/Http/Controllers/ListingDraftController.php`, thêm import ở đầu file (sau dòng `use CMBcoreSeller\Modules\Products\Http\Requests\CloneListingDraftRequest;`):

```php
use CMBcoreSeller\Modules\Products\Http\Requests\BulkUpdateListingDraftRequest;
```

Thêm 2 method mới, ngay trước method `show()` (dòng 36):

```php
    /** GET /api/v1/listings/bulk?ids=1,2,3 — nhiều nháp đầy đủ, phải CÙNG provider. */
    public function bulkShow(\Illuminate\Http\Request $request): JsonResponse
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))));
        abort_if($ids === [], 422, 'Thiếu danh sách ids.');

        $drafts = ListingDraft::with(['product', 'skus.masterSku'])->whereIn('id', $ids)->get();
        abort_if($drafts->pluck('provider')->unique()->count() > 1, 422, 'Chỉ chọn được các listing cùng 1 sàn.');

        return response()->json(['data' => ListingDraftResource::collection($drafts)]);
    }

    /** PUT /api/v1/listings/bulk — lưu nhiều nháp, mỗi nháp xử lý độc lập. */
    public function bulkUpdate(BulkUpdateListingDraftRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->svc->bulkUpdate($request->validated('items'))]);
    }

```

- [ ] **Bước 5: Đăng ký route**

Trong `app/routes/api.php`, sau dòng 308 (`Route::post('listings/{id}/ai-description', ...)`), thêm:

```php
            Route::get('listings/bulk', [ListingDraftController::class, 'bulkShow'])->name('listing-drafts.bulk-show');
            Route::put('listings/bulk', [ListingDraftController::class, 'bulkUpdate'])->name('listing-drafts.bulk-update');
```

- [ ] **Bước 6: Chạy lại test, xác nhận PASS**

```bash
cd app
php artisan test --filter=BulkListingDraftTest
```

Kỳ vọng: tất cả PASS (kể cả 2 test của Task 3).

- [ ] **Bước 7: Cập nhật `docs/05-api/endpoints.md`**

Trong `docs/05-api/endpoints.md`, tìm dòng 495 (`| GET | /api/v1/listings/{id} | ...`), thêm 2 dòng NGAY TRƯỚC nó:

```
| GET | `/api/v1/listings/bulk` | sanctum + tenant | `ids` (CSV, ≤50) | `{ data: ListingDraftResource[] }` — nhiều nháp đầy đủ kèm SKU, dùng cho màn "Chỉnh sửa hàng loạt". Các `ids` không CÙNG `provider` ⇒ `422`. (SPEC 2026-07-15) |
| PUT | `/api/v1/listings/bulk` | sanctum + tenant | `{ items: [{id, ...như PUT /listings/{id}}] (≤50) }` | `{ data: [{id, status, validation_errors}] }` — lưu nhiều nháp, MỖI nháp xử lý độc lập (lỗi 1 nháp không chặn nháp khác, `status:'error'` nếu id không tồn tại). (SPEC 2026-07-15) |
```

- [ ] **Bước 8: Pint + PHPStan**

```bash
cd app
vendor/bin/pint --test app/Modules/Products/Http/Controllers/ListingDraftController.php app/Modules/Products/Http/Requests/BulkUpdateListingDraftRequest.php
vendor/bin/phpstan analyse app/Modules/Products/Http/Controllers/ListingDraftController.php app/Modules/Products/Http/Requests/BulkUpdateListingDraftRequest.php
```

- [ ] **Bước 9: Chạy toàn bộ test Products để chắc không phá gì**

```bash
php artisan test --filter=Products
```

- [ ] **Bước 10: Commit**

```bash
git add app/app/Modules/Products/Http/Controllers/ListingDraftController.php app/app/Modules/Products/Http/Requests/BulkUpdateListingDraftRequest.php app/routes/api.php app/tests/Feature/Products/BulkListingDraftTest.php docs/05-api/endpoints.md
git commit -m "feat(products): endpoint GET/PUT /listings/bulk cho màn chỉnh sửa hàng loạt

GET trả nhiều nháp đầy đủ (chặn trộn provider, 422). PUT lưu nhiều nháp,
mỗi nháp xử lý độc lập qua ListingDraftService::bulkUpdate(). SPEC 2026-07-15."
```

---

## Task 5: FE — `api.ts` + `hooks.ts` cho bulk

**Files:**
- Modify: `app/resources/js/features/products/api.ts`
- Modify: `app/resources/js/features/products/hooks.ts`

**Interfaces:**
- Consumes: `GET/PUT /listings/bulk` (Task 4), `ListingDraft.validation_errors: Record<string,string>` (Task 1).
- Produces: `getListingsBulk(client, ids): Promise<ListingDraft[]>`, `updateListingsBulk(client, items): Promise<BulkUpdateResult[]>`, `useListingsBulk(ids): UseQueryResult<ListingDraft[]>`, `useBulkUpdateListings(): UseMutationResult` — dùng ở Task 7-11 (`BulkListingEditPage.tsx`).

- [ ] **Bước 1: Thêm type + hàm API trong `api.ts`**

Trong `app/resources/js/features/products/api.ts`, thêm vào cuối file (sau `getBrands`, dòng 399):

```ts
/* ============================================================================
 * Chỉnh sửa hàng loạt (SPEC 2026-07-15) — sửa nhiều nháp cùng 1 provider cùng lúc
 * ========================================================================== */

export interface BulkUpdateItem extends UpdateListingPayload {
    id: number;
}

export interface BulkUpdateResult {
    id: number;
    status: ListingStatus | 'error';
    validation_errors: Record<string, string> | null;
}

export async function getListingsBulk(client: AxiosInstance, ids: number[]): Promise<ListingDraft[]> {
    const { data } = await client.get<{ data: ListingDraft[] }>('/listings/bulk', {
        params: { ids: ids.join(',') },
    });
    return data.data;
}

export async function updateListingsBulk(client: AxiosInstance, items: BulkUpdateItem[]): Promise<BulkUpdateResult[]> {
    const { data } = await client.put<{ data: BulkUpdateResult[] }>('/listings/bulk', { items });
    return data.data;
}
```

- [ ] **Bước 2: Thêm hook trong `hooks.ts`**

Trong `app/resources/js/features/products/hooks.ts`, thêm vào phần import (dòng 5-32), bổ sung:

```ts
    getListingsBulk,
    updateListingsBulk,
    type BulkUpdateItem,
    type BulkUpdateResult,
```

Thêm 2 hook mới vào cuối file (sau `useBulkPush`, trước dòng `export type { PushBatch };`):

```ts
export function useListingsBulk(ids: number[]) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    const key = [...ids].sort((a, b) => a - b).join(',');
    return useQuery({
        queryKey: ['listings-bulk', tenantId, key],
        enabled: client != null && ids.length > 0,
        queryFn: () => getListingsBulk(client!, ids),
    });
}

export function useBulkUpdateListings() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (items: BulkUpdateItem[]) => updateListingsBulk(client!, items),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}
```

- [ ] **Bước 3: Verify**

```bash
cd app
npm run typecheck
```

Kỳ vọng: không lỗi type (chưa có gì dùng các hàm này nên chỉ kiểm tra tự-consistent).

- [ ] **Bước 4: Commit**

```bash
git add app/resources/js/features/products/api.ts app/resources/js/features/products/hooks.ts
git commit -m "feat(products): FE api/hooks cho GET+PUT /listings/bulk (SPEC 2026-07-15)"
```

---

## Task 6: FE — `ListingDraftsTable.tsx` điểm vào "Chỉnh sửa hàng loạt"

**Files:**
- Modify: `app/resources/js/features/products/ListingDraftsTable.tsx`

**Interfaces:**
- Produces: điều hướng `navigate('/marketplace/listings/bulk-edit', { state: { rows } })` với `rows: Array<{ id:number; productName:string; productImage:string|null; shopName:string; provider:string }>` — Task 7 đọc `location.state` này.

- [ ] **Bước 1: Nới điều kiện chọn dòng + thêm state kiểm tra cùng-provider**

Trong `app/resources/js/features/products/ListingDraftsTable.tsx`, thêm import `useNavigate` đã có sẵn (dòng 2). Tìm đoạn `rowSelection` (dòng 223-231):

```tsx
                rowSelection={
                    showPush
                        ? {
                              selectedRowKeys,
                              onChange: (keys) => setSelectedRowKeys(keys as number[]),
                              getCheckboxProps: (r) => ({ disabled: r.status !== 'ready' }),
                          }
                        : undefined
                }
```

Đổi thành (cho chọn cả `draft`/`failed`, chỉ chặn `pushing`/`reviewing`/`live`/`published`):

```tsx
                rowSelection={
                    showPush
                        ? {
                              selectedRowKeys,
                              onChange: (keys) => setSelectedRowKeys(keys as number[]),
                              getCheckboxProps: (r) => ({ disabled: !['draft', 'ready', 'failed'].includes(r.status) }),
                          }
                        : undefined
                }
```

- [ ] **Bước 2: Tính danh sách dòng đang chọn + kiểm tra cùng provider**

Sau đoạn `readyIds` (dòng 84-87), thêm:

```tsx
    const selectedRows = useMemo(
        () => rows.filter((r) => selectedRowKeys.includes(r.id)),
        [rows, selectedRowKeys],
    );
    const selectedProviders = useMemo(
        () => Array.from(new Set(selectedRows.map((r) => r.provider))),
        [selectedRows],
    );
    const bulkEditDisabled = selectedRows.length === 0 || selectedProviders.length > 1;
```

- [ ] **Bước 3: Thêm handler điều hướng**

Sau `handleBulkPush` (dòng 108-117), thêm:

```tsx
    const handleBulkEdit = () => {
        if (bulkEditDisabled) return;
        navigate('/marketplace/listings/bulk-edit', {
            state: {
                rows: selectedRows.map((r) => ({
                    id: r.id,
                    productName: r.productName,
                    productImage: r.productImage,
                    shopName: shopName(r.channel_account_id),
                    provider: r.provider,
                })),
            },
        });
    };
```

- [ ] **Bước 4: Thêm nút vào thanh hành động**

Tìm khối `{showPush && (...)}` (dòng 202-215), thêm nút mới cạnh "Đẩy hàng loạt":

```tsx
            {showPush && (
                <Space style={{ marginBottom: 12 }}>
                    <Button
                        type="primary"
                        icon={<CloudUploadOutlined />}
                        disabled={readyIds.length === 0}
                        loading={bulkPush.isPending}
                        onClick={handleBulkPush}
                    >
                        Đẩy hàng loạt{readyIds.length ? ` (${readyIds.length})` : ''}
                    </Button>
                    <Tooltip title={selectedProviders.length > 1 ? 'Chỉ chọn được các listing cùng 1 sàn' : undefined}>
                        <Button icon={<EditOutlined />} disabled={bulkEditDisabled} onClick={handleBulkEdit}>
                            Chỉnh sửa hàng loạt{selectedRows.length ? ` (${selectedRows.length})` : ''}
                        </Button>
                    </Tooltip>
                    <Typography.Text type="secondary">Chọn các listing Nháp/Sẵn sàng/Lỗi để sửa hoặc đẩy cùng lúc.</Typography.Text>
                </Space>
            )}
```

- [ ] **Bước 5: Thêm import còn thiếu**

Đầu file, dòng 5 (`import { CloudUploadOutlined, CopyOutlined, DeleteOutlined, EditOutlined, LoadingOutlined } from '@ant-design/icons';`) đã có sẵn `EditOutlined` — không cần đổi. Thêm `Tooltip` vào import antd (dòng 3):

```tsx
import { App as AntApp, Badge, Button, Empty, Image, Popconfirm, Space, Table, Tag, Tooltip, Typography } from 'antd';
```

- [ ] **Bước 6: Verify**

```bash
cd app
npm run typecheck && npm run lint
```

Sau đó `composer dev`, vào "Chờ đẩy lên sàn", tick vài dòng cùng provider → nút "Chỉnh sửa hàng loạt (N)" sáng lên và bấm được (trang đích chưa tồn tại, sẽ tạo ở Task 7 — tạm thời chấp nhận trang trắng/404). Tick lẫn 2 provider khác nhau → nút xám + tooltip đúng.

- [ ] **Bước 7: Commit**

```bash
git add app/resources/js/features/products/ListingDraftsTable.tsx
git commit -m "feat(products): nút Chỉnh sửa hàng loạt ở màn Chờ đẩy lên sàn (SPEC 2026-07-15)

Mở rộng chọn dòng sang Nháp/Lỗi (trước chỉ Sẵn sàng); chặn chọn lẫn nhiều
provider (chỉ vô hiệu hóa nút, không chặn tick). Trang đích ở Task 7."
```

---

## Task 7: FE — `BulkListingEditPage.tsx` khung sườn (route, tải dữ liệu, bảng chỉ đọc + SKU con)

**Files:**
- Create: `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`
- Modify: `app/resources/js/routes/appRoutes.tsx`

**Interfaces:**
- Consumes: `useListingsBulk`, `ListingDraft`, `ListingDraftSku` (Task 5), router state `rows` từ Task 6.
- Produces: state cục bộ `BulkEditRow[]` (id, productName, productImage, shopName, provider, channelAccountId, name, description, categoryId, brandId, attributes, mediaRefs, logistics, skus, status, validationErrors) + hàm `applyToAllRows(patch)` — Task 8-11 dùng để render/sửa từng cột và nút "Áp dụng cho tất cả".

- [ ] **Bước 1: Đăng ký route**

Trong `app/resources/js/routes/appRoutes.tsx`, thêm import sau dòng 13 (`import { ListingDraftEditorPage } ...`):

```tsx
import { BulkListingEditPage } from '@/pages/marketplace/BulkListingEditPage';
```

Thêm route sau dòng 95 (`<Route path="marketplace/listings/:id/edit" .../>`):

```tsx
            <Route path="marketplace/listings/bulk-edit" element={<BulkListingEditPage />} />
```

- [ ] **Bước 2: Tạo `BulkListingEditPage.tsx` — khung sườn + bảng chỉ đọc**

Tạo `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`:

```tsx
import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Image, Result, Space, Spin, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useListingsBulk } from '@/features/products/hooks';
import type { ListingDraftSku } from '@/features/products/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    failed: { color: 'red', label: 'Lỗi' },
};

/** Metadata hiển thị truyền từ ListingDraftsTable qua router state — tránh fetch lại. */
interface RowMeta {
    id: number;
    productName: string;
    productImage: string | null;
    shopName: string;
    provider: string;
}

/** Dòng đang sửa trong bảng — gộp metadata hiển thị + dữ liệu đầy đủ lấy về từ GET /listings/bulk. */
export interface BulkEditRow {
    id: number;
    productName: string;
    productImage: string | null;
    shopName: string;
    provider: string;
    channelAccountId: number;
    name: string;
    description: string;
    categoryId: string | null;
    brandId: string | null;
    attributes: Record<string, unknown>;
    mediaRefs: string[];
    logistics: Record<string, unknown>;
    skus: ListingDraftSku[];
    status: string;
    validationErrors: Record<string, string>;
}

/**
 * Trang bảng sửa nhiều bản nháp CÙNG NỀN TẢNG cùng lúc (SPEC 2026-07-15).
 * Điểm vào: `ListingDraftsTable` → nút "Chỉnh sửa hàng loạt", truyền `rows` qua
 * router state. Không có state (tải lại trang) ⇒ quay về danh sách.
 */
export function BulkListingEditPage() {
    const navigate = useNavigate();
    const location = useLocation();
    const { message } = AntApp.useApp();

    const rowsMeta = (location.state as { rows?: RowMeta[] } | null)?.rows ?? null;
    const ids = useMemo(() => (rowsMeta ?? []).map((r) => r.id), [rowsMeta]);

    const { data: fetched, isLoading, isError, error } = useListingsBulk(ids);
    const [rows, setRows] = useState<BulkEditRow[] | null>(null);

    useEffect(() => {
        if (!fetched || !rowsMeta) return;
        setRows(
            rowsMeta
                .map((meta): BulkEditRow | null => {
                    const d = fetched.find((x) => x.id === meta.id);
                    if (!d) return null;
                    return {
                        id: d.id,
                        productName: meta.productName,
                        productImage: meta.productImage,
                        shopName: meta.shopName,
                        provider: d.provider,
                        channelAccountId: d.channel_account_id,
                        name: d.name ?? '',
                        description: d.description ?? '',
                        categoryId: d.category_id,
                        brandId: d.brand_id,
                        attributes: d.attributes ?? {},
                        mediaRefs: d.media_refs ?? [],
                        logistics: d.logistics ?? {},
                        skus: d.skus,
                        status: d.status,
                        validationErrors: d.validation_errors ?? {},
                    };
                })
                .filter((r): r is BulkEditRow => r !== null),
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fetched]);

    const back = () => navigate('/marketplace/to-push');

    /** Ghi đè 1 field lên MỌI dòng đang có trong bảng — dùng cho nút "Áp dụng cho tất cả". */
    const applyToAllRows = (patch: Partial<Pick<BulkEditRow, 'categoryId' | 'brandId' | 'attributes' | 'logistics'>>) => {
        setRows((prev) => (prev ? prev.map((r) => ({ ...r, ...patch })) : prev));
    };

    const updateRow = (id: number, patch: Partial<BulkEditRow>) => {
        setRows((prev) => (prev ? prev.map((r) => (r.id === id ? { ...r, ...patch } : r)) : prev));
    };

    const skuColumns: ColumnsType<ListingDraftSku> = [
        { title: 'SKU người bán', dataIndex: 'seller_sku', width: 160 },
        { title: 'Giá (VND)', dataIndex: 'price', width: 120 },
        { title: 'Tồn đẩy sàn', dataIndex: 'stock', width: 100 },
    ];

    const columns: ColumnsType<BulkEditRow> = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, r) => (
                <Space>
                    {r.productImage ? (
                        <Image src={r.productImage} width={40} height={40} style={{ objectFit: 'cover', borderRadius: 6 }} />
                    ) : (
                        <div style={{ width: 40, height: 40, background: '#F1F5F9', borderRadius: 6 }} />
                    )}
                    <Typography.Text>{r.productName}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Gian hàng',
            key: 'shop',
            render: (_, r) => (
                <Space size={4}>
                    <span>{r.shopName}</span>
                    <Tag>{r.provider}</Tag>
                </Space>
            ),
        },
        {
            title: 'Trạng thái',
            key: 'status',
            width: 120,
            render: (_, r) => {
                const meta = STATUS_TAG[r.status] ?? STATUS_TAG.draft;
                return <Tag color={meta.color}>{meta.label}</Tag>;
            },
        },
    ];

    if (!rowsMeta || rowsMeta.length === 0) {
        return (
            <Result
                status="warning"
                title="Chưa chọn nháp nào để sửa"
                subTitle="Vui lòng quay lại danh sách và chọn các nháp cần sửa hàng loạt."
                extra={<Button onClick={back}>Quay lại</Button>}
            />
        );
    }

    if (isError) {
        return (
            <Result status="error" title="Không tải được dữ liệu" subTitle={errorMessage(error)} extra={<Button onClick={back}>Quay lại</Button>} />
        );
    }

    return (
        <div>
            <PageHeader
                title={<Space><Button icon={<ArrowLeftOutlined />} onClick={back}>Quay lại</Button><span>Chỉnh sửa hàng loạt ({rowsMeta.length})</span></Space>}
                subtitle="Sửa nhiều bản nháp cùng 1 sàn cùng lúc — dùng nút “Áp dụng cho tất cả” để tránh nhập lại thông tin giống nhau."
            />
            {isLoading || !rows ? (
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            ) : (
                <Table<BulkEditRow>
                    rowKey="id"
                    dataSource={rows}
                    columns={columns}
                    pagination={false}
                    expandable={{
                        expandedRowRender: (r) => <Table<ListingDraftSku> rowKey="id" size="small" dataSource={r.skus} columns={skuColumns} pagination={false} />,
                        rowExpandable: (r) => r.skus.length > 0,
                    }}
                />
            )}
        </div>
    );
}
```

- [ ] **Bước 3: Verify**

```bash
cd app
npm run typecheck && npm run lint
```

Kỳ vọng: không lỗi. Chạy `composer dev`, lặp lại thao tác Task 6 bước 6 — lần này trang bảng hiện ra đúng: đủ N dòng đã chọn, ảnh/tên sản phẩm/gian hàng/trạng thái đúng, mở rộng dòng thấy bảng SKU con (mã/giá/tồn). Tải lại trang (F5) khi đang ở `/marketplace/listings/bulk-edit` → tự quay về "Chờ đẩy lên sàn" (mất `location.state`).

- [ ] **Bước 4: Commit**

```bash
git add app/resources/js/pages/marketplace/BulkListingEditPage.tsx app/resources/js/routes/appRoutes.tsx
git commit -m "feat(products): khung BulkListingEditPage — tải dữ liệu + bảng chỉ đọc + SKU con

Route /marketplace/listings/bulk-edit, nhận danh sách đã chọn qua router
state, gộp metadata hiển thị (ảnh/tên) với dữ liệu đầy đủ từ GET
/listings/bulk. Chưa sửa được gì — sườn cho Task 8-11. SPEC 2026-07-15."
```

---

## Task 8: FE — sửa tiêu đề/mô tả/ngành hàng/thương hiệu + "Áp dụng cho tất cả"

**Files:**
- Modify: `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`

**Interfaces:**
- Consumes: `CategoryPicker`, `useBrands` (đã có ở `features/products/`), `RichTextEditor` (đã có ở `components/`), `applyToAllRows`/`updateRow` (Task 7).

- [ ] **Bước 1: Thêm cột Tiêu đề (inline, đếm ký tự theo `useListingLimits`)**

Sửa dòng import antd đã có ở đầu file (Task 7: `import { App as AntApp, Button, Image, Result, Space, Spin, Table, Tag, Typography } from 'antd';`) thành:

```tsx
import { App as AntApp, Button, Image, Input, InputNumber, Modal, Popover, Result, Select, Space, Spin, Table, Tag, Tooltip, Typography } from 'antd';
```

Sửa dòng import icon (Task 7: `import { ArrowLeftOutlined } from '@ant-design/icons';`) thành:

```tsx
import { ArrowLeftOutlined, CopyOutlined } from '@ant-design/icons';
```

Sửa dòng import hooks (Task 7: `import { useListingsBulk } from '@/features/products/hooks';`) thành:

```tsx
import { useBrands, useListingLimits, useListingsBulk } from '@/features/products/hooks';
```

Thêm các import mới (dòng riêng):

```tsx
import { CategoryPicker } from '@/features/products/CategoryPicker';
import { RichTextEditor } from '@/components/RichTextEditor';
```

Trong component, sau khai báo `rows`/`setRows`, thêm:

```tsx
    const provider = rowsMeta?.[0]?.provider ?? '';
    const { data: limits } = useListingLimits(provider || null);
    const titleMax = limits?.title_max_length ?? 255;
    const richDescription = provider === 'tiktok' || provider === 'lazada';
```

Thêm cột Tiêu đề vào mảng `columns`, ngay sau cột "Sản phẩm":

```tsx
        {
            title: 'Tiêu đề',
            key: 'title',
            width: 260,
            render: (_, r) => (
                <Input
                    value={r.name}
                    maxLength={titleMax}
                    showCount
                    status={r.name.length > titleMax ? 'error' : undefined}
                    onChange={(e) => updateRow(r.id, { name: e.target.value })}
                />
            ),
        },
```

- [ ] **Bước 2: Thêm cột Mô tả (Modal, RichTextEditor cho TikTok/Lazada, textarea cho Shopee)**

Thêm state:

```tsx
    const [descRowId, setDescRowId] = useState<number | null>(null);
    const descRow = rows?.find((r) => r.id === descRowId) ?? null;
```

Thêm cột (sau cột Tiêu đề):

```tsx
        {
            title: 'Mô tả',
            key: 'description',
            width: 120,
            render: (_, r) => <Button size="small" onClick={() => setDescRowId(r.id)}>Sửa mô tả</Button>,
        },
```

Thêm Modal sau `</Table>` (trong nhánh `rows &&`):

```tsx
                <Modal
                    title="Sửa mô tả"
                    open={descRowId !== null}
                    onCancel={() => setDescRowId(null)}
                    onOk={() => setDescRowId(null)}
                    width={720}
                >
                    {descRow && (richDescription ? (
                        <RichTextEditor value={descRow.description} onChange={(html) => updateRow(descRow.id, { description: html })} />
                    ) : (
                        <Input.TextArea rows={6} value={descRow.description} onChange={(e) => updateRow(descRow.id, { description: e.target.value })} />
                    ))}
                </Modal>
```

- [ ] **Bước 3: Thêm cột Ngành hàng (Popover chứa `CategoryPicker`) + nút "Áp dụng cho tất cả"**

Thêm cột (sau cột Mô tả):

```tsx
        {
            title: 'Ngành hàng',
            key: 'category',
            width: 220,
            render: (_, r) => (
                <Space>
                    <Popover
                        trigger="click"
                        placement="bottomLeft"
                        content={<div style={{ width: 320 }}><CategoryPicker provider={r.provider} channelAccountId={r.channelAccountId} value={r.categoryId} onChange={(cid) => updateRow(r.id, { categoryId: cid, brandId: null })} /></div>}
                    >
                        <Button size="small" danger={!r.categoryId}>{r.categoryId ? 'Đã chọn' : 'Chưa chọn'}</Button>
                    </Popover>
                    <Tooltip title="Áp dụng ngành hàng này cho mọi dòng đang chọn">
                        <Button size="small" icon={<CopyOutlined />} disabled={!r.categoryId} onClick={() => applyToAllRows({ categoryId: r.categoryId })} />
                    </Tooltip>
                </Space>
            ),
        },
```

- [ ] **Bước 4: Thêm cột Thương hiệu (Select + `useBrands`) + nút "Áp dụng cho tất cả"**

Cột Thương hiệu cần `useBrands` theo TỪNG dòng (category khác nhau) — tách thành component con `BrandCell` trong CÙNG FILE (theo đúng pattern `TikTokShipping`/`ShopeeShipping` ở `ListingDraftEditorPage.tsx`). Thêm trước `export function BulkListingEditPage()`:

```tsx
function BrandCell({ row, onChange, onApplyAll }: { row: BulkEditRow; onChange: (brandId: string | null) => void; onApplyAll: (brandId: string | null) => void }) {
    const { data: brands, isFetching } = useBrands(row.provider, row.channelAccountId, row.categoryId);
    const options = (brands ?? []).map((b) => ({ value: b.id, label: b.mandatory ? `${b.name} (bắt buộc)` : b.name }));
    return (
        <Space>
            <Select
                style={{ width: 180 }}
                size="small"
                disabled={!row.categoryId}
                loading={isFetching}
                value={row.brandId ?? undefined}
                onChange={onChange}
                allowClear
                showSearch
                filterOption={(input, opt) => (opt?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                options={options}
                status={!row.brandId ? 'error' : undefined}
            />
            <Tooltip title="Áp dụng thương hiệu này cho mọi dòng đang chọn">
                <Button size="small" icon={<CopyOutlined />} disabled={!row.brandId} onClick={() => onApplyAll(row.brandId)} />
            </Tooltip>
        </Space>
    );
}
```

Thêm cột (sau cột Ngành hàng):

```tsx
        {
            title: 'Thương hiệu',
            key: 'brand',
            width: 260,
            render: (_, r) => (
                <BrandCell
                    row={r}
                    onChange={(bid) => updateRow(r.id, { brandId: bid })}
                    onApplyAll={(bid) => applyToAllRows({ brandId: bid })}
                />
            ),
        },
```

- [ ] **Bước 5: Verify**

```bash
cd app
npm run typecheck && npm run lint
```

Sau đó thao tác tay: mở trang bảng hàng loạt (≥2 dòng cùng provider), sửa tiêu đề (đếm ký tự đúng giới hạn provider), sửa mô tả qua Modal (đúng loại editor theo provider), chọn ngành hàng ở 1 dòng rồi bấm nút sao chép (icon) → ngành hàng lan sang mọi dòng khác + thương hiệu ở các dòng đó tự load lại theo ngành hàng mới; chọn thương hiệu rồi "áp dụng cho tất cả" tương tự.

- [ ] **Bước 6: Commit**

```bash
git add app/resources/js/pages/marketplace/BulkListingEditPage.tsx
git commit -m "feat(products): sửa tiêu đề/mô tả/ngành hàng/thương hiệu trong bảng hàng loạt

Tiêu đề đếm ký tự theo giới hạn provider (Task 2); mô tả qua Modal
RichTextEditor (TikTok/Lazada) hoặc textarea (Shopee) khớp trang soạn
đơn lẻ; ngành hàng/thương hiệu có nút 'Áp dụng cho tất cả'. SPEC 2026-07-15."
```

---

## Task 9: FE — thuộc tính bắt buộc + khối lượng/kích thước/vận chuyển + "Áp dụng cho tất cả"

**Files:**
- Modify: `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`

**Interfaces:**
- Consumes: `AttributeForm`, `useShippingOptions`, `ShippingOptions` type (đã có).

- [ ] **Bước 1: Thêm cột Thuộc tính bắt buộc (Drawer chứa `AttributeForm`)**

Thêm `Drawer` vào dòng import antd (đã sửa ở Task 8) và thêm import `AttributeForm`:

```tsx
import { App as AntApp, Button, Drawer, Image, Input, InputNumber, Modal, Popover, Result, Select, Space, Spin, Table, Tag, Tooltip, Typography } from 'antd';
import { AttributeForm } from '@/features/products/AttributeForm';
```

Thêm state:

```tsx
    const [attrRowId, setAttrRowId] = useState<number | null>(null);
    const attrRow = rows?.find((r) => r.id === attrRowId) ?? null;
    const [missingByRow, setMissingByRow] = useState<Record<number, string[]>>({});
```

Thêm cột (sau cột Thương hiệu):

```tsx
        {
            title: 'Thuộc tính bắt buộc',
            key: 'attributes',
            width: 200,
            render: (_, r) => {
                const missing = missingByRow[r.id]?.length ?? 0;
                return (
                    <Space>
                        <Button size="small" danger={missing > 0} onClick={() => setAttrRowId(r.id)}>
                            {missing > 0 ? `Thiếu ${missing}` : 'Đã đủ'}
                        </Button>
                        <Tooltip title="Áp dụng bộ thuộc tính này cho mọi dòng đang chọn">
                            <Button size="small" icon={<CopyOutlined />} onClick={() => applyToAllRows({ attributes: r.attributes })} />
                        </Tooltip>
                    </Space>
                );
            },
        },
```

Thêm Drawer sau Modal mô tả (trong nhánh `rows &&`):

```tsx
                <Drawer title="Thuộc tính bắt buộc" open={attrRowId !== null} onClose={() => setAttrRowId(null)} width={520}>
                    {attrRow && (
                        <AttributeForm
                            provider={attrRow.provider}
                            channelAccountId={attrRow.channelAccountId}
                            categoryId={attrRow.categoryId}
                            value={attrRow.attributes}
                            onChange={(attrs) => updateRow(attrRow.id, { attributes: attrs })}
                            onMissingRequiredChange={(missing) => setMissingByRow((prev) => ({ ...prev, [attrRow.id]: missing }))}
                        />
                    )}
                </Drawer>
```

- [ ] **Bước 2: Thêm cột Khối lượng/Kích thước + Vận chuyển (theo provider) + "Áp dụng cho tất cả"**

TikTok/Shopee: khối lượng/kích thước nằm ở cấp LISTING (`logistics.package_weight`/`weight`, `logistics.package_dims`). Lazada: nằm ở CẤP SKU (mỗi SKU tự có `package_weight`/`package_dims`) — áp dụng hàng loạt phải ghi đè xuống MỌI SKU của MỌI dòng.

Thêm hàm áp dụng khối lượng/kích thước xuống mọi SKU (cạnh `applyToAllRows`):

```tsx
    const applyWeightDimsToAllSkus = (weight: number | null, dims: { length?: number; width?: number; height?: number }) => {
        setRows((prev) => prev ? prev.map((r) => ({ ...r, skus: r.skus.map((s) => ({ ...s, package_weight: weight, package_dims: dims })) })) : prev);
    };
```

Thêm cột (sau cột Thuộc tính bắt buộc):

```tsx
        {
            title: 'Khối lượng/Kích thước',
            key: 'weight',
            width: 260,
            render: (_, r) => {
                if (r.provider === 'lazada') {
                    const first = r.skus[0];
                    const w = first?.package_weight ?? null;
                    const dims = first?.package_dims ?? {};
                    return (
                        <Space size={4}>
                            <InputNumber size="small" style={{ width: 70 }} min={0} step={0.1} placeholder="KL(kg)" value={w ?? undefined}
                                onChange={(v) => updateRow(r.id, { skus: r.skus.map((s) => ({ ...s, package_weight: v == null ? null : Number(v) })) })} />
                            <InputNumber size="small" style={{ width: 50 }} min={0} placeholder="D" value={dims.length}
                                onChange={(v) => updateRow(r.id, { skus: r.skus.map((s) => ({ ...s, package_dims: { ...s.package_dims, length: v == null ? undefined : Number(v) } })) })} />
                            <InputNumber size="small" style={{ width: 50 }} min={0} placeholder="R" value={dims.width}
                                onChange={(v) => updateRow(r.id, { skus: r.skus.map((s) => ({ ...s, package_dims: { ...s.package_dims, width: v == null ? undefined : Number(v) } })) })} />
                            <InputNumber size="small" style={{ width: 50 }} min={0} placeholder="C" value={dims.height}
                                onChange={(v) => updateRow(r.id, { skus: r.skus.map((s) => ({ ...s, package_dims: { ...s.package_dims, height: v == null ? undefined : Number(v) } })) })} />
                            <Tooltip title="Áp dụng khối lượng/kích thước này cho mọi SKU của mọi dòng đang chọn">
                                <Button size="small" icon={<CopyOutlined />} onClick={() => applyWeightDimsToAllSkus(w, dims)} />
                            </Tooltip>
                        </Space>
                    );
                }
                const weightKey = r.provider === 'tiktok' ? 'package_weight' : 'weight';
                const w = (r.logistics[weightKey] as number | undefined) ?? undefined;
                return (
                    <Space size={4}>
                        <InputNumber size="small" style={{ width: 90 }} min={0} step={0.1} placeholder="Khối lượng" value={w}
                            onChange={(v) => updateRow(r.id, { logistics: { ...r.logistics, [weightKey]: v == null ? undefined : Number(v) } })} />
                        <Tooltip title="Áp dụng khối lượng này cho mọi dòng đang chọn">
                            <Button size="small" icon={<CopyOutlined />} onClick={() => applyToAllRows({ logistics: { ...r.logistics, [weightKey]: w } })} />
                        </Tooltip>
                    </Space>
                );
            },
        },
```

- [ ] **Bước 3: Thêm cột Vận chuyển (thu gọn `ShippingSection`)**

`ShippingSection`/`TikTokShipping`/`ShopeeShipping`/`LazadaShipping` hiện là hàm nội bộ (không export) trong `ListingDraftEditorPage.tsx`. Export chúng để tái dùng — sửa `app/resources/js/pages/marketplace/ListingDraftEditorPage.tsx`, đổi khai báo (dòng 500-501 và 520, 557, 588):

```tsx
function ShippingSection({
```

thành:

```tsx
export function ShippingSection({
```

(giữ nguyên phần còn lại của hàm). KHÔNG cần export `ShopeeShipping`/`TikTokShipping`/`LazadaShipping` riêng — `ShippingSection` tự route theo `opts.mode`.

Trong `BulkListingEditPage.tsx`, thêm import:

```tsx
import { ShippingSection } from './ListingDraftEditorPage';
```

Thêm cột (sau cột Khối lượng/Kích thước):

```tsx
        {
            title: 'Vận chuyển',
            key: 'shipping',
            width: 200,
            render: (_, r) => (
                <Popover
                    trigger="click"
                    placement="bottomLeft"
                    content={
                        <div style={{ width: 340 }}>
                            <ShippingSection
                                provider={r.provider}
                                channelAccountId={r.channelAccountId}
                                value={r.logistics}
                                onChange={(v) => updateRow(r.id, { logistics: v })}
                                onApplyWarehouse={(wid) => updateRow(r.id, { skus: r.skus.map((s) => ({ ...s, warehouse_id: wid })) })}
                            />
                        </div>
                    }
                >
                    <Space>
                        <Button size="small">Cấu hình</Button>
                        <Tooltip title="Áp dụng cấu hình vận chuyển này cho mọi dòng đang chọn">
                            <Button size="small" icon={<CopyOutlined />} onClick={() => applyToAllRows({ logistics: r.logistics })} />
                        </Tooltip>
                    </Space>
                </Popover>
            ),
        },
```

- [ ] **Bước 4: Verify**

```bash
cd app
npm run typecheck && npm run lint && npm run build
```

Kỳ vọng: build thành công (xác nhận export `ShippingSection` không phá `ListingDraftEditorPage.tsx` — trang soạn đơn lẻ vẫn dùng `<ShippingSection .../>` y hệt trước, chỉ đổi visibility). Thao tác tay: mở trang bảng hàng loạt TikTok, sửa kho vận chuyển ở 1 dòng qua Popover → bấm sao chép → kho lan sang mọi dòng + `warehouse_id` mọi SKU con của mọi dòng cũng đổi theo (mở dòng con kiểm tra). Với Lazada, sửa khối lượng/kích thước ở dòng 1 → áp dụng cho tất cả → mọi SKU của mọi dòng khác đổi theo.

- [ ] **Bước 5: Commit**

```bash
git add app/resources/js/pages/marketplace/BulkListingEditPage.tsx app/resources/js/pages/marketplace/ListingDraftEditorPage.tsx
git commit -m "feat(products): thuộc tính bắt buộc + khối lượng/kích thước/vận chuyển hàng loạt

Export ShippingSection để tái dùng nguyên vẹn (TikTok kho+giao hàng,
Shopee kênh vận chuyển, Lazada ghi chú) — không đổi hành vi trang soạn
đơn lẻ. Lazada khối lượng/kích thước ở cấp SKU nên 'áp dụng cho tất cả'
cascade xuống mọi SKU của mọi dòng, khác TikTok/Shopee (cấp listing).
SPEC 2026-07-15."
```

---

## Task 10: FE — hiển thị lỗi validate theo từng ô/từng SKU

**Files:**
- Modify: `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`

**Interfaces:**
- Consumes: `BulkEditRow.validationErrors: Record<string,string>` (Task 7), field keys từ 3 validator: `title`, `categoryId`, `brandId`, `logistics.package_weight`/`logistics.weight`, `skus.{i}.warehouse_id`, `skus.{i}.package_weight`, `skus.{i}.package_dims`, v.v. (xem Task 2 + validator hiện có).

- [ ] **Bước 1: Thêm cột "Trạng thái" đổi thành badge lỗi + đếm**

Sửa cột "Trạng thái" hiện có (Task 7) thành hiện SỐ LỖI khi `status !== 'ready'`:

```tsx
        {
            title: 'Trạng thái',
            key: 'status',
            width: 140,
            render: (_, r) => {
                const errCount = Object.keys(r.validationErrors).length;
                const meta = STATUS_TAG[r.status] ?? STATUS_TAG.draft;
                return (
                    <Space direction="vertical" size={2}>
                        <Tag color={meta.color}>{meta.label}</Tag>
                        {errCount > 0 && <Tag color="red">Thiếu {errCount} trường</Tag>}
                    </Space>
                );
            },
        },
```

- [ ] **Bước 2: Hiện message lỗi ngay dưới từng ô liên quan**

Thêm hàm helper trước `export function BulkListingEditPage()`:

```tsx
function fieldError(errors: Record<string, string>, key: string): string | null {
    return errors[key] ?? null;
}

function ErrorText({ msg }: { msg: string | null }) {
    if (!msg) return null;
    return <div style={{ color: '#ff4d4f', fontSize: 12, marginTop: 2 }}>{msg}</div>;
}
```

Bọc lại nội dung 4 cột đã có lỗi backend tương ứng — sửa render của cột Tiêu đề (thêm `title` error):

```tsx
            render: (_, r) => (
                <div>
                    <Input
                        value={r.name}
                        maxLength={titleMax}
                        showCount
                        status={r.name.length > titleMax || fieldError(r.validationErrors, 'title') ? 'error' : undefined}
                        onChange={(e) => updateRow(r.id, { name: e.target.value })}
                    />
                    <ErrorText msg={fieldError(r.validationErrors, 'title')} />
                </div>
            ),
```

Cột Ngành hàng (định nghĩa ở Task 8) — thay toàn bộ `render` bằng:

```tsx
            render: (_, r) => (
                <div>
                    <Space>
                        <Popover
                            trigger="click"
                            placement="bottomLeft"
                            content={<div style={{ width: 320 }}><CategoryPicker provider={r.provider} channelAccountId={r.channelAccountId} value={r.categoryId} onChange={(cid) => updateRow(r.id, { categoryId: cid, brandId: null })} /></div>}
                        >
                            <Button size="small" danger={!r.categoryId}>{r.categoryId ? 'Đã chọn' : 'Chưa chọn'}</Button>
                        </Popover>
                        <Tooltip title="Áp dụng ngành hàng này cho mọi dòng đang chọn">
                            <Button size="small" icon={<CopyOutlined />} disabled={!r.categoryId} onClick={() => applyToAllRows({ categoryId: r.categoryId })} />
                        </Tooltip>
                    </Space>
                    <ErrorText msg={fieldError(r.validationErrors, 'categoryId')} />
                </div>
            ),
```

Cột Thương hiệu (định nghĩa ở Task 8) — thay toàn bộ hàm `BrandCell` bằng (thêm prop `errorMsg`):

```tsx
function BrandCell({ row, onChange, onApplyAll, errorMsg }: { row: BulkEditRow; onChange: (brandId: string | null) => void; onApplyAll: (brandId: string | null) => void; errorMsg: string | null }) {
    const { data: brands, isFetching } = useBrands(row.provider, row.channelAccountId, row.categoryId);
    const options = (brands ?? []).map((b) => ({ value: b.id, label: b.mandatory ? `${b.name} (bắt buộc)` : b.name }));
    return (
        <div>
            <Space>
                <Select
                    style={{ width: 180 }}
                    size="small"
                    disabled={!row.categoryId}
                    loading={isFetching}
                    value={row.brandId ?? undefined}
                    onChange={onChange}
                    allowClear
                    showSearch
                    filterOption={(input, opt) => (opt?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                    options={options}
                    status={!row.brandId ? 'error' : undefined}
                />
                <Tooltip title="Áp dụng thương hiệu này cho mọi dòng đang chọn">
                    <Button size="small" icon={<CopyOutlined />} disabled={!row.brandId} onClick={() => onApplyAll(row.brandId)} />
                </Tooltip>
            </Space>
            <ErrorText msg={errorMsg} />
        </div>
    );
}
```

Cột Thương hiệu (định nghĩa ở Task 8) — sửa `render` để truyền thêm `errorMsg`:

```tsx
            render: (_, r) => (
                <BrandCell
                    row={r}
                    onChange={(bid) => updateRow(r.id, { brandId: bid })}
                    onApplyAll={(bid) => applyToAllRows({ brandId: bid })}
                    errorMsg={fieldError(r.validationErrors, 'brandId')}
                />
            ),
```

Cột Khối lượng/Kích thước (định nghĩa ở Task 9) — nhánh TikTok/Shopee (nhánh Lazada giữ nguyên, không có lỗi cấp-listing vì lỗi nằm ở SKU con — xem bước 3), thêm `<ErrorText .../>` sau khối `<Space size={4}>...</Space>` của nhánh không-Lazada:

```tsx
                const weightKey = r.provider === 'tiktok' ? 'package_weight' : 'weight';
                const w = (r.logistics[weightKey] as number | undefined) ?? undefined;
                const weightErrKey = r.provider === 'tiktok' ? 'logistics.package_weight' : 'logistics.weight';
                return (
                    <div>
                        <Space size={4}>
                            <InputNumber size="small" style={{ width: 90 }} min={0} step={0.1} placeholder="Khối lượng" value={w}
                                onChange={(v) => updateRow(r.id, { logistics: { ...r.logistics, [weightKey]: v == null ? undefined : Number(v) } })} />
                            <Tooltip title="Áp dụng khối lượng này cho mọi dòng đang chọn">
                                <Button size="small" icon={<CopyOutlined />} onClick={() => applyToAllRows({ logistics: { ...r.logistics, [weightKey]: w } })} />
                            </Tooltip>
                        </Space>
                        <ErrorText msg={fieldError(r.validationErrors, weightErrKey)} />
                    </div>
                );
```

- [ ] **Bước 3: Hiện lỗi trong bảng SKU con**

Sửa `skuColumns` (Task 7) — cần biết `validationErrors` của dòng CHA để map `skus.{i}.field` → đúng SKU theo INDEX. Đổi `expandedRowRender` để truyền index:

```tsx
                    expandable={{
                        expandedRowRender: (r) => {
                            const skuCols: ColumnsType<ListingDraftSku> = [
                                { title: 'SKU người bán', dataIndex: 'seller_sku', width: 160 },
                                { title: 'Giá (VND)', dataIndex: 'price', width: 120 },
                                { title: 'Tồn đẩy sàn', dataIndex: 'stock', width: 100 },
                                {
                                    title: 'Lỗi', key: 'error', render: (_, __, idx) => {
                                        const msgs = Object.entries(r.validationErrors)
                                            .filter(([k]) => k.startsWith(`skus.${idx}.`))
                                            .map(([, v]) => v);
                                        return msgs.length > 0 ? <ErrorText msg={msgs.join('; ')} /> : null;
                                    },
                                },
                            ];
                            return <Table<ListingDraftSku> rowKey="id" size="small" dataSource={r.skus} columns={skuCols} pagination={false} />;
                        },
                        rowExpandable: (r) => r.skus.length > 0,
                    }}
```

Xóa khai báo `skuColumns` cũ ở Task 7 (không còn dùng ngoài `expandedRowRender`).

- [ ] **Bước 4: Verify**

```bash
cd app
npm run typecheck && npm run lint
```

Thao tác tay: mở bảng hàng loạt với ít nhất 1 dòng thiếu `category_id`/`warehouse_id` (tenant Enko Store trên dev/staging là ví dụ thật, hoặc tạo nháp mới chưa cấu hình) — SAU KHI bấm "Lưu" (Task 11, tạm thời có thể test bằng cách gọi `updateRow` thủ công qua devtools nếu Task 11 chưa xong) badge "Thiếu N trường" hiện đúng số, message lỗi hiện đúng dưới đúng ô, lỗi SKU hiện đúng dòng con.

- [ ] **Bước 5: Commit**

```bash
git add app/resources/js/pages/marketplace/BulkListingEditPage.tsx
git commit -m "feat(products): hiển thị lỗi validate theo từng ô/từng SKU trong bảng hàng loạt

Map validation_errors (field->message) vào đúng ô cấp listing (title/
categoryId/brandId/logistics.*) và đúng dòng SKU con (skus.{i}.*) theo
index. Badge tổng 'Thiếu N trường' ở cột Trạng thái. SPEC 2026-07-15."
```

---

## Task 11: FE — thanh hành động Lưu / Lưu & đẩy + tổng kết + quay về

**Files:**
- Modify: `app/resources/js/pages/marketplace/BulkListingEditPage.tsx`

**Interfaces:**
- Consumes: `useBulkUpdateListings` (Task 5), `useBulkPush`/`usePushBatch` (đã có, `features/products/hooks.ts`), `PushProgressModal` (đã có).

- [ ] **Bước 1: Thêm mutation + state cho push**

Sửa dòng import hooks (đã có từ Task 7+8, hiện là `import { useBrands, useListingLimits, useListingsBulk } from '@/features/products/hooks';`) thành:

```tsx
import { useBrands, useBulkPush, useBulkUpdateListings, useListingLimits, useListingsBulk, usePushBatch } from '@/features/products/hooks';
```

Sửa dòng import icon (đã có từ Task 7+8, hiện là `import { ArrowLeftOutlined, CopyOutlined } from '@ant-design/icons';`) thành:

```tsx
import { ArrowLeftOutlined, CloudUploadOutlined, CopyOutlined, SaveOutlined } from '@ant-design/icons';
```

Thêm import mới:

```tsx
import { PushProgressModal } from '@/features/products/PushProgressModal';
```

Trong component, thêm:

```tsx
    const bulkUpdate = useBulkUpdateListings();
    const bulkPush = useBulkPush();
    const [pushBatchId, setPushBatchId] = useState<number | null>(null);
    const [pushModalOpen, setPushModalOpen] = useState(false);
    const { data: pushBatch } = usePushBatch(pushBatchId);
    const [saving, setSaving] = useState(false);
```

- [ ] **Bước 2: Hàm build payload + Lưu**

Thêm hàm:

```tsx
    const buildBulkPayload = () =>
        (rows ?? []).map((r) => ({
            id: r.id,
            name: r.name,
            description: r.description,
            category_id: r.categoryId,
            brand_id: r.brandId,
            attributes: r.attributes,
            media_refs: r.mediaRefs,
            logistics: r.logistics,
            skus: r.skus.map((s) => ({
                id: s.id, seller_sku: s.seller_sku, sale_props: s.sale_props, price: s.price, stock: s.stock,
                package_weight: s.package_weight, package_dims: s.package_dims, warehouse_id: s.warehouse_id,
                master_variant_id: s.master_variant_id ?? null, image_ref: s.image_ref ?? null,
            })),
        }));

    /** Lưu toàn bộ, cập nhật lại status/lỗi từng dòng từ response. Trả về danh sách id đã 'ready'. */
    const saveAll = async (): Promise<number[]> => {
        setSaving(true);
        try {
            const results = await bulkUpdate.mutateAsync(buildBulkPayload());
            setRows((prev) =>
                prev
                    ? prev.map((r) => {
                          const res = results.find((x) => x.id === r.id);
                          if (!res) return r;
                          return { ...r, status: res.status, validationErrors: res.validation_errors ?? {} };
                      })
                    : prev,
            );
            return results.filter((r) => r.status === 'ready').map((r) => r.id);
        } finally {
            setSaving(false);
        }
    };

    const handleSave = async () => {
        try {
            const readyIds = await saveAll();
            const total = rows?.length ?? 0;
            message.success(`Đã lưu ${total} nháp — ${readyIds.length} sẵn sàng đẩy, ${total - readyIds.length} còn thiếu thông tin.`);
            navigate('/marketplace/to-push');
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    const handleSaveAndPush = async () => {
        try {
            const readyIds = await saveAll();
            if (readyIds.length === 0) {
                message.warning('Không có nháp nào sẵn sàng đẩy — vui lòng sửa các lỗi còn lại.');
                return;
            }
            bulkPush.mutate(readyIds, {
                onSuccess: ({ batch_id }) => {
                    setPushBatchId(batch_id);
                    setPushModalOpen(true);
                },
                onError: (e) => message.error(errorMessage(e)),
            });
        } catch (e) {
            message.error(errorMessage(e));
        }
    };
```

- [ ] **Bước 3: Thanh hành động + `PushProgressModal`**

Thêm vào `PageHeader` (Task 7) prop `extra`:

```tsx
                extra={
                    <Space>
                        <Button icon={<SaveOutlined />} loading={saving && !bulkPush.isPending} onClick={handleSave}>Lưu</Button>
                        <Button type="primary" icon={<CloudUploadOutlined />} loading={saving || bulkPush.isPending} onClick={handleSaveAndPush}>Lưu & đẩy</Button>
                    </Space>
                }
```

Thêm `PushProgressModal` cuối JSX (ngang hàng với Drawer/Modal khác):

```tsx
                <PushProgressModal
                    batch={pushBatch}
                    open={pushModalOpen}
                    onHide={() => setPushModalOpen(false)}
                    onClose={() => {
                        setPushModalOpen(false);
                        const succeeded = pushBatch?.succeeded ?? 0;
                        const failed = pushBatch?.failed ?? 0;
                        message.success(`Đẩy xong: ${succeeded} thành công${failed ? `, ${failed} lỗi` : ''}.`);
                        navigate('/marketplace/to-push');
                    }}
                />
```

- [ ] **Bước 4: Verify toàn luồng**

```bash
cd app
npm run typecheck && npm run lint && npm run build
```

Sau đó `composer dev`, thao tác tay đầy đủ:
1. Chọn ≥2 nháp TikTok Nháp/Lỗi (thiếu category/brand/weight/warehouse — case Enko Store) → "Chỉnh sửa hàng loạt".
2. Sửa tiêu đề dòng 1 (kiểm tra đếm ký tự đúng 25-255). Sửa mô tả qua Modal. Chọn ngành hàng dòng 1 → "Áp dụng cho tất cả" → mọi dòng cùng ngành hàng, thương hiệu reload theo. Chọn thương hiệu dòng 1 → áp dụng tất cả. Mở Vận chuyển dòng 1, xác nhận kho mặc định TỰ CHỌN SẴN (fix `95c58bac`) → áp dụng tất cả → mọi SKU mọi dòng có `warehouse_id`. Nhập khối lượng dòng 1 → áp dụng tất cả.
3. Bấm "Lưu" — xác nhận mọi dòng chuyển "Sẵn sàng" (nếu đã đủ field) hoặc còn "Thiếu N trường" đúng thực tế, quay về "Chờ đẩy lên sàn", toast đúng số.
4. Lặp lại, lần này bấm "Lưu & đẩy" — `PushProgressModal` hiện, theo dõi tới khi xong, đóng → quay về danh sách, các listing đã đẩy chuyển tab "Lịch sử đã đẩy".
5. Case: chọn 1 dòng cố tình để thiếu `brand_id` — bấm "Lưu & đẩy" → dòng đó KHÔNG bị đẩy (không nằm trong batch), dòng khác vẫn đẩy bình thường, dòng thiếu vẫn còn trong "Chờ đẩy" với lỗi hiện rõ.

- [ ] **Bước 5: Commit**

```bash
git add app/resources/js/pages/marketplace/BulkListingEditPage.tsx
git commit -m "feat(products): thanh hành động Lưu / Lưu & đẩy cho bảng chỉnh sửa hàng loạt

Lưu toàn bộ qua PUT /listings/bulk (mỗi dòng xử lý độc lập), cập nhật lại
status/lỗi từng dòng từ response. Lưu & đẩy: sau lưu, tự lọc dòng 'ready'
gọi bulk-push có sẵn, theo dõi qua PushProgressModal có sẵn. Cả 2 quay về
'Chờ đẩy lên sàn' kèm toast tổng kết X đã lưu/Y sẵn sàng. SPEC 2026-07-15."
```

---

## Task 12: Rà soát toàn bộ + cập nhật spec trạng thái Implemented

**Files:**
- Modify: `docs/specs/2026-07-15-bulk-listing-draft-edit-design.md` (dòng 3, `Trạng thái`)

**Interfaces:** —

- [ ] **Bước 1: Chạy toàn bộ quality gate**

```bash
cd app
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --filter=Products
npm run lint && npm run typecheck && npm run build
```

Kỳ vọng: tất cả pass (Pint/PHPStan toàn repo — nếu có lỗi PRE-EXISTING không liên quan tới thay đổi của plan này thì bỏ qua, chỉ đảm bảo không có lỗi MỚI do plan này gây ra).

- [ ] **Bước 2: Rà lại 10 tiêu chí hoàn thành trong spec**

Mở `docs/specs/2026-07-15-bulk-listing-draft-edit-design.md` mục 10, tick từng dòng đã làm xong (Task 1-11 ở trên phủ đủ cả 6 tiêu chí).

- [ ] **Bước 3: Đổi trạng thái spec**

Trong `docs/specs/2026-07-15-bulk-listing-draft-edit-design.md`, dòng 3, đổi:

```
- **Trạng thái:** Design
```

thành:

```
- **Trạng thái:** Implemented
```

- [ ] **Bước 4: Commit + push**

```bash
git add docs/specs/2026-07-15-bulk-listing-draft-edit-design.md
git commit -m "docs(products): đánh dấu spec chỉnh sửa hàng loạt bản nháp đã Implemented"
git push origin main
```

---

## Task 13 (phát sinh khi review toàn nhánh): Sửa `ListingDraftService::update()` ghi đè ngược tiêu đề/mô tả khi client gửi kèm `attributes` cũ

**Bối cảnh:** Review toàn nhánh (sau Task 11) phát hiện — và đã tự kiểm chứng bằng test thật chạy trên code thật — một bug tiền tồn tại trong `ListingDraftService::update()` (method này KHÔNG bị Task 1-11 chạm vào, nhưng cả trang soạn đơn lẻ `ListingDraftEditorPage.tsx` lẫn `BulkListingEditPage.tsx` mới xây đều gọi nó theo cùng 1 kiểu payload nên đều dính bug): method xử lý `name`/`description`/`video_url` (ghi vào `attributes['name']` v.v.) TRƯỚC, rồi mới xử lý khóa `attributes` do client gửi (`array_replace($draft->attributes, $data['attributes'])`) SAU — mà `array_replace` lấy tham số THỨ HAI làm giá trị thắng. FE luôn gửi `attributes` là bản đã nạp lúc mở trang (có `name`/`description` CŨ, vì raw column `attributes` vốn đã chứa các khóa này từ lần lưu trước) kèm theo `name`/`description` MỚI ở cấp ngoài. Kết quả: sau khi lưu, `attributes['name']`/`['description']` bị đè NGƯỢC về giá trị cũ — tiêu đề/mô tả sửa xong lưu lại mất, cả ở trang đơn lẻ lẫn bảng hàng loạt.

**Files:**
- Modify: `app/app/Modules/Products/Services/ListingDraftService.php` (method `update()`, dòng ~126-165 tại thời điểm viết brief này — đổi THỨ TỰ xử lý, không đổi logic từng khối)
- Test: `app/tests/Feature/Products/ListingDraftServiceTest.php` (thêm 1 test mới)

**Interfaces:** Không đổi chữ ký `update(int $listingId, array $data): ListingDraft` — chỉ đổi thứ tự các khối lệnh bên trong transaction closure.

- [ ] **Bước 1: Viết test thất bại**

Thêm vào `app/tests/Feature/Products/ListingDraftServiceTest.php`, cạnh `test_listing_title_override_is_saved_and_used_for_publish_title` (giữ nguyên style setUp có sẵn của file — dùng `$this->product`/`$this->accountId` đã có trong `setUp()`):

```php
    public function test_title_edit_survives_stale_attributes_payload_sent_alongside(): void
    {
        $svc = app(ListingDraftService::class);
        $draft = $svc->createDraft((int) $this->product->getKey(), $this->accountId, 'lazada');

        // Lưu lần 1: đặt tiêu đề — attributes column giờ đã có khóa 'name'.
        $draft = $svc->update((int) $draft->getKey(), ['name' => 'Tiêu đề đầu']);

        // Mô phỏng FE: đã nạp draft (attributes bao gồm 'name' CŨ) TRƯỚC khi người dùng sửa
        // tiếp tiêu đề, rồi Lưu — payload gửi cả 'name' MỚI lẫn 'attributes' (bản CŨ, nạp
        // trước đó, buildPayload()/buildBulkPayload() luôn gửi kèm attributes như vậy).
        $staleAttributes = $draft->attributes;

        $draft2 = $svc->update((int) $draft->getKey(), [
            'name' => 'Tiêu đề mới',
            'attributes' => $staleAttributes,
        ]);

        $this->assertSame('Tiêu đề mới', $draft2->attributes['name'] ?? null);
    }
```

- [ ] **Bước 2: Chạy test, xác nhận FAIL**

```bash
cd app
php artisan test --filter=test_title_edit_survives_stale_attributes_payload_sent_alongside
```

Kỳ vọng: FAIL — `attributes['name']` là `'Tiêu đề đầu'` thay vì `'Tiêu đề mới'`.

- [ ] **Bước 3: Đổi thứ tự trong `update()`**

Trong `app/app/Modules/Products/Services/ListingDraftService.php`, tìm khối hiện tại (bên trong `DB::transaction(function () use ($draft, $data) { ... })` của method `update()`):

```php
            // Tiêu đề riêng cho listing (override tên SP gốc) — lưu trong attributes['name'].
            // Rỗng ⇒ null để resource fallback về product->name.
            if (array_key_exists('name', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['name'] = (trim((string) ($data['name'] ?? '')) !== '') ? trim((string) $data['name']) : null;
                $draft->attributes = $attrs;
            }

            if (array_key_exists('description', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['description'] = $data['description'];
                $draft->attributes = $attrs;
            }

            if (array_key_exists('video_url', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['video_url'] = ($data['video_url'] ?? '') !== '' ? $data['video_url'] : null;
                $draft->attributes = $attrs;
            }

            foreach (['category_id', 'brand_id', 'attributes', 'media_refs', 'logistics'] as $key) {
                if (array_key_exists($key, $data)) {
                    if ($key === 'attributes') {
                        // array_replace (KHÔNG array_merge): id thuộc tính sàn là chuỗi-số
                        // (vd "100000"); array_merge sẽ ĐÁNH SỐ LẠI khóa số → mất id thật,
                        // phình mảng mỗi lần lưu ⇒ thuộc tính "điền xong lưu lại mất".
                        $draft->attributes = array_replace($draft->attributes ?? [], $data[$key] ?? []);
                    } else {
                        $draft->{$key} = $data[$key];
                    }
                }
            }
```

Đổi thành (đảo thứ tự: merge `attributes`/`category_id`/`brand_id`/`media_refs`/`logistics` TRƯỚC, `name`/`description`/`video_url` SAU — để 3 trường này LUÔN thắng thay vì bị `attributes` cũ đè ngược; comment giải thích thêm lý do đảo thứ tự):

```php
            foreach (['category_id', 'brand_id', 'attributes', 'media_refs', 'logistics'] as $key) {
                if (array_key_exists($key, $data)) {
                    if ($key === 'attributes') {
                        // array_replace (KHÔNG array_merge): id thuộc tính sàn là chuỗi-số
                        // (vd "100000"); array_merge sẽ ĐÁNH SỐ LẠI khóa số → mất id thật,
                        // phình mảng mỗi lần lưu ⇒ thuộc tính "điền xong lưu lại mất".
                        $draft->attributes = array_replace($draft->attributes ?? [], $data[$key] ?? []);
                    } else {
                        $draft->{$key} = $data[$key];
                    }
                }
            }

            // Tiêu đề riêng cho listing (override tên SP gốc) — lưu trong attributes['name'].
            // Rỗng ⇒ null để resource fallback về product->name. XỬ LÝ SAU khối 'attributes'
            // ở trên — FE luôn gửi kèm 'attributes' là bản nạp lúc mở trang (đã có 'name'/
            // 'description' CŨ từ lần lưu trước, vì đây là raw column), nếu xử lý trước thì
            // array_replace ở trên sẽ ghi đè NGƯỢC 'name'/'description' MỚI về giá trị cũ.
            if (array_key_exists('name', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['name'] = (trim((string) ($data['name'] ?? '')) !== '') ? trim((string) $data['name']) : null;
                $draft->attributes = $attrs;
            }

            if (array_key_exists('description', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['description'] = $data['description'];
                $draft->attributes = $attrs;
            }

            if (array_key_exists('video_url', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['video_url'] = ($data['video_url'] ?? '') !== '' ? $data['video_url'] : null;
                $draft->attributes = $attrs;
            }
```

- [ ] **Bước 4: Chạy lại test, xác nhận PASS**

```bash
php artisan test --filter=test_title_edit_survives_stale_attributes_payload_sent_alongside
```

- [ ] **Bước 5: Chạy toàn bộ test Products để chắc không phá gì (đặc biệt các test liên quan `attributes`/`update()`)**

```bash
php artisan test --filter=Products
```

Kỳ vọng: tất cả PASS — đặc biệt các test đã có từ trước dùng `update()`: `test_numeric_attribute_ids_are_preserved_on_save`, `test_listing_title_override_is_saved_and_used_for_publish_title`, `test_update_keeps_draft_when_validation_fails_then_ready_when_passes`, và toàn bộ `BulkListingDraftTest.php`.

- [ ] **Bước 6: Pint + PHPStan**

```bash
vendor/bin/pint --test app/Modules/Products/Services/ListingDraftService.php
vendor/bin/phpstan analyse app/Modules/Products/Services/ListingDraftService.php
```

- [ ] **Bước 7: Commit**

```bash
git add app/app/Modules/Products/Services/ListingDraftService.php app/tests/Feature/Products/ListingDraftServiceTest.php
git commit -m "fix(products): sửa tiêu đề/mô tả bị ghi đè ngược khi client gửi kèm attributes cũ

update() xử lý name/description/video_url TRƯỚC rồi mới merge khóa
'attributes' do client gửi (array_replace, tham số 2 thắng) — mà FE luôn
gửi kèm attributes là bản nạp lúc mở trang (còn name/description CŨ, vì
đây là raw column đã có từ lần lưu trước). Kết quả: sửa tiêu đề/mô tả rồi
Lưu bị hoàn tác về giá trị cũ, ở CẢ trang soạn đơn lẻ lẫn bảng hàng loạt
(bug tiền tồn tại trong update(), Task 1-11 không chạm vào method này
nhưng đều gọi nó theo kiểu payload gây lỗi). Đảo thứ tự: merge attributes
TRƯỚC, name/description/video_url SAU — 3 trường này luôn thắng."
```
