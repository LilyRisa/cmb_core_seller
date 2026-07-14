# Gộp nhóm biến thể theo sản phẩm + sao chép hàng loạt Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Trang "Sản phẩm đã có trên sàn" hiển thị 1 dòng/sản phẩm (mở rộng xem từng biến thể) thay vì phẳng theo biến thể, có checkbox chọn nhiều sản phẩm để sao chép hàng loạt sang gian hàng khác.

**Architecture:** Endpoint mới `GET /channel-listings/grouped` phân trang theo sản phẩm (2 bước: lấy trang nhóm distinct rồi lấy đủ dòng biến thể của các nhóm đó) — tách riêng khỏi endpoint `/channel-listings` phẳng đang dùng chung 4 nơi khác. Endpoint mới `POST /channel-listings/bulk-clone-to-shops` lặp gọi `MarketplaceCloneService::cloneToShops()` có sẵn cho từng sản phẩm, cô lập lỗi từng phần tử.

**Tech Stack:** Laravel 11 (PHPUnit `RefreshDatabase`), React + TypeScript + Ant Design (`Table.expandable`) + TanStack Query.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`, không phải repo root.
- **Không đổi** endpoint `GET /channel-listings` (phẳng) — đang dùng chung ở `SkuPickerModal.tsx`, `InventoryPage.tsx`, `ChannelLinkModal.tsx`, `OnChannelPage.tsx` (trước khi đổi). Mọi thay đổi nằm ở endpoint MỚI `/channel-listings/grouped`.
- Không đổi schema `channel_listings` (SPEC 0003 — 1 hàng/biến thể ở tầng lưu trữ vẫn đúng thiết kế).
- Không sửa bên trong `MarketplaceCloneService::cloneToShops()`/`MarketplaceListingEditService` — chỉ gọi lại nguyên trạng.
- Không có JS test runner trong repo — task frontend verify bằng `npm run typecheck && npm run lint && npm run build` + kiểm thủ công qua trình duyệt.
- Danh sách gian hàng đích khi sao chép hàng loạt = **mọi gian hàng active** (không lọc trừ nguồn) — backend `cloneToShops()` đã tự bỏ qua nếu đích trùng nguồn của từng sản phẩm.
- Spec đầy đủ: `docs/specs/2026-07-14-channel-listing-grouping-and-bulk-clone.md`.

---

### Task 1: Backend — endpoint `GET /channel-listings/grouped`

**Files:**
- Modify: `app/app/Modules/Products/Http/Controllers/ChannelListingController.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Products/ChannelListingGroupedTest.php`

**Interfaces:**
- Consumes: `ChannelListing` model (đã có, `external_product_id`, `channel_account_id`, `title`, `image`), `ChannelListingResource` (đã có, không đổi), `PromotionService::busyPromoPrices()` (đã có, dùng lại nguyên trạng qua `applyFilters()`).
- Produces: `GET /api/v1/channel-listings/grouped?<cùng query param của /channel-listings>` → `{ data: [{ channel_account_id:int, external_product_id:string|null, title:string|null, image:string|null, variant_count:int, variants: [ChannelListingResource...] }], meta: { pagination:{page,per_page,total,total_pages} } }` — `total`/`total_pages` tính theo SỐ SẢN PHẨM. Task 3 (FE hook) tiêu thụ đúng shape này.

- [ ] **Step 1: Viết test thất bại**

Tạo `app/tests/Feature/Products/ChannelListingGroupedTest.php`:

```php
<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelListingGroupedTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        $this->accountId = (int) ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'shop1', 'shop_name' => 'Shop 1', 'shop_region' => 'VN',
            'status' => 'active', 'access_token' => 'tok',
        ])->getKey();
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeListing(string $extSku, ?string $extProductId, string $title, ?int $tenantId = null, ?int $accountId = null): ChannelListing
    {
        return ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(), 'channel_account_id' => $accountId ?? $this->accountId,
            'external_product_id' => $extProductId, 'external_sku_id' => $extSku, 'title' => $title,
            'currency' => 'VND', 'sync_status' => ChannelListing::SYNC_OK,
        ]);
    }

    public function test_groups_variants_by_external_product_id(): void
    {
        $this->makeListing('S1', 'P1', 'Máy cạo râu');
        $this->makeListing('S2', 'P1', 'Máy cạo râu');
        $this->makeListing('S3', 'P1', 'Máy cạo râu');
        $this->makeListing('S4', 'P2', 'Tượng chú tiểu');
        $this->makeListing('S5', null, 'Sản phẩm đơn lẻ');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped')
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(3, $rows, 'P1 (3 biến thể) + P2 (1) + null (1) = 3 nhóm.');

        $p1 = collect($rows)->firstWhere('external_product_id', 'P1');
        $this->assertSame(3, $p1['variant_count']);
        $this->assertCount(3, $p1['variants']);
        $this->assertSame('Máy cạo râu', $p1['title']);

        $p2 = collect($rows)->firstWhere('external_product_id', 'P2');
        $this->assertSame(1, $p2['variant_count']);

        $this->assertSame(3, $res->json('meta.pagination.total'), 'Tổng SỐ SẢN PHẨM (nhóm), không phải số dòng biến thể (5).');
    }

    public function test_pagination_counts_products_not_rows(): void
    {
        // 3 sản phẩm, mỗi sản phẩm 2 biến thể = 6 dòng; per_page=2 SẢN PHẨM/trang.
        foreach (['P1', 'P2', 'P3'] as $i => $pid) {
            $this->makeListing("S{$i}a", $pid, "SP {$pid}");
            $this->makeListing("S{$i}b", $pid, "SP {$pid}");
        }

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped?per_page=2')
            ->assertOk();

        $this->assertCount(2, $res->json('data'));
        $this->assertSame(3, $res->json('meta.pagination.total'));
        $this->assertSame(2, $res->json('meta.pagination.total_pages'));
    }

    public function test_does_not_leak_other_tenant_listings(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        $otherAccount = (int) ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $otherTenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'shop2', 'shop_name' => 'Shop 2', 'shop_region' => 'VN',
            'status' => 'active', 'access_token' => 'tok',
        ])->getKey();
        $this->makeListing('X1S1', 'X1', 'Khác tenant', tenantId: $otherTenant->getKey(), accountId: $otherAccount);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped')
            ->assertOk();

        $this->assertCount(0, $res->json('data'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run (từ `app/`): `php artisan test --filter=ChannelListingGroupedTest`
Expected: FAIL — route `channel-listings/grouped` chưa tồn tại (404).

- [ ] **Step 3: Tách `applyFilters()` khỏi `index()`**

Trong `app/app/Modules/Products/Http/Controllers/ChannelListingController.php`, tìm toàn bộ method `index()`:

```php
    public function index(Request $request, PromotionService $promotions): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem listing.');
        $q = ChannelListing::query()->withCount('mappings')->with('mappings.sku');
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        // Lọc bỏ SKU đang BẬN (đã có khuyến mãi đang/sắp chạy) — SERVER-SIDE để "Chỉ hiện SKU chọn được" của
        // picker chiến dịch giảm giá đúng qua mọi trang (trước lọc client-side trên từng trang ⇒ sai). Cần 1
        // gian hàng cụ thể; `except` = chiến dịch đang sửa (không tự loại SKU của nó).
        if ($request->boolean('exclude_busy') && $cid) {
            $except = $request->query('except') !== null ? (int) $request->query('except') : null;
            // strval: external_sku_id/product_id là cột chuỗi; tránh phụ thuộc ép kiểu ngầm của DB khi so khớp.
            $busy = array_map('strval', array_keys($promotions->busyPromoPrices((int) $cid, $except)));
            if ($busy !== []) {
                $q->whereNotIn('external_sku_id', $busy)
                    ->where(fn ($x) => $x->whereNotIn('external_product_id', $busy)->orWhereNull('external_product_id'));
            }
        }
        // Lọc nhiều gian hàng (multi-select) — CSV id; bỏ qua nếu rỗng.
        if ($ids = $request->query('channel_account_ids')) {
            $list = array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
            if ($list !== []) {
                $q->whereIn('channel_account_id', $list);
            }
        }
        if ($status = $request->query('sync_status')) {
            $q->where('sync_status', (string) $status);
        }
        if ($request->has('mapped')) {
            $request->boolean('mapped') ? $q->whereHas('mappings') : $q->unmapped();
        }
        if ($term = trim((string) $request->query('q', ''))) {
            SkuSearch::apply($q, $term, ['seller_sku', 'external_sku_id'], ['title']);
        }
        $q->orderByDesc('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ChannelListingResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }
```

Thay bằng (tách phần dựng filter thành `applyFilters()` dùng chung, `index()` giữ NGUYÊN hành vi):

```php
    public function index(Request $request, PromotionService $promotions): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem listing.');
        $q = $this->applyFilters($request, $promotions)->withCount('mappings')->with('mappings.sku');
        $q->orderByDesc('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ChannelListingResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    /**
     * GET /api/v1/channel-listings/grouped — như `index()` nhưng phân trang THEO SẢN PHẨM (SPEC 2026-07-14):
     * gộp các dòng biến thể cùng `channel_account_id`+`external_product_id` (hoặc chính `id` nếu không có
     * external_product_id — coi là nhóm 1 phần tử) thành 1 phần tử, `variant_count`/`variants[]` đính kèm.
     */
    public function grouped(Request $request, PromotionService $promotions): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem listing.');

        $groupKeyExpr = 'COALESCE(external_product_id, CAST(id AS VARCHAR))';
        $groupsQuery = $this->applyFilters($request, $promotions)
            ->selectRaw("channel_account_id, {$groupKeyExpr} as group_key, MAX(id) as sort_id, COUNT(*) as variant_count")
            ->groupBy('channel_account_id', DB::raw($groupKeyExpr))
            ->orderByDesc('sort_id');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $groupsPage = $groupsQuery->paginate($perPage)->appends($request->query());

        $pairs = $groupsPage->getCollection()->map(fn ($g) => [(int) $g->channel_account_id, (string) $g->group_key])->all();

        $rowsByGroup = collect();
        if ($pairs !== []) {
            $rowsByGroup = $this->applyFilters($request, $promotions)
                ->with('mappings.sku')
                ->where(function ($w) use ($pairs, $groupKeyExpr) {
                    foreach ($pairs as [$cid, $key]) {
                        $w->orWhere(fn ($x) => $x->where('channel_account_id', $cid)->whereRaw("{$groupKeyExpr} = ?", [$key]));
                    }
                })
                ->get()
                ->groupBy(fn ($r) => $r->channel_account_id.'|'.($r->external_product_id ?? (string) $r->id));
        }

        $data = $groupsPage->getCollection()->map(function ($g) use ($rowsByGroup) {
            $key = $g->channel_account_id.'|'.$g->group_key;
            $variants = $rowsByGroup->get($key, collect());
            $first = $variants->first();

            return [
                'channel_account_id' => (int) $g->channel_account_id,
                'external_product_id' => $first?->external_product_id,
                'title' => $first?->title,
                'image' => $first?->image,
                'variant_count' => (int) $g->variant_count,
                'variants' => ChannelListingResource::collection($variants->values())->resolve($request),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => ['pagination' => ['page' => $groupsPage->currentPage(), 'per_page' => $groupsPage->perPage(), 'total' => $groupsPage->total(), 'total_pages' => $groupsPage->lastPage()]],
        ]);
    }

    /** Bộ filter dùng chung `index()`/`grouped()` — KHÔNG áp orderBy/pagination/eager-load (caller tự thêm). */
    private function applyFilters(Request $request, PromotionService $promotions): Builder
    {
        $q = ChannelListing::query();
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        if ($request->boolean('exclude_busy') && $cid) {
            $except = $request->query('except') !== null ? (int) $request->query('except') : null;
            $busy = array_map('strval', array_keys($promotions->busyPromoPrices((int) $cid, $except)));
            if ($busy !== []) {
                $q->whereNotIn('external_sku_id', $busy)
                    ->where(fn ($x) => $x->whereNotIn('external_product_id', $busy)->orWhereNull('external_product_id'));
            }
        }
        if ($ids = $request->query('channel_account_ids')) {
            $list = array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
            if ($list !== []) {
                $q->whereIn('channel_account_id', $list);
            }
        }
        if ($status = $request->query('sync_status')) {
            $q->where('sync_status', (string) $status);
        }
        if ($request->has('mapped')) {
            $request->boolean('mapped') ? $q->whereHas('mappings') : $q->unmapped();
        }
        if ($term = trim((string) $request->query('q', ''))) {
            SkuSearch::apply($q, $term, ['seller_sku', 'external_sku_id'], ['title']);
        }

        return $q;
    }
```

- [ ] **Step 4: Thêm import + route**

Trong `app/app/Modules/Products/Http/Controllers/ChannelListingController.php`, sửa khối import (thêm 2 dòng):

Tìm:
```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
```
Thay bằng:
```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
```

Trong `app/routes/api.php`, tìm:
```php
            Route::get('channel-listings', [ChannelListingController::class, 'index'])->name('channel-listings.index');
            Route::post('channel-listings/sync', [ChannelListingController::class, 'sync'])->name('channel-listings.sync');
```
Thay bằng:
```php
            Route::get('channel-listings', [ChannelListingController::class, 'index'])->name('channel-listings.index');
            Route::get('channel-listings/grouped', [ChannelListingController::class, 'grouped'])->name('channel-listings.grouped');       // SPEC 2026-07-14
            Route::post('channel-listings/sync', [ChannelListingController::class, 'sync'])->name('channel-listings.sync');
```

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `php artisan test --filter=ChannelListingGroupedTest`
Expected: PASS — 3/3 test.

- [ ] **Step 6: Chạy `index()` cũ để chắc không hồi quy + quality gate**

Run: `php artisan test --filter=ChannelListingApiTest` (nếu có tên khác, chạy `php artisan test --testsuite=Feature --filter=Products` để phủ toàn module).
Run: `vendor/bin/pint --test app/Modules/Products/Http/Controllers/ChannelListingController.php app/routes/api.php tests/Feature/Products/ChannelListingGroupedTest.php`
Run: `vendor/bin/phpstan analyse app/Modules/Products/Http/Controllers/ChannelListingController.php`

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Products/Http/Controllers/ChannelListingController.php app/routes/api.php app/tests/Feature/Products/ChannelListingGroupedTest.php
git commit -m "feat(products): endpoint channel-listings/grouped phân trang theo sản phẩm"
```

---

### Task 2: Backend — sao chép hàng loạt `POST /channel-listings/bulk-clone-to-shops`

**Files:**
- Modify: `app/app/Modules/Products/Services/MarketplaceCloneService.php`
- Modify: `app/app/Modules/Products/Http/Controllers/ChannelListingController.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Products/ChannelListingBulkCloneTest.php`

**Interfaces:**
- Consumes: `MarketplaceCloneService::cloneToShops(int $channelListingId, array $targetShopIds): array` (đã có, KHÔNG sửa — xem `app/app/Modules/Products/Services/MarketplaceCloneService.php:35`).
- Produces: `MarketplaceCloneService::bulkCloneToShops(array $channelListingIds, array $targetShopIds): array` trả `list<array{channel_listing_id:int, ok:bool, results?:array, error?:string}>`. Route `POST /api/v1/channel-listings/bulk-clone-to-shops` → `{ data: [...] }` cùng shape. Task 4 (FE hook) tiêu thụ đúng shape này.

- [ ] **Step 1: Viết test thất bại**

Tạo `app/tests/Feature/Products/ChannelListingBulkCloneTest.php`:

```php
<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDetailDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingEditDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingStatusDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelListingBulkCloneTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $lazadaSource;

    private int $shopeeSource; // provider KHÔNG đăng ký publisher ⇒ clone sẽ lỗi cho sản phẩm nguồn này

    private int $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $this->lazadaSource = $this->account('lazada', 'src-lzd');
        $this->shopeeSource = $this->account('shopee', 'src-shp');
        $this->target = $this->account('lazada', 'dst-lzd');

        $reg = new PublisherRegistry($this->app);
        $reg->register('lazada', FakeBulkDetailPublisher::class);
        $this->app->instance(FakeBulkDetailPublisher::class, new FakeBulkDetailPublisher);
        $this->app->instance(PublisherRegistry::class, $reg);
    }

    private function account(string $provider, string $shop): int
    {
        return (int) ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => $provider,
            'external_shop_id' => $shop, 'shop_name' => $shop, 'shop_region' => 'VN',
            'status' => 'active', 'access_token' => 'tok',
        ])->getKey();
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_bulk_clone_processes_each_product_independently_isolating_failures(): void
    {
        $okListing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->lazadaSource,
            'external_product_id' => 'OK-1', 'external_sku_id' => 'S1', 'title' => 'SP OK',
            'currency' => 'VND', 'sync_status' => ChannelListing::SYNC_OK,
        ]);
        $failListing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->shopeeSource,
            'external_product_id' => 'FAIL-1', 'external_sku_id' => 'S2', 'title' => 'SP lỗi',
            'currency' => 'VND', 'sync_status' => ChannelListing::SYNC_OK,
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/channel-listings/bulk-clone-to-shops', [
                'channel_listing_ids' => [$okListing->getKey(), $failListing->getKey()],
                'channel_account_ids' => [$this->target],
            ]);

        $res->assertCreated();
        $rows = $res->json('data');
        $this->assertCount(2, $rows);

        $ok = collect($rows)->firstWhere('channel_listing_id', $okListing->getKey());
        $this->assertTrue($ok['ok']);
        $this->assertArrayHasKey('results', $ok);

        $fail = collect($rows)->firstWhere('channel_listing_id', $failListing->getKey());
        $this->assertFalse($fail['ok']);
        $this->assertArrayHasKey('error', $fail);

        $this->assertDatabaseHas('listing_drafts', ['channel_account_id' => $this->target]);
    }
}

