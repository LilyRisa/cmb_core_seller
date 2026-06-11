# Marketplace Product Publishing — Implementation Plan (Lazada + TikTok Shop + Shopee)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép user quản lý sản phẩm hệ thống và **đẩy listing lên Lazada, TikTok Shop, Shopee** qua Open Platform API ở backend, với trạng thái nháp cần sửa, token extension scope hẹp, và popup tiến trình.

**Architecture:** Mở rộng module `Products` (cmb_core_seller) với `ChannelListing` (phép chiếu sản phẩm → 1 shop sàn) + state machine `draft→ready→pushing→live/failed`. Thêm interface `ProductPublishingConnector` cho 3 connector hiện có (tái dùng signer/client/token sẵn có), validate field bắt buộc theo [tài liệu nghiên cứu](./marketplace-product-listing-api-requirements.md) trước khi gọi API. Push chạy qua job hàng đợi `listings` (đã có Horizon supervisor) cập nhật bảng progress; SPA poll modal tiến trình.

**Tech Stack:** Laravel 11 (PHP 8.3), Pest/PHPUnit, Sanctum PAT abilities, Horizon/Redis, React 18 + Ant Design + TanStack Query, Chrome MV3 (vanilla JS).

**Spec nguồn:** [`2026-06-11-marketplace-product-publishing-design.md`](./2026-06-11-marketplace-product-publishing-design.md)