/** Fake: chỉ getListingDetail có dữ liệu (đủ để tạo draft); còn lại không dùng. */
final class FakeBulkDetailPublisher implements ProductPublishingConnector
{
    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        return new ListingDetailDTO(
            externalProductId: $externalProductId,
            title: 'SP OK',
            description: 'Mô tả',
            images: ['https://cdn/a.jpg'],
            skus: [['external_sku_id' => 'S1', 'seller_sku' => 'S1', 'price' => 100000]],
            categoryId: '3',
            brandId: '40516',
            attributes: [],
        );
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        return [];
    }

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        throw new \RuntimeException('not used');
    }

    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        throw new \RuntimeException('not used');
    }

    public function getBrands(AuthContext $auth, string $categoryId, ?string $keyword = null, int $limit = 50): array
    {
        throw new \RuntimeException('not used');
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        return new MediaRefDTO($imageUrlOrPath, 'cdn_url');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `php artisan test --filter=ChannelListingBulkCloneTest`
Expected: FAIL — route chưa tồn tại (404).

- [ ] **Step 3: Thêm `MarketplaceCloneService::bulkCloneToShops()`**

Trong `app/app/Modules/Products/Services/MarketplaceCloneService.php`, thêm method mới ngay sau `cloneToShops()` (sau dòng `}` đóng method đó):

```php
    /**
     * Sao chép NHIỀU sản phẩm cùng lúc (SPEC 2026-07-14) — mỗi `channel_listing_id` đại diện 1 sản phẩm,
     * xử lý ĐỘC LẬP: lỗi 1 sản phẩm (token hết hạn, sản phẩm bị gỡ...) không chặn các sản phẩm còn lại.
     *
     * @param  int[]  $channelListingIds
     * @param  int[]  $targetShopIds
     * @return list<array{channel_listing_id:int, ok:bool, results?:array, error?:string}>
     */
    public function bulkCloneToShops(array $channelListingIds, array $targetShopIds): array
    {
        $out = [];
        foreach (array_unique(array_map('intval', $channelListingIds)) as $id) {
            try {
                $out[] = ['channel_listing_id' => $id, 'ok' => true, 'results' => $this->cloneToShops($id, $targetShopIds)];
            } catch (\Throwable $e) {
                $out[] = ['channel_listing_id' => $id, 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        return $out;
    }
```

- [ ] **Step 4: Thêm controller action + route**

Trong `app/app/Modules/Products/Http/Controllers/ChannelListingController.php`, tìm method `cloneToShops()` hiện có (kết thúc bằng `return response()->json(['data' => $svc->cloneToShops($id, $data['channel_account_ids'])], 201);` rồi `}`), thêm method mới NGAY SAU nó:

```php
    /**
     * POST /api/v1/channel-listings/bulk-clone-to-shops — sao chép NHIỀU sản phẩm "đã có trên sàn" sang
     * nhiều shop cùng lúc (SPEC 2026-07-14). Mỗi channel_listing_id đại diện 1 sản phẩm (giống clone đơn).
     */
    public function bulkCloneToShops(Request $request, MarketplaceCloneService $svc): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sao chép sản phẩm.');
        $data = $request->validate([
            'channel_listing_ids' => ['required', 'array', 'min:1', 'max:50'],
            'channel_listing_ids.*' => ['integer'],
            'channel_account_ids' => ['required', 'array', 'min:1', 'max:50'],
            'channel_account_ids.*' => ['integer'],
        ]);

        return response()->json(['data' => $svc->bulkCloneToShops($data['channel_listing_ids'], $data['channel_account_ids'])], 201);
    }
```

Trong `app/routes/api.php`, tìm:
```php
            Route::post('channel-listings/{id}/clone-to-shops', [ChannelListingController::class, 'cloneToShops'])->whereNumber('id')->name('channel-listings.clone-to-shops');
```
Thay bằng:
```php
            Route::post('channel-listings/{id}/clone-to-shops', [ChannelListingController::class, 'cloneToShops'])->whereNumber('id')->name('channel-listings.clone-to-shops');
            Route::post('channel-listings/bulk-clone-to-shops', [ChannelListingController::class, 'bulkCloneToShops'])->name('channel-listings.bulk-clone-to-shops'); // SPEC 2026-07-14
```

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `php artisan test --filter=ChannelListingBulkCloneTest`
Expected: PASS.

- [ ] **Step 6: Chạy test clone đơn cũ để chắc không hồi quy + quality gate**

Run: `php artisan test --filter=MarketplaceCloneTest`
Run: `vendor/bin/pint --test app/Modules/Products/Services/MarketplaceCloneService.php app/Modules/Products/Http/Controllers/ChannelListingController.php app/routes/api.php tests/Feature/Products/ChannelListingBulkCloneTest.php`
Run: `vendor/bin/phpstan analyse app/Modules/Products/Services/MarketplaceCloneService.php app/Modules/Products/Http/Controllers/ChannelListingController.php`

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Products/Services/MarketplaceCloneService.php app/app/Modules/Products/Http/Controllers/ChannelListingController.php app/routes/api.php app/tests/Feature/Products/ChannelListingBulkCloneTest.php
git commit -m "feat(products): sao chép hàng loạt nhiều sản phẩm sang gian hàng khác"
```

---

### Task 3: Frontend — hook `useGroupedChannelListings`

**Files:**
- Modify: `app/resources/js/lib/inventory.tsx`

**Interfaces:**
- Consumes: `GET /channel-listings/grouped` (Task 1) → `{ data: GroupedChannelListing[], meta:{pagination} }`; `ChannelListing` (type đã có, cùng file).
- Produces: `export interface GroupedChannelListing { channel_account_id: number; external_product_id: string | null; title: string | null; image: string | null; variant_count: number; variants: ChannelListing[] }`; `export function useGroupedChannelListings(filters): UseQueryResult<Paginated<GroupedChannelListing>>`. Task 5 (trang) tiêu thụ trực tiếp hook + type này.

- [ ] **Step 1: Thêm type + hook**

Trong `app/resources/js/lib/inventory.tsx`, thêm ngay sau hàm `useChannelListings` (sau dòng `}` đóng hàm đó):

```tsx
export interface GroupedChannelListing {
    channel_account_id: number;
    external_product_id: string | null;
    title: string | null;
    image: string | null;
    variant_count: number;
    variants: ChannelListing[];
}

/** Như useChannelListings nhưng phân trang THEO SẢN PHẨM (gộp biến thể) — SPEC 2026-07-14. */
export function useGroupedChannelListings(filters: { channel_account_id?: number; channel_account_ids?: string; mapped?: 0 | 1; sync_status?: string; q?: string; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['channel-listings-grouped', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<GroupedChannelListing>>('/channel-listings/grouped', { params });
            return data;
        },
    });
}
```

- [ ] **Step 2: Typecheck**

Run (từ `app/`): `npm run typecheck`
Expected: không lỗi mới trong `inventory.tsx` (chưa có nơi nào import hook mới nên không ảnh hưởng file khác ở bước này).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/inventory.tsx
git commit -m "feat(products): hook useGroupedChannelListings tra cứu sản phẩm gộp biến thể"
```

---

### Task 4: Frontend — hook `useBulkCloneChannelListingsToShops`

**Files:**
- Modify: `app/resources/js/features/products/api.ts`
- Modify: `app/resources/js/features/products/hooks.ts`

**Interfaces:**
- Consumes: `POST /channel-listings/bulk-clone-to-shops` (Task 2) → `{ data: BulkCloneResult[] }`; `CloneToShopsResult` (type đã có, `api.ts`).
- Produces: `export interface BulkCloneResult { channel_listing_id: number; ok: boolean; results?: CloneToShopsResult[]; error?: string }`; `export async function bulkCloneChannelListingsToShops(client, channelListingIds, channelAccountIds): Promise<BulkCloneResult[]>`; `export function useBulkCloneChannelListingsToShops()` (mutation) → `Promise<BulkCloneResult[]>`. Task 5 tiêu thụ trực tiếp hook.