> **Quy ước chạy:** mọi lệnh PHP/Node chạy từ `app/`. Quality gate mỗi task: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test --filter=...`. Commit thường xuyên (conventional commits). Mỗi connector publish được gate bằng `capabilities()` + `supports()`; method chưa hỗ trợ ném `UnsupportedOperation`.

---

## File Structure (khóa quyết định phân rã)

**Integration layer — `app/app/Integrations/Channels/`**
- `Contracts/ProductPublishingConnector.php` — interface publish (CREATE).
- `Contracts/ListingValidator.php` — interface validate field bắt buộc.
- `DTO/CategoryNodeDTO.php`, `DTO/ListingAttributeDTO.php`, `DTO/BrandDTO.php`, `DTO/MediaRefDTO.php`, `DTO/ListingDraftDTO.php`, `DTO/ListingResultDTO.php`, `DTO/ListingStatusDTO.php` — DTO chuẩn (readonly).
- `Lazada/LazadaPublisher.php`, `Lazada/LazadaListingValidator.php`, `Lazada/LazadaProductPayload.php` (build XML/JSON payload).
- `TikTok/TikTokPublisher.php`, `TikTok/TikTokListingValidator.php`, `TikTok/TikTokProductPayload.php`.
- `Shopee/ShopeePublisher.php`, `Shopee/ShopeeListingValidator.php`, `Shopee/ShopeeProductPayload.php`.
- Sửa `IntegrationsServiceProvider.php` — bind publisher per provider; sửa mỗi connector `capabilities()` bật cờ `listings.*`.

**Module `Products` — `app/app/Modules/Products/`**
- `Models/ChannelListing.php`, `Models/ChannelListingSku.php`, `Models/ProductPushBatch.php`, `Models/ProductPushJob.php`.
- `Database/Migrations/*_create_channel_listings_table.php`, `*_create_channel_listing_skus_table.php`, `*_create_product_push_batches_table.php`, `*_create_product_push_jobs_table.php`.
- `Services/ListingDraftService.php` (tạo/cập nhật nháp + chạy validator), `Services/ListingTaxonomyService.php` (proxy danh mục/attr/brand có cache), `Services/ListingPushService.php` (tạo batch + dispatch job), `Services/MediaPrepService.php` (resize/upload ảnh lên CDN sàn).
- `Jobs/PushListingJob.php` (1 listing/1 job, cập nhật progress).
- `Http/Controllers/ChannelListingController.php`, `ListingPushController.php`, `ListingTaxonomyController.php`, `ExtensionTokenController.php`.
- `Http/Requests/*`, `Http/Resources/*`, `Http/routes.php` (load qua ProductsServiceProvider).
- `Policies/ChannelListingPolicy.php`.

**Auth/PAT — `app/`**
- `app/Modules/Auth/...` hoặc `Products/Http/Controllers/ExtensionTokenController.php` cấp PAT ability `copy-product:push`; route push của extension thêm middleware `abilities:copy-product:push`.

**SPA — `app/resources/js/features/products/`**
- `api.ts` (gọi `/api/v1` listings/taxonomy/push), `hooks.ts` (TanStack Query + polling), `ProductListPage.tsx`, `ListingEditorDrawer.tsx` (chọn danh mục lá, form attr bắt buộc, brand, ảnh, giá/tồn, package), `PushProgressModal.tsx`, `CategoryPicker.tsx`, `AttributeForm.tsx`.

**Extension — `D:\cmb_copy_product\`**
- `popup/progress.js` + `popup/progress.html` (popup thanh tiến trình), sửa `content/*.js` gọi progress, `background/background.js` lưu/đính PAT scope, `config/env.js` thống nhất `api/v1`.

---

## Phần A — Nền tảng (foundation)

### Task A1: Migration + model `ChannelListing` & `ChannelListingSku`

**Files:**
- Create: `app/app/Modules/Products/Database/Migrations/2026_06_11_100001_create_channel_listings_table.php`
- Create: `app/app/Modules/Products/Database/Migrations/2026_06_11_100002_create_channel_listing_skus_table.php`
- Create: `app/app/Modules/Products/Models/ChannelListing.php`
- Create: `app/app/Modules/Products/Models/ChannelListingSku.php`
- Test: `app/tests/Feature/Products/ChannelListingModelTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
// tests/Feature/Products/ChannelListingModelTest.php
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Products\Models\ChannelListingSku;

it('creates a draft listing scoped to tenant with skus', function () {
    $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::factory()->create();
    test()->actingAsTenant($tenant); // helper hiện có; nếu chưa, set app('currentTenant')

    $listing = ChannelListing::create([
        'tenant_id' => $tenant->id,
        'product_id' => 1,
        'channel_account_id' => 1,
        'provider' => 'lazada',
        'status' => ChannelListing::STATUS_DRAFT,
        'attributes' => ['brand_id' => '40516'],
    ]);
    $listing->skus()->create([
        'tenant_id' => $tenant->id,
        'seller_sku' => 'SKU-1',
        'price' => 35000,
        'stock' => 3,
        'sale_props' => ['color_family' => 'Green'],
        'package_weight' => 0.5,
        'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
    ]);

    expect($listing->fresh()->status)->toBe('draft')
        ->and($listing->skus)->toHaveCount(1)
        ->and($listing->skus->first()->sale_props['color_family'])->toBe('Green');
});
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `php artisan test --filter=ChannelListingModelTest`
Expected: FAIL — class/table không tồn tại.

- [ ] **Step 3: Viết migration `channel_listings`**

```php
// ..._create_channel_listings_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('channel_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('product_id')->index();              // master product
            $table->foreignId('channel_account_id')->index();      // shop đích đã OAuth
            $table->string('provider', 32);                        // lazada|tiktok|shopee
            $table->string('external_item_id')->nullable()->index();
            $table->string('category_id')->nullable();             // leaf của sàn
            $table->string('brand_id')->nullable();
            $table->json('attributes')->nullable();                // attr bắt buộc theo sàn
            $table->json('media_refs')->nullable();                // image_id/uri/URL CDN đã upload
            $table->json('logistics')->nullable();                 // channel/template/warehouse
            $table->string('status', 16)->default('draft');        // draft|ready|pushing|live|failed
            $table->json('validation_errors')->nullable();
            $table->string('raw_qc_status')->nullable();           // PENDING/APPROVED/Activate...
            $table->json('last_error')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'product_id', 'channel_account_id'], 'uq_listing_product_shop');
            $table->index(['tenant_id', 'provider', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('channel_listings'); }
};
```

- [ ] **Step 4: Viết migration `channel_listing_skus`**

```php
Schema::create('channel_listing_skus', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->index();
    $table->foreignId('channel_listing_id')->index();
    $table->foreignId('master_variant_id')->nullable();
    $table->string('seller_sku');                  // immutable trên Lazada → giữ ổn định
    $table->json('sale_props')->nullable();        // {color_family, size,...}
    $table->unsignedBigInteger('price');           // VND integer
    $table->unsignedInteger('stock')->default(0);
    $table->decimal('package_weight', 8, 2)->nullable();   // kg
    $table->json('package_dims')->nullable();              // {length,width,height} cm
    $table->string('external_sku_id')->nullable();
    $table->string('image_ref')->nullable();
    $table->timestamps();
    $table->unique(['channel_listing_id', 'seller_sku'], 'uq_listing_seller_sku');
});
```

- [ ] **Step 5: Viết model (BelongsToTenant + constants + casts)**

```php
// Models/ChannelListing.php
class ChannelListing extends Model {
    use BelongsToTenant, SoftDeletes;
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_PUSHING = 'pushing';
    public const STATUS_LIVE = 'live';
    public const STATUS_FAILED = 'failed';
    protected $guarded = [];
    protected function casts(): array {
        return ['attributes'=>'array','media_refs'=>'array','logistics'=>'array',
                'validation_errors'=>'array','last_error'=>'array','pushed_at'=>'datetime'];
    }
    public function skus() { return $this->hasMany(ChannelListingSku::class); }
}
// Models/ChannelListingSku.php
class ChannelListingSku extends Model {
    use BelongsToTenant;
    protected $guarded = [];
    protected function casts(): array { return ['sale_props'=>'array','package_dims'=>'array','price'=>'integer','stock'=>'integer']; }
    public function listing() { return $this->belongsTo(ChannelListing::class, 'channel_listing_id'); }
}
```

- [ ] **Step 6: Migrate + chạy test PASS**

Run: `php artisan migrate && php artisan test --filter=ChannelListingModelTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Products/Database/Migrations app/app/Modules/Products/Models app/tests/Feature/Products/ChannelListingModelTest.php
git commit -m "feat(products): channel_listings + skus model (tenant-scoped)"
```

---

### Task A2: Migration + model progress `ProductPushBatch` & `ProductPushJob`

**Files:**
- Create: `..._create_product_push_batches_table.php`, `..._create_product_push_jobs_table.php`
- Create: `Models/ProductPushBatch.php`, `Models/ProductPushJob.php`
- Test: `app/tests/Feature/Products/PushBatchModelTest.php`

- [ ] **Step 1: Test thất bại**

```php
it('aggregates job progress into batch counters', function () {
    $batch = ProductPushBatch::create(['tenant_id'=>1,'type'=>'push','total'=>2,'status'=>'running','created_by'=>1]);
    $batch->jobs()->create(['tenant_id'=>1,'channel_listing_id'=>1,'status'=>'success','step_label'=>'done','progress'=>100]);
    $batch->jobs()->create(['tenant_id'=>1,'channel_listing_id'=>2,'status'=>'failed','error'=>['msg'=>'x'],'progress'=>100]);
    $batch->recountAndFinish();
    expect($batch->fresh()->succeeded)->toBe(1)->and($batch->fresh()->failed)->toBe(1)->and($batch->fresh()->status)->toBe('done');
});
```

- [ ] **Step 2: Run → FAIL**

Run: `php artisan test --filter=PushBatchModelTest` — Expected FAIL.

- [ ] **Step 3: Migrations**

```php
Schema::create('product_push_batches', function (Blueprint $t) {
    $t->id(); $t->foreignId('tenant_id')->index();
    $t->string('type', 16);                  // push|bulk|clone
    $t->unsignedInteger('total')->default(0);
    $t->unsignedInteger('succeeded')->default(0);
    $t->unsignedInteger('failed')->default(0);
    $t->string('status', 16)->default('running');  // running|done
    $t->foreignId('created_by')->nullable();
    $t->timestamps();
});
Schema::create('product_push_jobs', function (Blueprint $t) {
    $t->id(); $t->foreignId('tenant_id')->index();
    $t->foreignId('product_push_batch_id')->index();
    $t->foreignId('channel_listing_id')->index();
    $t->string('status', 16)->default('queued');   // queued|running|success|failed
    $t->string('step_label')->nullable();
    $t->unsignedTinyInteger('progress')->default(0);
    $t->json('error')->nullable();
    $t->timestamps();
});
```

- [ ] **Step 4: Models**

```php
class ProductPushBatch extends Model {
    use BelongsToTenant; protected $guarded=[];
    public function jobs(){ return $this->hasMany(ProductPushJob::class); }
    public function recountAndFinish(): void {
        $this->succeeded = $this->jobs()->where('status','success')->count();
        $this->failed = $this->jobs()->where('status','failed')->count();
        if ($this->succeeded + $this->failed >= $this->total) $this->status = 'done';
        $this->save();
    }
}
class ProductPushJob extends Model {
    use BelongsToTenant; protected $guarded=[];
    protected function casts(): array { return ['error'=>'array','progress'=>'integer']; }
    public function batch(){ return $this->belongsTo(ProductPushBatch::class,'product_push_batch_id'); }
    public function mark(string $status, ?string $step=null, int $progress=0, ?array $error=null): void {
        $this->fill(array_filter(['status'=>$status,'step_label'=>$step,'progress'=>$progress,'error'=>$error], fn($v)=>$v!==null && $v!==''))->save();
    }
}
```

- [ ] **Step 5: Migrate + test PASS** — Run: `php artisan migrate && php artisan test --filter=PushBatchModelTest`

- [ ] **Step 6: Commit** — `git commit -am "feat(products): push batch/job progress models"`

---

### Task A3: DTO chuẩn cho publishing

**Files:** Create 7 DTO trong `app/app/Integrations/Channels/DTO/`. Test: `app/tests/Unit/Integrations/PublishingDtoTest.php`

- [ ] **Step 1: Test thất bại**

```php
it('builds a listing draft dto immutably', function () {
    $dto = new \CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO(
        title:'Áo thun', description:'<p>mô tả</p>', categoryId:'3',
        brandId:'40516', attributes:['warranty_type'=>'No Warranty'],
        media:[new \CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO(ref:'https://cdn/x.jpg', kind:'cdn_url')],
        skus:[['seller_sku'=>'S1','price'=>35000,'stock'=>3,'sale_props'=>['size'=>'M'],'package_weight'=>0.5,'package_dims'=>['length'=>10,'width'=>10,'height'=>10]]],
        logistics:['channels'=>[]],
    );
    expect($dto->categoryId)->toBe('3')->and($dto->media[0]->ref)->toContain('cdn');
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Tạo DTO (readonly, mirror `ChannelListingDTO` style)**

```php
final readonly class MediaRefDTO { public function __construct(public string $ref, public string $kind, public array $raw=[]) {} } // kind: cdn_url|image_id|uri
final readonly class CategoryNodeDTO { public function __construct(public string $id, public ?string $parentId, public string $name, public bool $isLeaf, public array $raw=[]) {} }
final readonly class ListingAttributeDTO { public function __construct(public string $id, public string $name, public bool $required, public bool $isSaleProp, public string $inputType, public array $values=[], public array $raw=[]) {} }
final readonly class BrandDTO { public function __construct(public string $id, public string $name, public bool $mandatory=false, public array $raw=[]) {} }
final readonly class ListingDraftDTO {
    public function __construct(
        public string $title, public string $description, public string $categoryId,
        public ?string $brandId, public array $attributes, public array $media, public array $skus,
        public array $logistics, public ?string $shortDescription=null, public ?string $videoRef=null,
    ) {}
}
final readonly class ListingResultDTO { public function __construct(public string $externalItemId, public array $skuMap=[], public string $rawStatus='', public array $raw=[]) {} }
final readonly class ListingStatusDTO { public function __construct(public string $externalItemId, public string $rawStatus, public string $normalized, public ?string $reason=null, public array $raw=[]) {} }
```

- [ ] **Step 4: Run → PASS.**

- [ ] **Step 5: Commit** — `git commit -am "feat(integrations): publishing DTOs"`

---

### Task A4: Interface `ProductPublishingConnector` + `ListingValidator` + capability keys

**Files:** Create `Contracts/ProductPublishingConnector.php`, `Contracts/ListingValidator.php`. Test: `app/tests/Unit/Integrations/PublishingContractTest.php`

- [ ] **Step 1: Test thất bại** — assert interface tồn tại + method.

```php
it('declares the publishing contract methods', function () {
    $r = new ReflectionClass(\CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector::class);
    expect($r->isInterface())->toBeTrue();
    foreach (['getCategoryTree','getCategoryAttributes','getBrands','uploadMedia','createListing','getListingStatus'] as $m)
        expect($r->hasMethod($m))->toBeTrue();
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Interface**

```php
interface ProductPublishingConnector {
    /** @return CategoryNodeDTO[] */ public function getCategoryTree(AuthContext $auth, ?string $parentId=null): array;
    /** @return ListingAttributeDTO[] */ public function getCategoryAttributes(AuthContext $auth, string $categoryId): array;
    /** @return BrandDTO[] */ public function getBrands(AuthContext $auth, string $categoryId): array;
    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase='main'): MediaRefDTO;
    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO;
    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO;
}
interface ListingValidator {
    /** @return array<string,string> field => message (rỗng = hợp lệ) */
    public function validate(ListingDraftDTO $draft): array;
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(integrations): ProductPublishingConnector + ListingValidator contracts"`

---

### Task A5: PAT scope `copy-product:push` cho extension (req #3)

**Files:**
- Create: `app/app/Modules/Products/Http/Controllers/ExtensionTokenController.php`
- Modify: `app/app/Modules/Products/Http/routes.php` (route admin mint/revoke + đổi route store của extension)
- Test: `app/tests/Feature/Products/ExtensionTokenTest.php`

- [ ] **Step 1: Test thất bại**

```php
it('mints a non-expiring token limited to copy-product:push', function () {
    $user = userWithTenant();
    $this->actingAs($user);
    $res = $this->postJson('/api/v1/extension-tokens', ['name'=>'My Chrome'])->assertOk();
    $plain = $res->json('data.token');
    $token = \Laravel\Sanctum\PersonalAccessToken::findToken(explode('|', $plain)[1] ?? $plain);
    expect($token->abilities)->toBe(['copy-product:push'])->and($token->expires_at)->toBeNull();
});

it('blocks a copy-push token from reading orders', function () {
    $user = userWithTenant();
    $plain = $user->createToken('ext', ['copy-product:push'])->plainTextToken;
    $this->withHeader('Authorization', "Bearer $plain")->withHeader('X-Tenant-Id', $user->current_tenant_id)
        ->getJson('/api/v1/orders')->assertForbidden();
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Controller + routes**

```php
// ExtensionTokenController.php
public function store(Request $r) {
    $name = $r->string('name')->value() ?: 'Chrome Extension';
    $token = $r->user()->createToken($name, ['copy-product:push']); // expires_at null (mặc định không hết hạn nếu config expiration=null)
    return response()->json(['data'=>['id'=>$token->accessToken->id,'token'=>$token->plainTextToken]]);
}
public function destroy(Request $r, int $id) { $r->user()->tokens()->where('id',$id)->delete(); return response()->noContent(); }
```

```php
// Http/routes.php (trong group auth:sanctum + tenant)
Route::post('extension-tokens', [ExtensionTokenController::class,'store']);
Route::delete('extension-tokens/{id}', [ExtensionTokenController::class,'destroy']);
// route store dùng cho extension — chỉ chấp nhận token ability copy-product:push:
Route::post('products', [ProductController::class,'store'])->middleware('abilities:copy-product:push');
```

> **Lưu ý ổn định:** `config/sanctum.php` `'expiration' => null` để PAT không tự hết hạn (req #3). Các route SPA khác vẫn dùng cookie session (không bị ảnh hưởng). Middleware `abilities` chặn token này gọi nhầm endpoint khác. Thêm rate-limit `throttle:60,1` cho route `products` store.

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(products): scoped non-expiring extension PAT (copy-product:push)"`

---

## Phần B — Lazada publisher (vertical slice đầy đủ)

### Task B1: `LazadaListingValidator` (field bắt buộc theo tài liệu)

**Files:** Create `Lazada/LazadaListingValidator.php`. Test: `app/tests/Unit/Integrations/Lazada/LazadaValidatorTest.php`

- [ ] **Step 1: Test thất bại**

```php
it('flags missing leaf category, brand_id, seller_sku and package dims', function () {
    $v = new LazadaListingValidator();
    $bad = new ListingDraftDTO(title:'', description:'', categoryId:'', brandId:null,
        attributes:[], media:[], skus:[['seller_sku'=>'','price'=>0,'stock'=>0,'sale_props'=>[],'package_weight'=>null,'package_dims'=>[]]], logistics:[]);
    $errors = $v->validate($bad);
    expect($errors)->toHaveKeys(['title','categoryId','brandId','skus.0.seller_sku','skus.0.package_weight']);
});

it('passes a valid single-sku draft', function () {
    $v = new LazadaListingValidator();
    $ok = new ListingDraftDTO(title:'Áo', description:'<p>x</p>', categoryId:'3', brandId:'40516',
        attributes:['warranty_type'=>'No Warranty'],
        media:[new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg','cdn_url')],
        skus:[['seller_sku'=>'S1','price'=>35000,'stock'=>3,'sale_props'=>[],'package_weight'=>0.5,'package_dims'=>['length'=>10,'width'=>10,'height'=>10]]], logistics:[]);
    expect($v->validate($ok))->toBe([]);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Validator (mục 2 tài liệu Lazada)**

```php
final class LazadaListingValidator implements ListingValidator {
    public function validate(ListingDraftDTO $d): array {
        $e = [];
        if (trim($d->title) === '') $e['title'] = 'Tên sản phẩm bắt buộc';
        elseif (mb_strlen($d->title) > 255) $e['title'] = 'Tên tối đa 255 ký tự';
        if ($d->categoryId === '') $e['categoryId'] = 'Phải chọn danh mục lá';
        if (!$d->brandId) $e['brandId'] = 'brand_id bắt buộc (dùng id No Brand nếu không có)';
        if (count($d->media) === 0) $e['media'] = 'Cần ít nhất 1 ảnh đã upload lên CDN Lazada';
        if (count($d->skus) > 1) {
            foreach ($d->skus as $i=>$s) if (empty($s['sale_props'])) $e["skus.$i.sale_props"]='Nhiều SKU phải có sale_props';
        }
        foreach ($d->skus as $i=>$s) {
            if (($s['seller_sku'] ?? '') === '') $e["skus.$i.seller_sku"]='SellerSku bắt buộc';
            if (($s['price'] ?? 0) <= 0) $e["skus.$i.price"]='Giá > 0';
            if (($s['package_weight'] ?? null) === null) $e["skus.$i.package_weight"]='package_weight (kg) bắt buộc';
            foreach (['length','width','height'] as $k) if (!isset($s['package_dims'][$k])) $e["skus.$i.package_$k"]="package_$k (cm) bắt buộc";
        }
        return $e;
    }
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(lazada): listing field validator"`

---

### Task B2: `LazadaProductPayload` build payload `/product/create`

**Files:** Create `Lazada/LazadaProductPayload.php`. Test: `app/tests/Unit/Integrations/Lazada/LazadaPayloadTest.php`

- [ ] **Step 1: Test thất bại** — build XML payload đúng cấu trúc `Request>Product`.

```php
it('builds create-product xml with primary category, brand_id, sku saleProp and package', function () {
    $xml = LazadaProductPayload::toXml(new ListingDraftDTO(
        title:'test', description:'<p>desc</p>', categoryId:'3', brandId:'40516',
        attributes:['short_description'=>'<ul><li>a</li></ul>'],
        media:[new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg','cdn_url')],
        skus:[['seller_sku'=>'S1','price'=>35,'stock'=>3,'sale_props'=>['color_family'=>'Green','size'=>'10'],'package_weight'=>0.5,'package_dims'=>['length'=>10,'width'=>10,'height'=>10]]],
        logistics:[]));
    expect($xml)->toContain('<PrimaryCategory>3</PrimaryCategory>')
        ->toContain('<brand_id>40516</brand_id>')
        ->toContain('<SellerSku>S1</SellerSku>')
        ->toContain('<color_family>Green</color_family>')
        ->toContain('<package_weight>0.5</package_weight>');
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Payload builder (dùng `SimpleXMLElement`/`DOMDocument`, escape an toàn; `Images.Image` & `Skus.Sku` LUÔN là array → tránh err 1001)**

```php
final class LazadaProductPayload {
    public static function toXml(ListingDraftDTO $d): string {
        $dom = new \DOMDocument('1.0','UTF-8'); $dom->formatOutput=false;
        $req = $dom->createElement('Request'); $dom->appendChild($req);
        $p = $dom->createElement('Product'); $req->appendChild($p);
        $p->appendChild($dom->createElement('PrimaryCategory', htmlspecialchars($d->categoryId)));
        $imgs = $dom->createElement('Images');
        foreach ($d->media as $m) $imgs->appendChild($dom->createElement('Image', htmlspecialchars($m->ref)));
        $p->appendChild($imgs);
        $attr = $dom->createElement('Attributes');
        self::child($dom,$attr,'name',$d->title);
        self::child($dom,$attr,'description',$d->description);
        if ($d->shortDescription) self::child($dom,$attr,'short_description',$d->shortDescription);
        self::child($dom,$attr,'brand_id',(string)$d->brandId);
        foreach ($d->attributes as $k=>$v) self::child($dom,$attr,$k,(string)$v);
        $p->appendChild($attr);
        $skus = $dom->createElement('Skus');
        foreach ($d->skus as $s) {
            $sku = $dom->createElement('Sku');
            self::child($dom,$sku,'SellerSku',$s['seller_sku']);
            self::child($dom,$sku,'price',(string)$s['price']);
            self::child($dom,$sku,'quantity',(string)$s['stock']);
            $sp = $dom->createElement('saleProp');
            foreach (($s['sale_props']??[]) as $k=>$v) self::child($dom,$sp,$k,(string)$v);
            $sku->appendChild($sp);
            foreach (['height'=>'package_height','length'=>'package_length','width'=>'package_width'] as $k=>$tag)
                self::child($dom,$sku,$tag,(string)$s['package_dims'][$k]);
            self::child($dom,$sku,'package_weight',(string)$s['package_weight']);
            $skus->appendChild($sku);
        }
        $p->appendChild($skus);
        return $dom->saveXML($dom->documentElement);
    }
    private static function child(\DOMDocument $dom, \DOMElement $parent, string $tag, string $val): void {
        $el = $dom->createElement($tag); $el->appendChild($dom->createTextNode($val)); $parent->appendChild($el);
    }
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(lazada): create-product XML payload builder"`

---

### Task B3: `LazadaPublisher` gọi API (tái dùng `LazadaClient`/`LazadaSigner`)

**Files:** Create `Lazada/LazadaPublisher.php`. Test: `app/tests/Feature/Integrations/Lazada/LazadaPublisherTest.php` (Http::fake)

- [ ] **Step 1: Test thất bại (Http::fake gateway api.lazada.vn/rest)**

```php
it('creates a listing and returns item_id + sku map', function () {
    Http::fake(['*/rest/product/create' => Http::response(['code'=>'0','data'=>['item_id'=>3069252927,'sku_list'=>[['shop_sku'=>'X','seller_sku'=>'S1','sku_id'=>123]]]])]);
    $auth = new AuthContext(channelAccountId:1, provider:'lazada', externalShopId:'shop', accessToken:'tok', region:'VN');
    $pub = app(LazadaPublisher::class);
    $res = $pub->createListing($auth, validLazadaDraft());
    expect($res->externalItemId)->toBe('3069252927')->and($res->skuMap['S1'])->toBe('123');
});

it('throws MarketplaceApiException on lazada error code', function () {
    Http::fake(['*/rest/product/create' => Http::response(['code'=>'IllegalAccessToken','message'=>'token expired'])]);
    $pub = app(LazadaPublisher::class);
    expect(fn()=>$pub->createListing(lazadaAuth(), validLazadaDraft()))->toThrow(MarketplaceApiException::class);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Publisher (gọi qua LazadaClient hiện có; map response; ném exception khi `code !== '0'`)**

```php
final class LazadaPublisher implements ProductPublishingConnector {
    public function __construct(private LazadaClient $client, private LazadaListingValidator $validator) {}

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO {
        $errors = $this->validator->validate($draft);
        if ($errors) throw MarketplaceApiException::validation('lazada', $errors);
        $payload = LazadaProductPayload::toXml($draft);
        $resp = $this->client->call('/product/create', $auth, ['payload'=>$payload]); // POST, body params
        if (($resp['code'] ?? '') !== '0') throw MarketplaceApiException::fromLazada($resp);
        $skuMap = [];
        foreach ($resp['data']['sku_list'] ?? [] as $s) $skuMap[$s['seller_sku']] = (string)($s['sku_id'] ?? '');
        return new ListingResultDTO(externalItemId:(string)$resp['data']['item_id'], skuMap:$skuMap, rawStatus:'PENDING', raw:$resp);
    }