- [ ] **Step 1: Thêm type + hàm API**

Trong `app/resources/js/features/products/api.ts`, tìm hàm `cloneChannelListingToShops` hiện có:

```ts
export async function cloneChannelListingToShops(
    client: AxiosInstance,
    channelListingId: number,
    channelAccountIds: number[],
): Promise<CloneToShopsResult[]> {
    const { data } = await client.post<{ data: CloneToShopsResult[] }>(`/channel-listings/${channelListingId}/clone-to-shops`, {
        channel_account_ids: channelAccountIds,
    });
    return data.data;
```

Ngay SAU dấu `}` đóng hàm này (và dòng `}` tiếp theo nếu có), thêm:

```ts
export interface BulkCloneResult { channel_listing_id: number; ok: boolean; results?: CloneToShopsResult[]; error?: string }

export async function bulkCloneChannelListingsToShops(
    client: AxiosInstance,
    channelListingIds: number[],
    channelAccountIds: number[],
): Promise<BulkCloneResult[]> {
    const { data } = await client.post<{ data: BulkCloneResult[] }>('/channel-listings/bulk-clone-to-shops', {
        channel_listing_ids: channelListingIds,
        channel_account_ids: channelAccountIds,
    });
    return data.data;
}
```

- [ ] **Step 2: Thêm hook**

Trong `app/resources/js/features/products/hooks.ts`, sửa import (thêm `bulkCloneChannelListingsToShops` + `type BulkCloneResult` vào danh sách import từ `./api`, theo đúng thứ tự alphabet hiện có — chèn trước `cloneChannelListingToShops`):

Tìm:
```ts
    bulkPush,
    cloneChannelListingToShops,
```
Thay bằng:
```ts
    bulkCloneChannelListingsToShops,
    bulkPush,
    cloneChannelListingToShops,
    type BulkCloneResult,
```

Thêm hook mới ngay sau `useCloneChannelListingToShops()` (sau dòng `}` đóng hàm đó):

```ts
export function useBulkCloneChannelListingsToShops() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (vars: { channelListingIds: number[]; channelAccountIds: number[] }): Promise<BulkCloneResult[]> =>
            bulkCloneChannelListingsToShops(client!, vars.channelListingIds, vars.channelAccountIds),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}
```

- [ ] **Step 3: Typecheck**

Run: `npm run typecheck`
Expected: sạch (chưa có nơi nào dùng hook mới).

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/features/products/api.ts app/resources/js/features/products/hooks.ts
git commit -m "feat(products): hook useBulkCloneChannelListingsToShops sao chép hàng loạt"
```

---

### Task 5: Frontend — gắn bảng cha-con + checkbox + sao chép hàng loạt vào `OnChannelPage.tsx`

**Files:**
- Modify: `app/resources/js/pages/marketplace/OnChannelPage.tsx`

**Interfaces:**
- Consumes: `useGroupedChannelListings`, `GroupedChannelListing` (Task 3, từ `@/lib/inventory`); `useBulkCloneChannelListingsToShops`, `BulkCloneResult` (Task 4, từ `@/features/products/hooks`).
- Produces: không (chỉ wiring UI, file cuối cùng của plan).

- [ ] **Step 1: Thay import + query listing**

Tìm dòng import:
```tsx
import { type ChannelListing, useChannelListings, useSyncChannelListings } from '@/lib/inventory';
import { useCloneChannelListingToShops } from '@/features/products/hooks';
```
Thay bằng:
```tsx
import { type ChannelListing, type GroupedChannelListing, useGroupedChannelListings, useSyncChannelListings } from '@/lib/inventory';
import { useBulkCloneChannelListingsToShops, useCloneChannelListingToShops } from '@/features/products/hooks';
```

Tìm:
```tsx
    const { data, isFetching, refetch } = useChannelListings({
        page,
        per_page: 20,
        q: q || undefined,
        channel_account_ids: shopIds.length ? shopIds.join(',') : undefined,
    });