    public function getCategoryTree(AuthContext $auth, ?string $parentId=null): array {
        $resp = $this->client->call('/category/tree/get', $auth, [], 'GET');
        return array_map(fn($n)=>new CategoryNodeDTO((string)$n['category_id'], isset($n['parent_id'])?(string)$n['parent_id']:null, $n['name'], empty($n['children']), $n), self::flatten($resp['data'] ?? []));
    }
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array {
        $resp = $this->client->call('/category/attributes/get', $auth, ['primary_category_id'=>$categoryId], 'GET');
        return array_map(fn($a)=>new ListingAttributeDTO((string)$a['id'], $a['name'], (bool)($a['is_mandatory']??0), (bool)($a['is_sale_prop']??0), (string)($a['input_type']??'text'), $a['options'] ?? [], $a), $resp['data'] ?? []);
    }
    public function getBrands(AuthContext $auth, string $categoryId): array {
        $resp = $this->client->call('/category/brands/query', $auth, ['startRow'=>0,'pageSize'=>100], 'GET');
        return array_map(fn($b)=>new BrandDTO((string)$b['brand_id'], $b['name'], true, $b), $resp['data']['module'] ?? []);
    }
    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase='main'): MediaRefDTO {
        $resp = $this->client->call('/image/migrate', $auth, ['url'=>$imageUrlOrPath]); // ảnh ngoài → CDN Lazada
        return new MediaRefDTO((string)$resp['data']['image']['url'], 'cdn_url', $resp);
    }
    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO {
        $resp = $this->client->call('/products/get', $auth, ['filter'=>'all','item_id'=>$externalItemId], 'GET');
        $raw = $resp['data']['products'][0]['status'] ?? 'unknown';
        return new ListingStatusDTO($externalItemId, $raw, self::normalize($raw), null, $resp);
    }
    // self::flatten / self::normalize helpers...
}
```

> **Lưu ý:** nếu `LazadaClient` chưa có method public `call($path,$auth,$params,$method)`, thêm một method mỏng bọc lại logic ký+gửi hiện có (đừng nhân bản signer). Map lỗi token (`IllegalAccessToken`) → để token-refresh hiện có xử lý ở lớp client; publisher chỉ ném exception.

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(lazada): ProductPublishingConnector (create/taxonomy/media/status)"`

---

### Task B4: Bật capability + đăng ký publisher Lazada

**Files:** Modify `Lazada/LazadaConnector.php` (`capabilities()`), `IntegrationsServiceProvider.php` (bind publisher). Test: `app/tests/Feature/Integrations/PublisherResolutionTest.php`

- [ ] **Step 1: Test thất bại**

```php
it('resolves a lazada publisher and reports capability', function () {
    $registry = app(ChannelRegistry::class);
    expect($registry->for('lazada')->supports('listings.publish'))->toBeTrue();
    expect(app(PublisherRegistry::class)->for('lazada'))->toBeInstanceOf(LazadaPublisher::class);
});
it('throws UnsupportedOperation resolving a tiktok publisher (not yet)', function () {
    expect(fn()=>app(PublisherRegistry::class)->for('tiktok'))->toThrow(UnsupportedOperation::class);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3:** Tạo `PublisherRegistry` (song song `ChannelRegistry`) map provider→publisher class; bind trong `IntegrationsServiceProvider`. Sửa `LazadaConnector::capabilities()` đặt `'listings.publish'=>true,'listings.taxonomy'=>true,'listings.media'=>true,'listings.statusRead'=>true`.

```php
final class PublisherRegistry {
    private array $map = [];
    public function __construct(private Container $c) {}
    public function register(string $p, string $cls): void { $this->map[$p]=$cls; }
    public function for(string $p): ProductPublishingConnector {
        if (!isset($this->map[$p])) throw UnsupportedOperation::for($p,'listings.publish');
        return $this->c->make($this->map[$p]);
    }
    public function has(string $p): bool { return isset($this->map[$p]); }
}
// trong IntegrationsServiceProvider:
$this->app->singleton(PublisherRegistry::class, function ($app) {
    $r = new PublisherRegistry($app);
    $r->register('lazada', LazadaPublisher::class); // tiktok/shopee thêm ở Phần C/D
    return $r;
});
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(integrations): PublisherRegistry + enable lazada listings"`

---

## Phần C — TikTok Shop publisher

> **Khác biệt cốt lõi (từ tài liệu):** `category_version=v2` bắt buộc cho VN; **warehouse bắt buộc mỗi SKU**; `save_mode` AS_DRAFT/LISTING; `price.amount` là **string**; ảnh upload trước → `uri`; **`shop_cipher`** lấy từ `meta` (TikTokClient đã gắn); audit state machine (Draft/Pending/Activate/Failed) theo dõi qua webhook.

### Task C1: `TikTokListingValidator`

**Files:** Create `TikTok/TikTokListingValidator.php`. Test: `LazadaValidatorTest` tương ứng cho TikTok.

- [ ] **Step 1: Test thất bại**

```php
it('flags vn title<25, missing warehouse, zero package_weight, missing v2 category', function () {
    $v = new TikTokListingValidator();
    $bad = new ListingDraftDTO(title:'Áo', description:'desc', categoryId:'', brandId:null, attributes:[],
        media:[new MediaRefDTO('uri-1','uri')],
        skus:[['seller_sku'=>'S1','price'=>1000,'stock'=>1,'sale_props'=>[],'warehouse_id'=>null]],
        logistics:['package_weight'=>0]);
    $e = $v->validate($bad);
    expect($e)->toHaveKeys(['title','categoryId','logistics.package_weight','skus.0.warehouse_id']);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Validator (mục 2 tài liệu TikTok; VN title [25,255], package_weight≠0, warehouse mỗi SKU, ≤9 ảnh, ≤100 SKU)**

```php
final class TikTokListingValidator implements ListingValidator {
    public function validate(ListingDraftDTO $d): array {
        $e=[];
        $len=mb_strlen($d->title);
        if ($len<25 || $len>255) $e['title']='VN: tiêu đề 25–255 ký tự';
        if (mb_strlen($d->description)>10000) $e['description']='Mô tả ≤10.000 ký tự';
        if ($d->categoryId==='') $e['categoryId']='Phải chọn danh mục lá (category_version=v2)';
        if (count($d->media)===0) $e['media']='Cần ≥1 ảnh (uri)'; elseif (count($d->media)>9) $e['media']='Tối đa 9 ảnh';
        if (count($d->skus)>100) $e['skus']='Tối đa 100 SKU';
        if ((float)($d->logistics['package_weight'] ?? 0) <= 0) $e['logistics.package_weight']='package_weight > 0';
        foreach ($d->skus as $i=>$s) {
            if (empty($s['warehouse_id'])) $e["skus.$i.warehouse_id"]='Mỗi SKU phải có warehouse_id';
            if (($s['price'] ?? 0) <= 0) $e["skus.$i.price"]='Giá > 0';
        }
        return $e;
    }
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(tiktok): listing validator (vn rules, warehouse, package weight)"`

---

### Task C2: `TikTokProductPayload` (JSON, price string, sales_attributes, inventory+warehouse)

**Files:** Create `TikTok/TikTokProductPayload.php`. Test JSON shape.

- [ ] **Step 1: Test thất bại**

```php
it('builds create payload with v2 category, string price, warehouse inventory and main_images uri', function () {
    $body = TikTokProductPayload::toBody(tiktokDraft(), saveMode:'LISTING');
    expect($body['category_id'])->toBe('600001')
        ->and($body['category_version'])->toBe('v2')
        ->and($body['save_mode'])->toBe('LISTING')
        ->and($body['main_images'][0]['uri'])->toBe('uri-1')
        ->and($body['skus'][0]['price']['amount'])->toBeString()
        ->and($body['skus'][0]['inventory'][0]['warehouse_id'])->toBe('WH1');
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Payload (amount là string; `package_dimensions` optional VN nên bỏ qua nếu trống)**

```php
final class TikTokProductPayload {
    public static function toBody(ListingDraftDTO $d, string $saveMode='LISTING'): array {
        $body = [
            'title'=>$d->title, 'description'=>$d->description,
            'category_id'=>$d->categoryId, 'category_version'=>'v2', 'save_mode'=>$saveMode,
            'main_images'=>array_map(fn($m)=>['uri'=>$m->ref], $d->media),
            'package_weight'=>['value'=>(string)($d->logistics['package_weight']??''),'unit'=>$d->logistics['weight_unit']??'KILOGRAM'],
            'product_attributes'=>$d->attributes['product_attributes'] ?? [],
            'skus'=>array_map(fn($s)=>[
                'seller_sku'=>$s['seller_sku'],
                'sales_attributes'=>array_map(fn($k,$v)=>['id'=>$k,'value_name'=>$v], array_keys($s['sale_props']??[]), array_values($s['sale_props']??[])),
                'price'=>['amount'=>(string)$s['price'],'currency'=>$s['currency']??'VND'],
                'inventory'=>[['warehouse_id'=>$s['warehouse_id'],'quantity'=>(int)$s['stock']]],
            ], $d->skus),
        ];
        if ($d->brandId) $body['brand_id']=$d->brandId;
        return $body;
    }
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(tiktok): create-product json payload builder"`

---

### Task C3: `TikTokPublisher` (tái dùng `TikTokClient` shop_cipher + x-tts-access-token)

**Files:** Create `TikTok/TikTokPublisher.php`. Test Http::fake host `open-api.tiktokglobalshop.com`.

- [ ] **Step 1: Test thất bại**

```php
it('creates a tiktok product and returns product_id + sku ids', function () {
    Http::fake(['*/product/202309/products' => Http::response(['code'=>0,'data'=>['product_id'=>'17xx','skus'=>[['id'=>'sku-9','seller_sku'=>'S1']]]])]);
    $res = app(TikTokPublisher::class)->createListing(tiktokAuthWithCipher(), tiktokDraft());
    expect($res->externalItemId)->toBe('17xx')->and($res->skuMap['S1'])->toBe('sku-9')->and($res->rawStatus)->toBe('PENDING');
});

it('throws on non-zero tiktok code (warehouse missing 12019022)', function () {
    Http::fake(['*/product/202309/products' => Http::response(['code'=>12019022,'message'=>'SKU must contain a valid warehouse'])]);
    expect(fn()=>app(TikTokPublisher::class)->createListing(tiktokAuthWithCipher(), tiktokDraft()))->toThrow(MarketplaceApiException::class);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Publisher**

```php
final class TikTokPublisher implements ProductPublishingConnector {
    public function __construct(private TikTokClient $client, private TikTokListingValidator $validator) {}
    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO {
        if ($errors=$this->validator->validate($draft)) throw MarketplaceApiException::validation('tiktok',$errors);
        $resp = $this->client->request('POST','/product/202309/products',$auth,[],TikTokProductPayload::toBody($draft));
        if (($resp['code'] ?? -1) !== 0) throw MarketplaceApiException::fromTikTok($resp);
        $skuMap=[]; foreach ($resp['data']['skus'] ?? [] as $s) $skuMap[$s['seller_sku']]=(string)$s['id'];
        return new ListingResultDTO((string)$resp['data']['product_id'],$skuMap,'PENDING',$resp);
    }
    public function getCategoryTree(AuthContext $auth, ?string $parentId=null): array {
        $resp=$this->client->request('GET','/product/202309/categories',$auth,['category_version'=>'v2']);
        return array_map(fn($c)=>new CategoryNodeDTO((string)$c['id'], isset($c['parent_id'])?(string)$c['parent_id']:null, $c['local_name'], (bool)$c['is_leaf'], $c), $resp['data']['categories'] ?? []);
    }
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array {
        $resp=$this->client->request('GET',"/product/202309/categories/{$categoryId}/attributes",$auth,['category_version'=>'v2']);
        return array_map(fn($a)=>new ListingAttributeDTO((string)$a['id'],$a['name'],(bool)($a['is_required']??$a['is_requried']??false),false,(string)($a['type']??'PRODUCT_PROPERTY'),$a['values']??[],$a), $resp['data']['attributes'] ?? []);
    }
    public function getBrands(AuthContext $auth, string $categoryId): array {
        $resp=$this->client->request('GET','/product/202309/brands',$auth,['category_id'=>$categoryId]);
        return array_map(fn($b)=>new BrandDTO((string)$b['id'],$b['name'],false,$b), $resp['data']['brands'] ?? []);
    }
    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase='main'): MediaRefDTO {
        $resp=$this->client->uploadMultipart('/product/202309/images/upload',$auth,'data',$imageUrlOrPath,['use_case'=>$useCase==='main'?'MAIN_IMAGE':'DESCRIPTION_IMAGE']);
        return new MediaRefDTO((string)$resp['data']['uri'],'uri',$resp);
    }
    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO {
        $resp=$this->client->request('GET',"/product/202309/products/{$externalItemId}",$auth);
        $raw=$resp['data']['status'] ?? 'unknown';
        return new ListingStatusDTO($externalItemId,$raw,self::normalize($raw),null,$resp);
    }
}
```

> Nếu `TikTokClient` chưa có `uploadMultipart()`, thêm method bọc upload multipart (tải URL ảnh về tạm rồi gửi `data` file). Tránh nhân bản logic ký.

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(tiktok): ProductPublishingConnector"`

---

### Task C4: Đăng ký publisher TikTok + bật capability

**Files:** Modify `TikTokConnector::capabilities()` + `IntegrationsServiceProvider` (register `'tiktok'`). Test: cập nhật `PublisherResolutionTest` (đổi assert tiktok từ throw → instanceof).

- [ ] **Step 1–4:** Đặt cờ `listings.*=>true` cho TikTok; `$r->register('tiktok', TikTokPublisher::class)`; chạy test PASS; commit `git commit -am "feat(integrations): enable tiktok listings"`.

---

## Phần D — Shopee publisher (đa bước: upload_image → add_item → init_tier_variation)

> **Khác biệt cốt lõi:** ảnh phải `upload_image` trước (không nhận URL) → `image_id`; `tier_variation` **KHÔNG** nằm trong `add_item`, làm sau bằng `init_tier_variation`; `logistic_info` bắt buộc (≥1 channel `enabled=true`); weight/dimension bắt buộc khi channel `SIZE_INPUT`; token sống **4h** (refresh hiện có xử lý); trang field `add_item` chính thức **INACCESSIBLE** → field map cần xác minh khi chạy sandbox (xem rủi ro D-R1).

### Task D1: `ShopeeListingValidator`

**Files:** Create `Shopee/ShopeeListingValidator.php`. Test tương ứng.

- [ ] **Step 1: Test thất bại** — cờ thiếu category, ảnh (image_id), logistic_info, weight khi SIZE_INPUT.

```php
it('flags missing category, image_id list, logistic_info and weight for SIZE_INPUT', function () {
    $v=new ShopeeListingValidator();
    $bad=new ListingDraftDTO(title:'Áo', description:'x', categoryId:'', brandId:null, attributes:[], media:[],
        skus:[['seller_sku'=>'S1','price'=>10000,'stock'=>1,'sale_props'=>[]]],
        logistics:['channels'=>[['logistics_channel_id'=>1,'enabled'=>true,'fee_type'=>'SIZE_INPUT']],'weight'=>null]);
    $e=$v->validate($bad);
    expect($e)->toHaveKeys(['categoryId','media','logistics.weight']);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Validator (mục 2 & 8 tài liệu Shopee)**

```php
final class ShopeeListingValidator implements ListingValidator {
    public function validate(ListingDraftDTO $d): array {
        $e=[];
        if (trim($d->title)==='') $e['title']='Tên bắt buộc';
        if ($d->categoryId==='') $e['categoryId']='Phải chọn danh mục lá';
        if (count($d->media)===0) $e['media']='Cần ≥1 image_id (đã upload_image)';
        $channels=$d->logistics['channels'] ?? [];
        if (!collect($channels)->contains(fn($c)=>!empty($c['enabled']))) $e['logistics.channels']='Cần ≥1 logistics channel enabled';
        $needsSize=collect($channels)->contains(fn($c)=>($c['fee_type']??'')==='SIZE_INPUT');
        if ($needsSize && ($d->logistics['weight']??null)===null) $e['logistics.weight']='weight bắt buộc với SIZE_INPUT';
        foreach ($d->skus as $i=>$s) if (($s['price']??0)<=0) $e["skus.$i.price"]='Giá > 0';
        return $e;
    }
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(shopee): listing validator"`

---

### Task D2: `ShopeeProductPayload` (add_item) + tier_variation builder

**Files:** Create `Shopee/ShopeeProductPayload.php`. Test build body `add_item` (image_id_list, logistic_info, attribute_list, original_price/stock) + body `init_tier_variation`.

- [ ] **Step 1: Test thất bại**

```php
it('builds add_item body with image ids, logistic_info, price/stock', function () {
    $body=ShopeeProductPayload::addItem(shopeeDraftSingleSku());
    expect($body['category_id'])->toBe(100012)
        ->and($body['image']['image_id_list'][0])->toBe('img-1')
        ->and($body['logistic_info'][0]['enabled'])->toBeTrue()
        ->and($body['original_price'])->toBe(10000)
        ->and($body['normal_stock'])->toBe(5);
});
it('builds init_tier_variation body for 2 variants', function () {
    $body=ShopeeProductPayload::tierVariation(123, shopeeDraftTwoSku());
    expect($body['item_id'])->toBe(123)->and($body['tier_variation'])->toHaveCount(1)->and($body['model'])->toHaveCount(2);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Payload builders (item không biến thể: giá/stock trên add_item; có biến thể: add_item không giá-per-model, rồi init_tier_variation)**

```php
final class ShopeeProductPayload {
    public static function addItem(ListingDraftDTO $d): array {
        $first=$d->skus[0];
        $body=[
            'category_id'=>(int)$d->categoryId,
            'item_name'=>$d->title,
            'description'=>$d->description,
            'image'=>['image_id_list'=>array_map(fn($m)=>$m->ref, $d->media)],
            'logistic_info'=>array_map(fn($c)=>array_filter([
                'logistics_channel_id'=>$c['logistics_channel_id'],'enabled'=>(bool)$c['enabled'],
                'size_id'=>$c['size_id']??null,'shipping_fee'=>$c['shipping_fee']??null,
            ], fn($v)=>$v!==null), $d->logistics['channels']),
            'weight'=>$d->logistics['weight'] ?? null,
        ];
        if ($d->brandId) $body['brand']=['brand_id'=>(int)$d->brandId];
        if (!empty($d->attributes['attribute_list'])) $body['attribute_list']=$d->attributes['attribute_list'];
        if (isset($d->logistics['dimension'])) $body['dimension']=$d->logistics['dimension'];
        if (count($d->skus)===1) { $body['original_price']=$first['price']; $body['normal_stock']=(int)$first['stock']; if(!empty($first['seller_sku'])) $body['item_sku']=$first['seller_sku']; }
        return array_filter($body, fn($v)=>$v!==null);
    }
    public static function tierVariation(int $itemId, ListingDraftDTO $d): array {
        // gom sale_props thành tiers (tối đa 2); mỗi sku → model với tier_index
        [$tiers,$models]=self::buildTiers($d->skus);
        return ['item_id'=>$itemId,'tier_variation'=>$tiers,'model'=>$models];
    }
    // buildTiers(): tính option_list theo thứ tự, tier_index bắt đầu 0, model{tier_index,original_price,normal_stock,model_sku}
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(shopee): add_item + tier_variation payload builders"`

---

### Task D3: `ShopeePublisher` (upload_image → add_item → init_tier_variation, chờ ~5s)

**Files:** Create `Shopee/ShopeePublisher.php`. Test Http::fake host `partner.shopeemobile.com`.

- [ ] **Step 1: Test thất bại**

```php
it('uploads image, creates item, then inits variation for multi-sku', function () {
    Http::fake([
        '*/media_space/upload_image*'=>Http::response(['response'=>['image_info'=>['image_id'=>'img-1']]]),
        '*/product/add_item*'=>Http::response(['item_id'=>555,'response'=>['item_id'=>555]]),
        '*/product/init_tier_variation*'=>Http::response(['response'=>['model'=>[['model_id'=>1,'tier_index'=>[0]],['model_id'=>2,'tier_index'=>[1]]]]]),
    ]);
    $res=app(ShopeePublisher::class)->createListing(shopeeAuth(), shopeeDraftTwoSku());
    expect($res->externalItemId)->toBe('555')->and($res->raw)->toHaveKey('tier');
});

it('throws on shopee error envelope', function () {
    Http::fake(['*/product/add_item*'=>Http::response(['error'=>'error_param','message'=>'Invalid category id'])]);
    expect(fn()=>app(ShopeePublisher::class)->createListing(shopeeAuth(), shopeeDraftSingleSku()))->toThrow(MarketplaceApiException::class);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Publisher (orchestrate; dùng `Sleep::for(5)->seconds()` để test fake được; map `error` envelope)**

```php
final class ShopeePublisher implements ProductPublishingConnector {
    public function __construct(private ShopeeClient $client, private ShopeeListingValidator $validator) {}
    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO {
        if ($errors=$this->validator->validate($draft)) throw MarketplaceApiException::validation('shopee',$errors);
        $resp=$this->client->call('/product/add_item',$auth, ShopeeProductPayload::addItem($draft));
        if (!empty($resp['error'])) throw MarketplaceApiException::fromShopee($resp);
        $itemId=(int)($resp['item_id'] ?? $resp['response']['item_id']);
        $raw=['add_item'=>$resp];
        if (count($draft->skus)>1) {
            \Illuminate\Support\Sleep::for(5)->seconds(); // data delay theo tài liệu
            $tv=$this->client->call('/product/init_tier_variation',$auth, ShopeeProductPayload::tierVariation($itemId,$draft));
            if (!empty($tv['error'])) throw MarketplaceApiException::fromShopee($tv);
            $raw['tier']=$tv;
        }
        return new ListingResultDTO((string)$itemId, [], 'NORMAL', $raw);
    }
    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase='main'): MediaRefDTO {
        $resp=$this->client->uploadImage('/media_space/upload_image',$auth,$imageUrlOrPath,$useCase==='main'?'normal':'desc');
        return new MediaRefDTO((string)$resp['response']['image_info']['image_id'],'image_id',$resp);
    }
    public function getCategoryTree(AuthContext $auth, ?string $parentId=null): array {
        $resp=$this->client->call('/product/get_category',$auth,['language'=>'vi'],'GET');
        return array_map(fn($c)=>new CategoryNodeDTO((string)$c['category_id'], (string)$c['parent_category_id'], $c['display_category_name'], !$c['has_children'], $c), $resp['response']['category_list'] ?? []);
    }
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array {
        $resp=$this->client->call('/product/get_attribute_tree',$auth,['category_id_list'=>$categoryId,'language'=>'vi'],'GET');
        $list=$resp['response']['list'][0]['attribute_tree'] ?? [];
        return array_map(fn($a)=>new ListingAttributeDTO((string)$a['attribute_id'],$a['original_attribute_name'],(bool)($a['mandatory']??false),false,(string)($a['input_type']??''),$a['attribute_value_list']??[],$a), $list);
    }
    public function getBrands(AuthContext $auth, string $categoryId): array {
        $resp=$this->client->call('/product/get_brand_list',$auth,['category_id'=>(int)$categoryId,'page_size'=>100,'offset'=>0,'status'=>1],'GET');
        return array_map(fn($b)=>new BrandDTO((string)$b['brand_id'],$b['original_brand_name'],false,$b), $resp['response']['brand_list'] ?? []);
    }
    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO {
        $resp=$this->client->call('/product/get_item_base_info',$auth,['item_id_list'=>$externalItemId],'GET');
        $raw=$resp['response']['item_list'][0]['item_status'] ?? 'unknown';
        return new ListingStatusDTO($externalItemId,$raw,self::normalize($raw),null,$resp);
    }
}
```

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(shopee): ProductPublishingConnector (multi-step)"`

---

### Task D4: Đăng ký publisher Shopee + bật capability

**Files:** Modify `ShopeeConnector::capabilities()` + `IntegrationsServiceProvider`. Test cập nhật `PublisherResolutionTest` cho shopee.

- [ ] **Step 1–4:** Bật cờ; `$r->register('shopee', ShopeePublisher::class)`; PASS; commit `git commit -am "feat(integrations): enable shopee listings"`.

---

## Phần E — Orchestration, API, tiến trình

### Task E1: `MediaPrepService` — resize ảnh theo ràng buộc sàn rồi upload

**Files:** Create `Products/Services/MediaPrepService.php`. Test: `app/tests/Feature/Products/MediaPrepServiceTest.php`

- [ ] **Step 1: Test thất bại** — Lazada: ảnh >3MB hoặc >5000px bị resize trước khi `uploadMedia`; trả về `MediaRefDTO` đúng `kind`.

```php
it('resizes oversized images before lazada migrate (<=3MB, <=5000px)', function () {
    // fake publisher.uploadMedia ghi nhận kích thước đầu vào
    $svc=app(MediaPrepService::class);
    $refs=$svc->prepare('lazada', lazadaAuth(), ['https://src/huge.jpg']); // huge = 6000px/5MB stub
    expect($refs[0])->toBeInstanceOf(MediaRefDTO::class);
    // assert resize được gọi (spy) — chi tiết tùy image lib (Intervention)
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Service** — tải ảnh, nếu `provider==='lazada'` và (size>3MB || cạnh>5000px) thì resize (Intervention Image hoặc GD) về ≤5000px/≤3MB; TikTok ép trong [300,4000]px; Shopee ≤10MB. Sau đó gọi `PublisherRegistry::for($provider)->uploadMedia()`. Trả mảng `MediaRefDTO`. Cache theo hash ảnh + provider để **không upload lại** (bảng `listing_media_cache` hoặc cache store).

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(products): media prep + per-marketplace image constraints"`

---

### Task E2: `ListingTaxonomyService` (proxy danh mục/attr/brand + cache) + controller

**Files:** Create `Services/ListingTaxonomyService.php`, `Http/Controllers/ListingTaxonomyController.php`, routes. Test feature endpoint.

- [ ] **Step 1: Test thất bại**

```php
it('returns leaf categories for a connected shop and caches them', function () {
    // fake publisher.getCategoryTree
    $this->actingAsTenantUser();
    $res=$this->getJson('/api/v1/channels/lazada/categories?channel_account_id=1')->assertOk();
    expect($res->json('data.0.is_leaf'))->toBeBool();
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Service** — gọi publisher; **cache** kết quả theo `(provider, category_id)` với TTL (vd 12h) qua `Cache::remember` (danh mục/attr/brand đổi rất chậm → giảm tải API + tránh rate-limit). Controller mỏng: FormRequest → service → Resource.

> **Hiệu năng:** danh mục Lazada/Shopee rất lớn — trả **theo tầng** (`parent_id`) thay vì cả cây; attr/brand chỉ nạp khi đã chọn danh mục lá (lazy ở FE).

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(products): taxonomy proxy endpoints with cache"`

---

### Task E3: `ListingDraftService` + controller (tạo/sửa nháp, chạy validator → ready)

**Files:** Create `Services/ListingDraftService.php`, `Http/Controllers/ChannelListingController.php`, `Http/Requests/*`, `Http/Resources/ChannelListingResource.php`, routes. Test feature.

- [ ] **Step 1: Test thất bại**

```php
it('creates a draft listing from a master product for a shop', function () {
    $this->actingAsTenantUser();
    $res=$this->postJson('/api/v1/products/1/listings', ['channel_account_id'=>1,'provider'=>'lazada'])->assertCreated();
    expect($res->json('data.status'))->toBe('draft');
});
it('moves draft to ready only when validator passes', function () {
    $this->actingAsTenantUser();
    // sửa thiếu brand → vẫn draft + trả validation_errors
    $this->putJson('/api/v1/listings/1', ['category_id'=>''])->assertOk()->assertJsonPath('data.status','draft');
    // sửa đủ → ready
    $this->putJson('/api/v1/listings/1', validLazadaListingPayload())->assertOk()->assertJsonPath('data.status','ready');
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Service** — `createDraft(productId, channelAccountId, provider)` map master → `ChannelListing(draft)` + skus (sinh `seller_sku` ổn định, vd `MP{product}-{variant}`). `update(listingId, data)` lưu field; build `ListingDraftDTO` từ listing; chạy `ListingValidator` của provider; rỗng → `status=ready`, ngược lại `status=draft` + lưu `validation_errors`. Controller thin.

- [ ] **Step 4: Run → PASS. Commit** — `git commit -am "feat(products): listing draft service + editor endpoints"`

---

### Task E4: `PushListingJob` + `ListingPushService` + endpoint + progress

**Files:** Create `Jobs/PushListingJob.php`, `Services/ListingPushService.php`, `Http/Controllers/ListingPushController.php`, `Http/Resources/PushBatchResource.php`, routes. Test feature + job.

- [ ] **Step 1: Test thất bại**

```php
it('enqueues a push batch and pushes a ready listing to live', function () {
    Queue::fake();
    $this->actingAsTenantUser();
    $res=$this->postJson('/api/v1/listings/1/push')->assertOk();
    $batchId=$res->json('data.batch_id');
    Queue::assertPushedOn('listings', PushListingJob::class);
    expect(ProductPushBatch::find($batchId)->total)->toBe(1);
});

it('job pushes via publisher, stores external_item_id, marks success + progress', function () {
    // fake PublisherRegistry.for('lazada')->createListing → ListingResultDTO(item_id)
    (new PushListingJob($jobRowId))->handle(app(PublisherRegistry::class), app(MediaPrepService::class));
    $listing=ChannelListing::find(1);
    expect($listing->status)->toBe('live')->and($listing->external_item_id)->not->toBeNull();
    expect(ProductPushJob::first()->status)->toBe('success');
});

it('job marks failed + last_error on api exception, does not throw', function () {
    // fake createListing ném MarketplaceApiException
    (new PushListingJob($jobRowId))->handle(...);
    expect(ChannelListing::find(1)->status)->toBe('failed')->and(ProductPushJob::first()->status)->toBe('failed');
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Service + Job**

```php
// ListingPushService.php
public function push(array $listingIds, int $userId, string $type='push'): ProductPushBatch {
    $batch=ProductPushBatch::create(['tenant_id'=>app('currentTenant')->id,'type'=>$type,'total'=>count($listingIds),'status'=>'running','created_by'=>$userId]);
    foreach ($listingIds as $lid) {
        $listing=ChannelListing::findOrFail($lid);
        abort_unless($listing->status==='ready', 422, "Listing $lid chưa ready");
        $listing->update(['status'=>'pushing']);
        $row=$batch->jobs()->create(['tenant_id'=>$batch->tenant_id,'channel_listing_id'=>$lid,'status'=>'queued','progress'=>0]);
        PushListingJob::dispatch($row->id)->onQueue('listings');
    }
    return $batch;
}
```

```php
// PushListingJob.php
class PushListingJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=2; public int $timeout=180;
    public function __construct(public int $jobRowId) { $this->onQueue('listings'); }
    public function handle(PublisherRegistry $pubs, MediaPrepService $media): void {
        $row=ProductPushJob::findOrFail($this->jobRowId);
        $listing=ChannelListing::with('skus')->findOrFail($row->channel_listing_id);
        try {
            $row->mark('running','Đang chuẩn bị ảnh',10);
            $auth=ChannelAccount::findOrFail($listing->channel_account_id)->authContext();
            $refs=$media->prepare($listing->provider,$auth, $listing->media_refs['source_urls'] ?? []);
            $row->mark('running','Đang tạo listing trên sàn',60);
            $draft=ListingDraftMapper::fromListing($listing,$refs);
            $result=$pubs->for($listing->provider)->createListing($auth,$draft);
            $listing->update(['status'=>'live','external_item_id'=>$result->externalItemId,'raw_qc_status'=>$result->rawStatus,'pushed_at'=>now(),'last_error'=>null]);
            $row->mark('success','Hoàn tất',100);
        } catch (\Throwable $e) {
            $listing->update(['status'=>'failed','last_error'=>['message'=>$e->getMessage()]]);
            $row->mark('failed','Lỗi',100,['message'=>$e->getMessage()]);
        } finally {
            $row->batch->recountAndFinish();
        }
    }
}
```

> **Idempotency/ổn định:** trước khi tạo, nếu `external_item_id` đã tồn tại → gọi update thay vì create (tránh tạo trùng khi job retry). Job **không throw** ra ngoài (đã catch) để 1 listing lỗi không làm hỏng cả batch; nhưng vẫn dùng `tries=2` cho lỗi tạm thời mạng (lỗi nghiệp vụ sàn không nên retry — phân loại exception: `MarketplaceApiException::isRetryable()`).

- [ ] **Step 4: Endpoint progress** — `GET /api/v1/push-batches/{id}` trả `{total,succeeded,failed,status,jobs:[{listing_id,status,step_label,progress,error}]}` (Resource). Route `POST /listings/{id}/push` + `POST /listings/bulk-push` (bulk nhận `listing_ids[]`).

- [ ] **Step 5: Run → PASS. Commit** — `git commit -am "feat(products): push job + batch progress endpoint"`

---

## Phần F — SPA (React + Ant Design)

### Task F1: API client + hooks (TanStack Query + polling)

**Files:** Create `app/resources/js/features/products/api.ts`, `hooks.ts`. Test: `npm run typecheck` (không có test runner JS — theo baseline dự án).

- [ ] **Step 1: Viết `api.ts`** — `createListing`, `getListing`, `updateListing`, `pushListing(ids)`, `getCategories(provider, shopId, parentId?)`, `getAttributes`, `getBrands`, `getPushBatch(id)` (đều qua `tenantApi()`).

- [ ] **Step 2: Viết `hooks.ts`** — `usePushBatch(id)` dùng `useQuery({ queryKey:['push-batch',id], queryFn, refetchInterval: q => q.state.data?.status==='done' ? false : 1500 })` (poll tới khi done). `useCategories`/`useAttributes` với `enabled` lazy theo lựa chọn.

- [ ] **Step 3:** `npm run lint && npm run typecheck` PASS. **Commit** — `git commit -am "feat(spa): products listing api + polling hooks"`

---

### Task F2: `PushProgressModal` (req #4) + `ProductListPage`

**Files:** Create `PushProgressModal.tsx`, `ProductListPage.tsx`; add routes vào `app.tsx`. Test: typecheck + chạy app thủ công.

- [ ] **Step 1:** `PushProgressModal` — `Modal` AntD, `Progress` tổng (`succeeded+failed)/total*100`), `List` từng listing với icon trạng thái (`@ant-design/icons` — KHÔNG emoji), `step_label`, lỗi đỏ. Đóng disabled khi `status!=='done'`.

- [ ] **Step 2:** `ProductListPage` — `Table` sản phẩm hệ thống + lọc trạng thái; cột "Sàn" hiển thị các `ChannelListing` (badge draft/ready/live/failed); nút "Tạo nháp sàn" mở `ListingEditorDrawer`; chọn nhiều → "Đẩy hàng loạt" (Phần G). Toolbar luôn hiện, validate-by-disable (theo memory UI).

- [ ] **Step 3:** typecheck/lint PASS. **Commit** — `git commit -am "feat(spa): push progress modal + product list"`

---

### Task F3: `ListingEditorDrawer` + `CategoryPicker` + `AttributeForm`

**Files:** Create 3 component. 

- [ ] **Step 1:** `CategoryPicker` — nạp danh mục **theo tầng** (lazy load children khi mở node), chỉ cho chọn lá; dùng `Cascader`/`TreeSelect` AntD `loadData`.

- [ ] **Step 2:** `AttributeForm` — render form từ `getAttributes(categoryId)`; field bắt buộc (`required`) đánh dấu; `is_sale_prop` đẩy xuống bảng biến thể; input theo `input_type` (select/text/number/date). Ưu tiên `Radio.Group/Segmented` cho tập nhỏ thay `Select` (memory UI).

- [ ] **Step 3:** `ListingEditorDrawer` — gộp: tiêu đề, mô tả (rich text), CategoryPicker, brand (Select có search — ngoại lệ vì danh sách lớn), ảnh (upload→hiển thị), bảng SKU (giá/tồn/sale_props/package), logistics theo provider. Nút "Lưu nháp" và "Lưu & đánh dấu sẵn sàng" (gọi update → nếu `ready` thì cho phép push). Hiển thị `validation_errors` inline.

- [ ] **Step 4:** typecheck/lint PASS. **Commit** — `git commit -am "feat(spa): listing editor (category/attributes/skus/logistics)"`

---

## Phần G — Extension (`cmb_copy_product`)

### Task G1: Thống nhất API base + PAT scope

**Files:** Modify `config/env.js` (đổi `API_BASE_URL` về `/api/v1`), `background/background.js` (sau login gọi `/extension-tokens` lấy PAT scope, lưu `chrome.storage.local.copyPushToken`; đính `Authorization: Bearer` token này cho `POST /products`).

- [ ] **Step 1:** Sửa `env.js` `API_BASE_URL: 'https://app.cmbcore/api/v1'`.
- [ ] **Step 2:** `background.js` — sau `/auth/login` thành công, `POST /extension-tokens {name:'Chrome'}` → lưu token; `createDraftProduct()` đổi endpoint `/products` + header Bearer = `copyPushToken` (không dùng token login hết hạn). Bỏ logic kiểm tra `authExpiry` cho đường push.
- [ ] **Step 3:** Test thủ công: copy 1 sản phẩm → tạo thành công với token scope. **Commit** (repo extension) — `git commit -am "feat: api/v1 + scoped non-expiring push token"`

---

### Task G2: Popup thanh tiến trình khi copy (req #4)

**Files:** Create `popup/progress.html`, `popup/progress.js`; Modify `content/*.js` để bắn các bước.

- [ ] **Step 1:** Component progress (overlay góc) với các bước: `Đang lấy dữ liệu sản phẩm` → `Đang chuẩn hoá` → `Đang gửi lên hệ thống` → `Xong/Lỗi`, kèm thanh %.
- [ ] **Step 2:** `content/*.js` cập nhật bước qua `postMessage`/DOM thay cho toast hiện tại.
- [ ] **Step 3:** Test thủ công mỗi sàn. **Commit** — `git commit -am "feat: copy progress popup"`

---

## Self-Review (đã rà soát plan ↔ spec)

- **req #1 (2 trạng thái):** Task A1 (ChannelListing state machine) + E3 (draft→ready). ✔
- **req #3 (token scope không hết hạn):** Task A5 + G1. ✔
- **req #4 (popup tiến trình):** A2/E4 (progress model+endpoint) + F2 (modal) + G2 (extension popup). ✔
- **req #8 (tài liệu):** đã có file riêng. ✔
- **3 sàn:** Lazada B1–B4, TikTok C1–C4, Shopee D1–D4. ✔
- **req #2 clone / #5 bulk-edit / #7 kéo-đồng-bộ:** **KHÔNG** trong plan này — tách plan kế tiếp (Phần "Out of scope"). Bulk push (đẩy hàng loạt) đã có hạ tầng ở E4 (`bulk-push`), nhưng **bulk EDIT** (sửa hàng loạt) thuộc plan sau.

## Out of scope (plan kế tiếp — `...-publishing-phase2-plan.md`)
1. **Bulk edit (req #5):** multi-select ChannelListing(draft) → áp thay đổi hàng loạt (danh mục/giá/template ship) → bulk-push (đã có endpoint).
2. **Clone cùng nền tảng (req #2):** từ listing `live` → tạo nhanh listing shop khác cùng provider, tái dùng category/attr/brand đã validate.
3. **Kéo & đồng bộ chéo sàn (req #7):** `listListings` (get_item_list) → MasterProduct; copy chéo theo edit-gate. Cần bổ sung capability `listings.list` cho 3 publisher.

---

## Phụ lục — Đánh giá lỗi tiềm ẩn, hiệu năng, phương án ổn định

### 1. Lỗi tiềm ẩn theo sàn

**Lazada**
- **L-R1 Ảnh CDN bắt buộc:** ảnh nguồn (Shopee/TikTok) không dùng trực tiếp; phải `image/migrate` (≤3MB, 330–5000px). *Ổn định:* `MediaPrepService` resize trước (Task E1) + cache theo hash → tránh fail "image invalid" và upload lặp.
- **L-R2 `SellerSku` immutable:** sửa SKU sau khi tạo sẽ lỗi. *Ổn định:* sinh seller_sku ổn định từ master id, không đổi qua các lần push; update chỉ giá/tồn qua `/product/price_quantity/update`.
- **L-R3 `Images.Image`/`Skus.Sku` phải là array (err 1001):** builder DOM luôn tạo collection (Task B2).
- **L-R4 >50 SKU timeout:** chia batch ~20 SKU/lần create (thêm nhánh trong publisher khi `count(skus)>20`).
- **L-R5 brand_id theo quốc gia:** "No Brand" id khác mỗi nước → lấy động từ `getBrands`, không hardcode.

**TikTok Shop**
- **T-R1 Warehouse bắt buộc mỗi SKU (err 12019022):** validator chặn sớm (C1); cần API lấy warehouse khi mở editor (bổ sung `getWarehouses` ở taxonomy service — thêm trước khi bật push TikTok).
- **T-R2 `price.amount` là string:** payload ép string (C2); nếu để số → err currency/format.
- **T-R3 `category_version=v2` bắt buộc VN (err 12052217):** cố định `v2` ở payload + taxonomy.
- **T-R4 title VN [25,255]:** validator chặn (nhiều sản phẩm copy có title ngắn → cần FE nhắc bổ sung).
- **T-R5 Audit bất đồng bộ:** sau create là `PENDING`, không `live` ngay. *Ổn định:* lưu `raw_qc_status=PENDING`; đăng ký webhook "Product status change" hoặc poll `getListingStatus` định kỳ để cập nhật `live/failed` (thêm job `RefreshListingStatus` — khuyến nghị ở plan này nếu cần realtime, hoặc phase sau).
- **T-R6 `shop_cipher`:** nếu thiếu trong `meta` → mọi call fail; kiểm tra khi tạo draft, báo lỗi rõ "shop chưa uỷ quyền lại".

**Shopee**
- **S-R1 Field `add_item` chính thức INACCESSIBLE:** payload builder (D2) dựa trên developer-guide, **phải xác minh trên sandbox** trước khi bật prod (chạy 1 add_item thật, đối chiếu field). Đây là rủi ro cao nhất — đặt **cổng kiểm thử sandbox bắt buộc** trước Task D4.
- **S-R2 tier_variation tách rời + data delay:** `Sleep 5s` rồi `init_tier_variation`; nếu vẫn lỗi "item not ready" → retry có backoff. Token 4h: thao tác đa bước có thể vượt nếu nghẽn → đảm bảo refresh token trước chuỗi.
- **S-R3 Ảnh chỉ nhận image_id:** bắt buộc upload_image trước (đã orchestrate D3).
- **S-R4 logistic_info SIZE_INPUT cần weight+dimension:** validator (D1) + editor thu thập.

**Xuyên sàn**
- **X-R1 Idempotency:** job retry có thể tạo trùng. *Ổn định:* check `external_item_id` trước create → update; với TikTok dùng `idempotency_key` (UUID theo listing id).
- **X-R2 Rate limit / quota:** TikTok 100 sp/ngày (seller mới). *Ổn định:* bulk push **throttle** + đếm quota/ngày, dừng & báo khi chạm trần (log rõ, không nuốt).
- **X-R3 Token hết hạn giữa chừng:** dùng refresh hiện có ở client; publisher chỉ ném exception, không tự xử token.
- **X-R4 PII/secret:** secret sàn chỉ server; PAT extension scope hẹp + revocable + throttle.

### 2. Hiệu năng
- **Taxonomy cache (E2):** danh mục/attr/brand đổi chậm → `Cache::remember` TTL 12h, nạp **theo tầng** + lazy attr/brand. Tránh kéo cả cây (Shopee/Lazada hàng nghìn node).
- **Ảnh:** `MediaPrepService` cache theo hash+provider để **không re-upload**; resize phía server (GD/Intervention) chạy trong job, không chặn request.
- **Push song song có kiểm soát:** queue `listings` (supervisor-sync, maxProcesses 6) → tối đa ~6 listing đồng thời; bulk N listing không làm sập API sàn. 1 job = 1 listing (cô lập lỗi, tiến trình mịn).
- **Polling tiến trình:** FE poll 1.5s, **tự dừng khi `done`** (refetchInterval=false) → không poll vô hạn.
- **DB:** index `(tenant_id,provider,status)` + unique `(tenant_id,product_id,channel_account_id)`; progress đọc 1 batch + jobs (join nhẹ).

### 3. Phương án ổn định (tổng)
- **Validate trước khi gọi sàn** (mỗi provider validator) → biến lỗi API mơ hồ thành lỗi field rõ ràng cho user (đúng tinh thần "nháp cần sửa").
- **Capability gating + UnsupportedOperation** → bật từng sàn độc lập, không vỡ core; Lazada lên trước, TikTok/Shopee bật khi sandbox xanh.
- **Job cô lập + phân loại retry:** lỗi mạng (retryable) vs lỗi nghiệp vụ sàn (không retry) → tránh spam tạo trùng và tránh "kẹt im lặng" (queue đã trong supervisor).
- **Sandbox gate cho Shopee (S-R1)** và **warehouse API cho TikTok (T-R1)** là 2 điều kiện chặn bật prod tương ứng — ghi rõ trong checklist trước Task D4/C4.
- **Quan trắc:** lỗi push → `last_error` + Sentry; quota/ratelimit → log cảnh báo; trạng thái QC bất đồng bộ (TikTok/Lazada) cập nhật qua webhook/poll.