```
Thay bằng:
```tsx
    const { data, isFetching, refetch } = useGroupedChannelListings({
        page,
        per_page: 20,
        q: q || undefined,
        channel_account_ids: shopIds.length ? shopIds.join(',') : undefined,
    });
```

- [ ] **Step 2: Thêm state chọn hàng loạt + modal sao chép hàng loạt**

Tìm:
```tsx
    const [cloneFor, setCloneFor] = useState<ChannelListing | null>(null);
    const [cloneShopIds, setCloneShopIds] = useState<number[]>([]);
```
Thay bằng:
```tsx
    const [cloneFor, setCloneFor] = useState<ChannelListing | null>(null);
    const [cloneShopIds, setCloneShopIds] = useState<number[]>([]);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [bulkCloneOpen, setBulkCloneOpen] = useState(false);
    const [bulkCloneShopIds, setBulkCloneShopIds] = useState<number[]>([]);
```

Tìm:
```tsx
    const clone = useCloneChannelListingToShops();
```
Thay bằng:
```tsx
    const clone = useCloneChannelListingToShops();
    const bulkClone = useBulkCloneChannelListingsToShops();
```

Tìm (ngay sau hàm `handleClone`, trước `const shopName = ...`):
```tsx
    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;
```
Thay bằng:
```tsx
    const handleBulkClone = () => {
        if (selectedIds.length === 0 || bulkCloneShopIds.length === 0) return;
        bulkClone.mutate(
            { channelListingIds: selectedIds, channelAccountIds: bulkCloneShopIds },
            {
                onSuccess: (results) => {
                    setBulkCloneOpen(false);
                    setSelectedIds([]);
                    const okCount = results.filter((r) => r.ok).length;
                    const failed = results.length - okCount;
                    message.success(failed > 0
                        ? `Đã sao chép ${okCount}/${results.length} sản phẩm (${failed} lỗi).`
                        : `Đã sao chép ${okCount} sản phẩm sang ${bulkCloneShopIds.length} gian hàng.`);
                    navigate('/marketplace/to-push');
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;
```

- [ ] **Step 3: Tách columns thành cột cha (sản phẩm) + cột con (biến thể)**

Tìm TOÀN BỘ khối `const columns: ColumnsType<ChannelListing> = [ ... ];` (từ dòng `const columns:` tới dòng `];` đóng — nội dung đầy đủ đã có trong file, gồm 6 cột: Sản phẩm/Gian hàng/Giá gốc/Giá sau giảm/Tồn sàn/Trạng thái/actions).

Thay TOÀN BỘ khối đó bằng 2 khối cột mới (cột cha dùng `GroupedChannelListing`, cột con dùng `ChannelListing`):

```tsx
    const columns: ColumnsType<GroupedChannelListing> = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, r) => (
                <Space>
                    <Avatar shape="square" size={44} src={r.image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf', flex: 'none' }} />
                    <div style={{ minWidth: 0 }}>
                        <Typography.Text strong ellipsis={{ tooltip: r.title ?? undefined }} style={{ display: 'block', maxWidth: 340 }}>
                            {r.title ?? '—'}
                        </Typography.Text>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                            {r.variant_count} biến thể
                        </Typography.Text>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Gian hàng',
            key: 'shop',
            width: 220,
            render: (_, r) => {
                const provider = shopProvider(r.channel_account_id);
                return (
                    <Space size={4} wrap>
                        <ChannelBadge provider={provider} />
                        <Tag style={{ display: 'inline-flex', alignItems: 'center', gap: 4, paddingInline: 6 }}>
                            <ChannelLogo provider={provider} size={12} />
                            <span>{shopName(r.channel_account_id)}</span>
                        </Tag>
                    </Space>
                );
            },
        },
        {
            title: '',
            key: 'actions',
            width: 240,
            render: (_, r) => {
                const rep = r.variants[0];
                if (!rep) return null;
                return (
                    <Space>
                        <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/marketplace/on-channel/${rep.id}/edit`, { state: { listing: rep } })}>
                            Sửa trên sàn
                        </Button>
                        <Button size="small" icon={<CopyOutlined />} onClick={() => openClone(rep)}>
                            Sao chép sàn
                        </Button>
                    </Space>
                );
            },
        },
    ];

    const variantColumns: ColumnsType<ChannelListing> = [
        {
            title: 'Biến thể',
            key: 'variant',
            render: (_, r) => (
                <Typography.Text type="secondary">
                    {[r.variation, r.seller_sku ? `SKU: ${r.seller_sku}` : null].filter(Boolean).join(' · ') || '—'}
                </Typography.Text>
            ),
        },
        {
            title: 'Giá gốc',
            dataIndex: 'original_price',
            width: 120,
            align: 'right',
            render: (v: number | null, r) => {
                const base = v ?? r.price;
                return base == null ? <Typography.Text type="secondary">—</Typography.Text> : <MoneyText value={base} currency={r.currency} />;
            },
        },
        {
            title: 'Giá sau giảm',
            key: 'sale_price',
            width: 150,
            align: 'right',
            render: (_, r) => {
                const sale = r.special_price ?? r.price;
                if (sale == null) return <Typography.Text type="secondary">—</Typography.Text>;
                const base = r.original_price ?? null;
                const off = base != null && base > sale ? Math.round(((base - sale) / base) * 100) : 0;
                return (
                    <Space size={4}>
                        <MoneyText value={sale} currency={r.currency} strong />
                        {off > 0 && (
                            <Tooltip title={`Giảm ${off}% so với giá gốc`}>
                                <QuestionCircleOutlined style={{ color: '#bfbfbf', cursor: 'help' }} />
                            </Tooltip>
                        )}
                    </Space>
                );
            },
        },
        {
            title: 'Tồn sàn',
            dataIndex: 'channel_stock',
            width: 90,
            align: 'right',
            render: (v: number | null) => v ?? <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'sync_status',
            width: 130,
            render: (s: string, r) => {
                const meta = SYNC_TAG[s] ?? SYNC_TAG.pending;
                return (
                    <Space size={4}>
                        <Tag color={meta.color}>{meta.label}</Tag>
                        {!r.is_active && <Tag>Ẩn</Tag>}
                    </Space>
                );
            },
        },
    ];
```

- [ ] **Step 4: Thêm nút sao chép hàng loạt phía trên bảng**

Tìm:
```tsx
                </Space>

                <Table<ChannelListing>
                    rowKey="id"
                    size="middle"
                    loading={isFetching}
                    dataSource={data?.data ?? []}
                    columns={columns}
                    locale={{ emptyText: <Empty description="Chưa có sản phẩm nào. Bấm “Đồng bộ sản phẩm” để kéo sản phẩm của gian hàng về." /> }}
                    pagination={{
                        current: page,
                        pageSize: 20,
                        total: data?.meta?.pagination?.total ?? 0,
                        showSizeChanger: false,
                        onChange: setPage,
                    }}
                />
            </Card>
```

Thay bằng:

```tsx
                </Space>

                {selectedIds.length > 0 && (
                    <Button style={{ marginBottom: 12 }} icon={<CopyOutlined />} onClick={() => { setBulkCloneShopIds([]); setBulkCloneOpen(true); }}>
                        Sao chép sang gian hàng khác ({selectedIds.length} sản phẩm)
                    </Button>
                )}

                <Table<GroupedChannelListing>
                    rowKey={(r) => r.variants[0]?.id ?? `${r.channel_account_id}-${r.external_product_id ?? 'x'}`}
                    size="middle"
                    loading={isFetching}
                    dataSource={data?.data ?? []}
                    columns={columns}
                    rowSelection={{ selectedRowKeys: selectedIds, onChange: (keys) => setSelectedIds(keys as number[]) }}
                    expandable={{
                        expandedRowRender: (r) => (
                            <Table<ChannelListing> rowKey="id" size="small" pagination={false} showHeader columns={variantColumns} dataSource={r.variants} />
                        ),
                    }}
                    locale={{ emptyText: <Empty description="Chưa có sản phẩm nào. Bấm “Đồng bộ sản phẩm” để kéo sản phẩm của gian hàng về." /> }}
                    pagination={{
                        current: page,
                        pageSize: 20,
                        total: data?.meta?.pagination?.total ?? 0,
                        showSizeChanger: false,
                        onChange: setPage,
                    }}
                />
            </Card>
```

- [ ] **Step 5: Thêm modal sao chép hàng loạt**

Tìm dòng đóng file (ngay trước `</div>` cuối cùng, sau `</Modal>` của modal sao chép đơn hiện có):

```tsx
                )}
            </Modal>
        </div>
    );
}
```

Thay bằng:

```tsx
                )}
            </Modal>

            <Modal
                title="Sao chép sang gian hàng khác (hàng loạt)"
                open={bulkCloneOpen}
                onCancel={() => setBulkCloneOpen(false)}
                okText={bulkCloneShopIds.length > 1 ? `Sao chép ${bulkCloneShopIds.length} sàn` : 'Sao chép'}
                okButtonProps={{ disabled: bulkCloneShopIds.length === 0, loading: bulkClone.isPending }}
                onOk={handleBulkClone}
            >
                <Typography.Paragraph type="secondary" style={{ marginBottom: 8 }}>
                    Áp dụng cho {selectedIds.length} sản phẩm đã chọn. Cùng nền tảng sẽ đủ dữ liệu để đẩy luôn (sẵn
                    sàng); khác nền tảng tạo bản nháp cần soạn lại ngành hàng/thuộc tính. Tất cả đưa vào “Chờ đẩy lên sàn”.
                </Typography.Paragraph>
                {accounts.length === 0 ? (
                    <Empty description="Không có gian hàng đích nào." />
                ) : (
                    <Checkbox.Group value={bulkCloneShopIds} onChange={(v) => setBulkCloneShopIds(v as number[])}>
                        <Space direction="vertical">
                            {accounts.map((a) => (
                                <Checkbox key={a.id} value={a.id}>
                                    <Space size={6}>
                                        <ChannelLogo provider={a.provider} size={16} />
                                        <span>{a.name}</span>
                                        <Tag>{a.provider}</Tag>
                                    </Space>
                                </Checkbox>
                            ))}
                        </Space>
                    </Checkbox.Group>
                )}
            </Modal>
        </div>
    );
}
```

- [ ] **Step 6: Typecheck + lint + build**

Run: `npm run typecheck && npm run lint && npm run build`
Expected: sạch. Nếu lint báo `ChannelListing` không dùng — SAI, `ChannelListing` vẫn dùng làm type cho `variantColumns`/`cloneFor`/tham số `openClone`; nếu thật sự báo lỗi, kiểm tra lại import ở Step 1 giữ đủ cả `ChannelListing` lẫn `GroupedChannelListing`.

- [ ] **Step 7: Verify thủ công qua trình duyệt**

Mở `/marketplace/on-channel`: (1) sản phẩm nhiều biến thể hiện 1 dòng, có nút mở rộng hiện đủ biến thể con; (2) tick nhiều sản phẩm → nút "Sao chép sang gian hàng khác (N sản phẩm)" hiện; (3) bấm → modal mở, chọn gian hàng đích, sao chép thành công, điều hướng sang "Chờ đẩy lên sàn"; (4) nút "Sửa trên sàn"/"Sao chép sàn" ở dòng cha hoạt động đúng như trước khi dời vị trí.

- [ ] **Step 8: Commit**

```bash
git add app/resources/js/pages/marketplace/OnChannelPage.tsx
git commit -m "feat(products): gộp bảng cha-con theo sản phẩm + checkbox sao chép hàng loạt"
```

---

## Self-Review

**Spec coverage:**
- Endpoint gộp phân trang theo sản phẩm → Task 1. ✅
- Endpoint sao chép hàng loạt, lỗi từng sản phẩm không hỏng cả lô → Task 2. ✅
- Không đổi endpoint `/channel-listings` phẳng, không đổi `MarketplaceCloneService::cloneToShops()` bên trong → xác nhận trong toàn bộ Task 1/2 (chỉ thêm method mới, không sửa method cũ). ✅
- Bảng cha-con, dời nút Sửa/Sao chép lên dòng cha, checkbox cấp sản phẩm, nút sao chép hàng loạt → Task 5. ✅
- Testing (Unit/Feature backend qua RefreshDatabase + fake PublisherRegistry, FE typecheck/lint/build/manual) → mỗi task có bước riêng. ✅

**Placeholder scan:** không có TBD/TODO; mọi bước có code đầy đủ kèm chuỗi tìm/thay chính xác.

**Type consistency:** `GroupedChannelListing` (Task 3) khớp field `channel_account_id/external_product_id/title/image/variant_count/variants` dùng trong `columns`/`rowKey`/`expandedRowRender` (Task 5) và trong response `ChannelListingController::grouped()` (Task 1, cùng field names). `BulkCloneResult` (Task 4) khớp field `channel_listing_id/ok/results/error` dùng trong `handleBulkClone` (Task 5) và trong `MarketplaceCloneService::bulkCloneToShops()` (Task 2).
