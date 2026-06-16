# Visual Training & Tìm sản phẩm bằng ảnh — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép khách gửi ảnh → AI nhận ra đúng "item AI training" (sản phẩm/logo/bao bì…) bằng visual search (CLIP recall + vision re-rank), và cho seller một công cụ "Tìm bằng ảnh"; hoàn toàn tách biệt khỏi luồng AI reply tối ưu.

**Architecture:** 2 trục Integration mới vendor-agnostic (`Integrations/Vector` → Qdrant, `Integrations/Embedding/Image` → CLIP sidecar) + 1 capability AI additive (`vision.analyze`) + module domain `VisualSearch` (catalog nhập tay, index ảnh, match tri-state). Messaging tiêu thụ qua `Contracts\VisualItemSearch`; lỗi/tắt/không-match ⇒ luồng cũ bất biến.

**Tech Stack:** Laravel 11, PHP 8.2, Qdrant (HTTP, no SDK), CLIP sidecar (FastAPI + sentence-transformers), Horizon (queue `visual-index`), PHPUnit, Larastan L5, Pint.

**Spec:** `docs/specs/2026-06-16-visual-training-image-search-design.md` (đọc trước khi bắt đầu).

**Quy ước chung mọi task:**
- Mọi lệnh chạy từ `app/` (KHÔNG phải repo root): `cd app` trước `php artisan`, `vendor/bin/*`.
- Namespace `CMBcoreSeller\` → `app/app/`. Vd `CMBcoreSeller\Integrations\Vector\...` ⇒ `app/app/Integrations/Vector/...`.
- Sau mỗi nhóm code: `vendor/bin/pint` (auto-fix) rồi `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test --filter=<Test>`.
- Commit conventional, tiếng Việt phần mô tả; kết thúc message bằng dòng `Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>`.

---

## Phase 0 — Recon (đọc, KHÔNG sửa)

### Task 0.1: Xác nhận điểm nối hiện có

- [ ] **Step 1: Đọc & ghi chú signature**

Đọc và ghi lại chữ ký chính xác (chỉ đọc):
- `app/app/Modules/Billing/Contracts/AiCreditMeter.php` — xác nhận có `canUse(int $tenantId, int $n): bool`, `record(int $tenantId, int $n): void`, `summary(int $tenantId): array`.
- `app/app/Integrations/Ai/Contracts/AiAssistantConnector.php` — danh sách method để thêm `analyzeImages` đồng bộ 4 connector (`Claude/OpenAi/CustomHttp/Manual`).
- `app/app/Integrations/Ai/Claude/ClaudeConnector.php` + `OpenAi/OpenAiConnector.php` — cách build image block (`imageSource()` / `image_url`), `visionEnabled()`, đọc credentials (`AiProviderCredentials::resolve($this->code())`), `config('ai.http.*')`.
- `app/app/Integrations/IntegrationsServiceProvider.php` (≈ dòng 250-283) — pattern `singleton(Registry::class, …)` để bắt chước cho 2 registry mới.
- `app/app/Integrations/Ai/Exceptions/UnsupportedOperation.php` — chữ ký `UnsupportedOperation::for($code, $op)`.
- Tìm cách kiểm tra plan-feature: `grep -rn "messaging_ai" app/app/Modules/Messaging/Listeners/AiAutoModeOnInbound.php` và xem `hasAiFeature()` gọi gì (helper/contract nào) → tái dùng y hệt cho key `messaging_visual_search`.

Không có test ở task này. Mục tiêu: chắc chắn các chữ ký dưới đây khớp thực tế trước khi code.

- [ ] **Step 2: Commit (không có thay đổi) — bỏ qua**

---

## Phase 1 — Integration trục Vector (Qdrant)

### Task 1.1: Contract `VectorStore`

**Files:**
- Create: `app/app/Integrations/Vector/Contracts/VectorStore.php`

- [ ] **Step 1: Viết interface**

```php
<?php

namespace CMBcoreSeller\Integrations\Vector\Contracts;

/**
 * Kho vector vendor-agnostic (Qdrant hôm nay; có thể thêm Milvus/PGVector sau =
 * 1 connector mới). Triết lý: KHÔNG ném — lỗi/chưa cấu hình ⇒ log + trả false/[]
 * để caller (VisualSearch) suy biến (not_found) thay vì 500.
 */
interface VectorStore
{
    public function enabled(): bool;

    public function ensureCollection(string $collection, int $dim, string $distance = 'Cosine'): bool;

    public function recreateCollection(string $collection, int $dim, string $distance = 'Cosine'): bool;

    /**
     * @param  list<array{id:string, vector:list<float>, payload:array<string,mixed>}>  $points
     */
    public function upsert(string $collection, array $points): bool;

    /**
     * @param  list<float>  $vector
     * @param  array<string,mixed>  $filter  Map khoá=giá trị (equality). VD ['tenant_id'=>5].
     * @return list<array{id:string, score:float, payload:array<string,mixed>}>
     */
    public function search(string $collection, array $vector, int $topK, array $filter = []): array;

    /** @param list<string> $ids */
    public function deleteIds(string $collection, array $ids): bool;
}
```

- [ ] **Step 2: Commit**

```bash
git add app/app/Integrations/Vector/Contracts/VectorStore.php
git commit -m "feat(vector): contract VectorStore (vendor-agnostic)"
```

### Task 1.2: `QdrantStore` + test

**Files:**
- Create: `app/app/Integrations/Vector/Qdrant/QdrantStore.php`
- Test: `app/tests/Unit/Integrations/Vector/QdrantStoreTest.php`

- [ ] **Step 1: Viết test (HTTP fake)**

```php
<?php

namespace Tests\Unit\Integrations\Vector;

use CMBcoreSeller\Integrations\Vector\Qdrant\QdrantStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QdrantStoreTest extends TestCase
{
    public function test_disabled_when_no_url(): void
    {
        $store = new QdrantStore(['url' => '', 'api_key' => '', 'timeout' => 5]);
        $this->assertFalse($store->enabled());
        $this->assertSame([], $store->search('c', [0.1, 0.2], 5));
    }

    public function test_search_sends_tenant_filter_and_maps_result(): void
    {
        Http::fake([
            '*/collections/visual_training__m/points/search' => Http::response([
                'result' => [
                    ['id' => 'p1', 'score' => 0.91, 'payload' => ['item_id' => 7]],
                ],
            ], 200),
        ]);

        $store = new QdrantStore(['url' => 'http://qdrant:6333', 'api_key' => '', 'timeout' => 5]);
        $hits = $store->search('visual_training__m', [0.1, 0.2], 5, ['tenant_id' => 3]);

        $this->assertCount(1, $hits);
        $this->assertSame('p1', $hits[0]['id']);
        $this->assertSame(7, $hits[0]['payload']['item_id']);

        Http::assertSent(function ($req) {
            $body = $req->data();
            return $req->url() === 'http://qdrant:6333/collections/visual_training__m/points/search'
                && $body['filter']['must'][0]['key'] === 'tenant_id'
                && $body['filter']['must'][0]['match']['value'] === 3
                && $body['limit'] === 5;
        });
    }

    public function test_search_swallows_http_error(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $store = new QdrantStore(['url' => 'http://qdrant:6333', 'api_key' => '', 'timeout' => 5]);
        $this->assertSame([], $store->search('c', [0.1], 5, ['tenant_id' => 1]));
    }
}
```

- [ ] **Step 2: Chạy test → FAIL**

Run: `cd app && php artisan test --filter=QdrantStoreTest`
Expected: FAIL ("Class QdrantStore not found").

- [ ] **Step 3: Viết `QdrantStore`**

```php
<?php

namespace CMBcoreSeller\Integrations\Vector\Qdrant;

use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter HTTP tối giản cho Qdrant (không SDK). Tách RIÊNG khỏi
 * Support\QdrantClient (help-bot) để không đụng luồng tối ưu của Help Assistant
 * (ADR — chấp nhận trùng ~80 dòng đổi lấy tách biệt). Hỗ trợ nhiều collection +
 * filter equality (tenant isolation).
 */
class QdrantStore implements VectorStore
{
    private string $url;

    private string $apiKey;

    private int $timeout;

    /** @param array{url?:string,api_key?:string,timeout?:int}|null $config */
    public function __construct(?array $config = null)
    {
        $config ??= (array) config('integrations.vector.qdrant', []);
        $this->url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->apiKey = (string) ($config['api_key'] ?? '');
        $this->timeout = (int) ($config['timeout'] ?? 10);
    }

    public function enabled(): bool
    {
        return $this->url !== '';
    }

    public function ensureCollection(string $collection, int $dim, string $distance = 'Cosine'): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        try {
            if ($this->http()->get($this->ep("/collections/{$collection}"))->successful()) {
                return true;
            }
        } catch (\Throwable) {
            // fallthrough to create
        }

        return $this->create($collection, $dim, $distance);
    }

    public function recreateCollection(string $collection, int $dim, string $distance = 'Cosine'): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        try {
            $this->http()->delete($this->ep("/collections/{$collection}"));
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.delete_failed', ['error' => $e->getMessage()]);
        }

        return $this->create($collection, $dim, $distance);
    }

    public function upsert(string $collection, array $points): bool
    {
        if (! $this->enabled() || $points === []) {
            return false;
        }
        try {
            return $this->http()
                ->put($this->ep("/collections/{$collection}/points?wait=true"), ['points' => $points])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.upsert_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function search(string $collection, array $vector, int $topK, array $filter = []): array
    {
        if (! $this->enabled() || $vector === []) {
            return [];
        }
        $body = ['vector' => array_values($vector), 'limit' => $topK, 'with_payload' => true];
        if ($filter !== []) {
            $body['filter'] = ['must' => array_map(
                fn ($k, $v) => ['key' => $k, 'match' => ['value' => $v]],
                array_keys($filter),
                array_values($filter),
            )];
        }
        try {
            $res = $this->http()->post($this->ep("/collections/{$collection}/points/search"), $body);
            if (! $res->successful()) {
                return [];
            }

            return collect((array) $res->json('result', []))
                ->map(fn ($r) => [
                    'id' => (string) ($r['id'] ?? ''),
                    'score' => (float) ($r['score'] ?? 0),
                    'payload' => (array) ($r['payload'] ?? []),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.search_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function deleteIds(string $collection, array $ids): bool
    {
        if (! $this->enabled() || $ids === []) {
            return false;
        }
        try {
            return $this->http()
                ->post($this->ep("/collections/{$collection}/points/delete?wait=true"), ['points' => array_values($ids)])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.delete_ids_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function create(string $collection, int $dim, string $distance): bool
    {
        try {
            return $this->http()
                ->put($this->ep("/collections/{$collection}"), ['vectors' => ['size' => $dim, 'distance' => $distance]])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.create_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function http(): PendingRequest
    {
        $req = Http::timeout($this->timeout)->acceptJson();
        if ($this->apiKey !== '') {
            $req = $req->withHeaders(['api-key' => $this->apiKey]);
        }

        return $req;
    }

    private function ep(string $path): string
    {
        return $this->url.$path;
    }
}
```

- [ ] **Step 4: Chạy test → PASS**

Run: `cd app && php artisan test --filter=QdrantStoreTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Pint + commit**

```bash
cd app && vendor/bin/pint app/Integrations/Vector tests/Unit/Integrations/Vector
git add app/app/Integrations/Vector tests/Unit/Integrations/Vector
git commit -m "feat(vector): QdrantStore HTTP adapter + test"
```

### Task 1.3: `VectorStoreRegistry`

**Files:**
- Create: `app/app/Integrations/Vector/VectorStoreRegistry.php`

- [ ] **Step 1: Viết registry**

```php
<?php

namespace CMBcoreSeller\Integrations\Vector;

use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/** Registry trục Vector — đổi driver = thêm 1 register + 1 dòng config. */
class VectorStoreRegistry
{
    /** @var array<string, class-string<VectorStore>> */
    private array $drivers = [];

    public function __construct(private Container $container) {}

    /** @param class-string<VectorStore> $class */
    public function register(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function for(string $code): VectorStore
    {
        if (! isset($this->drivers[$code])) {
            throw new RuntimeException("Vector driver [{$code}] chưa đăng ký.");
        }

        return $this->container->make($this->drivers[$code]);
    }

    public function default(): VectorStore
    {
        return $this->for((string) config('integrations.vector.driver', 'qdrant'));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/app/Integrations/Vector/VectorStoreRegistry.php
git commit -m "feat(vector): VectorStoreRegistry"
```

---

## Phase 2 — Integration trục Image Embedding (CLIP)

### Task 2.1: DTO `ImageVectorDTO` + Contract `ImageEmbedder`

**Files:**
- Create: `app/app/Integrations/Embedding/Image/DTO/ImageVectorDTO.php`
- Create: `app/app/Integrations/Embedding/Image/Contracts/ImageEmbedder.php`

- [ ] **Step 1: DTO**

```php
<?php

namespace CMBcoreSeller\Integrations\Embedding\Image\DTO;

final readonly class ImageVectorDTO
{
    /** @param list<float> $vector */
    public function __construct(
        public array $vector,
        public int $dim,
        public string $model,
    ) {}
}
```

- [ ] **Step 2: Contract**

```php
<?php

namespace CMBcoreSeller\Integrations\Embedding\Image\Contracts;

use CMBcoreSeller\Integrations\Embedding\Image\DTO\ImageVectorDTO;

/**
 * Embedding ẢNH vendor-agnostic. CLIP/SigLIP hôm nay (self-host sidecar); thêm
 * Cohere/Voyage sau = 1 connector mới. `modelKey()` định danh collection + cột
 * `model` ⇒ chạy song song nhiều model không xung đột.
 */
interface ImageEmbedder
{
    public function enabled(): bool;

    /** @throws \RuntimeException khi sidecar lỗi (caller bắt & đánh status=failed). */
    public function embedImage(string $bytes, string $mime): ImageVectorDTO;

    public function modelKey(): string;

    public function dimension(): int;
}
```

- [ ] **Step 3: Commit**

```bash
git add app/app/Integrations/Embedding/Image/DTO app/app/Integrations/Embedding/Image/Contracts
git commit -m "feat(image-embedding): DTO + ImageEmbedder contract"
```

### Task 2.2: `ClipEmbedder` + test

**Files:**
- Create: `app/app/Integrations/Embedding/Image/Clip/ClipEmbedder.php`
- Test: `app/tests/Unit/Integrations/Embedding/ClipEmbedderTest.php`

- [ ] **Step 1: Test**

```php
<?php

namespace Tests\Unit\Integrations\Embedding;

use CMBcoreSeller\Integrations\Embedding\Image\Clip\ClipEmbedder;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClipEmbedderTest extends TestCase
{
    public function test_embed_posts_base64_and_returns_vector(): void
    {
        Http::fake([
            '*/embed' => Http::response(['vector' => [0.1, 0.2, 0.3], 'dim' => 3, 'model' => 'clip_vit_b32'], 200),
        ]);

        $e = new ClipEmbedder(['url' => 'http://clip:8000', 'model' => 'clip_vit_b32', 'dim' => 3, 'timeout' => 5]);
        $dto = $e->embedImage('RAWBYTES', 'image/jpeg');

        $this->assertSame([0.1, 0.2, 0.3], $dto->vector);
        $this->assertSame('clip_vit_b32', $dto->model);
        Http::assertSent(fn ($req) => $req->url() === 'http://clip:8000/embed'
            && $req->data()['image_base64'] === base64_encode('RAWBYTES'));
    }

    public function test_embed_throws_on_http_error(): void
    {
        Http::fake(['*' => Http::response('err', 500)]);
        $e = new ClipEmbedder(['url' => 'http://clip:8000', 'model' => 'm', 'dim' => 3, 'timeout' => 5]);
        $this->expectException(\RuntimeException::class);
        $e->embedImage('x', 'image/jpeg');
    }
}
```

- [ ] **Step 2: Chạy → FAIL**

Run: `cd app && php artisan test --filter=ClipEmbedderTest`
Expected: FAIL (class not found).

- [ ] **Step 3: Viết `ClipEmbedder`**

```php
<?php

namespace CMBcoreSeller\Integrations\Embedding\Image\Clip;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Embedding\Image\DTO\ImageVectorDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Gọi CLIP sidecar (FastAPI) `POST /embed` với ảnh base64. */
class ClipEmbedder implements ImageEmbedder
{
    private string $url;

    private string $model;

    private int $dim;

    private int $timeout;

    /** @param array{url?:string,model?:string,dim?:int,timeout?:int}|null $config */
    public function __construct(?array $config = null)
    {
        $config ??= (array) config('integrations.image_embedding.clip', []);
        $this->url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->model = (string) ($config['model'] ?? 'clip_vit_b32');
        $this->dim = (int) ($config['dim'] ?? 512);
        $this->timeout = (int) ($config['timeout'] ?? 30);
    }

    public function enabled(): bool
    {
        return $this->url !== '';
    }

    public function embedImage(string $bytes, string $mime): ImageVectorDTO
    {
        if (! $this->enabled()) {
            throw new RuntimeException('CLIP embedder chưa cấu hình (IMAGE_EMBEDDING_URL trống).');
        }
        $res = Http::timeout($this->timeout)->acceptJson()
            ->post($this->url.'/embed', ['image_base64' => base64_encode($bytes), 'mime' => $mime]);

        if (! $res->successful() || ! is_array($res->json('vector'))) {
            throw new RuntimeException('CLIP embed lỗi: HTTP '.$res->status());
        }

        $vector = array_map('floatval', (array) $res->json('vector'));

        return new ImageVectorDTO(
            vector: array_values($vector),
            dim: (int) ($res->json('dim') ?? count($vector)),
            model: (string) ($res->json('model') ?? $this->model),
        );
    }

    public function modelKey(): string
    {
        return $this->model;
    }

    public function dimension(): int
    {
        return $this->dim;
    }
}
```

- [ ] **Step 4: Chạy → PASS**

Run: `cd app && php artisan test --filter=ClipEmbedderTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Pint + commit**

```bash
cd app && vendor/bin/pint app/Integrations/Embedding tests/Unit/Integrations/Embedding
git add app/app/Integrations/Embedding tests/Unit/Integrations/Embedding
git commit -m "feat(image-embedding): ClipEmbedder + test"
```

### Task 2.3: `ImageEmbedderRegistry`

**Files:**
- Create: `app/app/Integrations/Embedding/Image/ImageEmbedderRegistry.php`

- [ ] **Step 1: Viết registry** (giống VectorStoreRegistry)

```php
<?php

namespace CMBcoreSeller\Integrations\Embedding\Image;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class ImageEmbedderRegistry
{
    /** @var array<string, class-string<ImageEmbedder>> */
    private array $drivers = [];

    public function __construct(private Container $container) {}

    /** @param class-string<ImageEmbedder> $class */
    public function register(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function for(string $code): ImageEmbedder
    {
        if (! isset($this->drivers[$code])) {
            throw new RuntimeException("Image embedder [{$code}] chưa đăng ký.");
        }

        return $this->container->make($this->drivers[$code]);
    }

    public function default(): ImageEmbedder
    {
        return $this->for((string) config('integrations.image_embedding.driver', 'clip'));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/app/Integrations/Embedding/Image/ImageEmbedderRegistry.php
git commit -m "feat(image-embedding): ImageEmbedderRegistry"
```

### Task 2.4: Config + binding 2 trục mới

**Files:**
- Modify: `app/config/integrations.php` (thêm 2 block trước dấu `];` cuối)
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php` (đăng ký 2 registry trong `register()`)

- [ ] **Step 1: Thêm config** — chèn trước `];` cuối của `app/config/integrations.php`:

```php
    /*
    |--------------------------------------------------------------------------
    | Vector store (visual search — 2026-06-16)
    |--------------------------------------------------------------------------
    | Tách RIÊNG Qdrant help-bot (config/support.php). Driver mặc định qdrant.
    */
    'vector' => [
        'driver' => env('VECTOR_DRIVER', 'qdrant'),
        'qdrant' => [
            'url' => env('VECTOR_QDRANT_URL', env('QDRANT_URL', 'http://qdrant:6333')),
            'api_key' => env('VECTOR_QDRANT_API_KEY', env('QDRANT_API_KEY', '')),
            'timeout' => (int) env('VECTOR_QDRANT_TIMEOUT', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image embedding (CLIP/SigLIP sidecar — 2026-06-16)
    |--------------------------------------------------------------------------
    */
    'image_embedding' => [
        'driver' => env('IMAGE_EMBEDDING_DRIVER', 'clip'),
        'clip' => [
            'url' => env('IMAGE_EMBEDDING_URL', ''),
            'model' => env('IMAGE_EMBEDDING_MODEL', 'clip_vit_b32'),
            'dim' => (int) env('IMAGE_EMBEDDING_DIM', 512),
            'timeout' => (int) env('IMAGE_EMBEDDING_TIMEOUT', 30),
        ],
    ],
```

- [ ] **Step 2: Đăng ký registry** — trong `IntegrationsServiceProvider::register()` (theo pattern AI registry đã đọc ở Task 0.1), thêm:

```php
use CMBcoreSeller\Integrations\Vector\VectorStoreRegistry;
use CMBcoreSeller\Integrations\Vector\Qdrant\QdrantStore;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Integrations\Embedding\Image\ImageEmbedderRegistry;
use CMBcoreSeller\Integrations\Embedding\Image\Clip\ClipEmbedder;
use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;

// ... trong register():
$this->app->singleton(VectorStoreRegistry::class, function ($app) {
    $r = new VectorStoreRegistry($app);
    $r->register('qdrant', QdrantStore::class);

    return $r;
});
$this->app->bind(VectorStore::class, fn ($app) => $app->make(VectorStoreRegistry::class)->default());

$this->app->singleton(ImageEmbedderRegistry::class, function ($app) {
    $r = new ImageEmbedderRegistry($app);
    $r->register('clip', ClipEmbedder::class);

    return $r;
});
$this->app->bind(ImageEmbedder::class, fn ($app) => $app->make(ImageEmbedderRegistry::class)->default());
```

- [ ] **Step 3: Verify boot**

Run: `cd app && php artisan config:clear && php -r "require 'vendor/autoload.php';" && php artisan about >/dev/null && echo OK`
Expected: `OK` (không lỗi container).

- [ ] **Step 4: Commit**

```bash
git add app/config/integrations.php app/app/Integrations/IntegrationsServiceProvider.php
git commit -m "feat(integrations): đăng ký trục Vector + Image Embedding (config + binding)"
```

---

## Phase 3 — AI capability `vision.analyze` (additive)

### Task 3.1: Thêm method vào contract + 4 connector

**Files:**
- Modify: `app/app/Integrations/Ai/Contracts/AiAssistantConnector.php`
- Modify: `app/app/Integrations/Ai/Claude/ClaudeConnector.php`
- Modify: `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php`
- Modify: `app/app/Integrations/Ai/CustomHttp/CustomHttpConnector.php`
- Modify: `app/app/Integrations/Ai/Manual/ManualAiAssistantConnector.php`
- Test: `app/tests/Unit/Integrations/Ai/VisionAnalyzeTest.php`

- [ ] **Step 1: Test (Claude + OpenAI gửi image block; Manual ném)**

```php
<?php

namespace Tests\Unit\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Ai\Manual\ManualAiAssistantConnector;
use Tests\TestCase;

class VisionAnalyzeTest extends TestCase
{
    public function test_manual_connector_does_not_support_vision_analyze(): void
    {
        $c = new ManualAiAssistantConnector('manual');
        $this->assertFalse($c->supports('vision.analyze'));
        $this->expectException(UnsupportedOperation::class);
        $c->analyzeImages(new AiContext(tenantId: 1, providerCode: 'manual'), ['data:image/png;base64,AAAA'], 'pick');
    }
}
```

> Lưu ý: test gọi Claude/OpenAI thật cần fake Http + credentials — thêm ở Task 3.2 nếu connector test đã có pattern; tối thiểu test Manual để chốt interface.

- [ ] **Step 2: Chạy → FAIL** (`analyzeImages` chưa tồn tại)

Run: `cd app && php artisan test --filter=VisionAnalyzeTest`
Expected: FAIL.

- [ ] **Step 3: Thêm method vào interface** — trong `AiAssistantConnector.php`, thêm trong phần capabilities-doc dòng `*   - 'vision.analyze'  — phân tích/đối chiếu ảnh (re-rank visual search)` và method:

```php
    /**
     * Phân tích/đối chiếu một tập ẢNH theo `$instruction` (vd: chọn item khớp).
     * `$images`: list URL hoặc data-URI `data:<mime>;base64,...` (giống recentMessages image_urls).
     * Provider không vision ⇒ {@see UnsupportedOperation}. Capability 'vision.analyze'.
     *
     * @param  list<string>  $images
     */
    public function analyzeImages(AiContext $ctx, array $images, string $instruction): string;
```

- [ ] **Step 4: Implement ở ClaudeConnector** — thêm `'vision.analyze' => true` vào `capabilities()` và method (tái dùng `imageSource()`, `visionEnabled()`, `buildHeaders`/HTTP như `generateReply`; build 1 user turn = instruction text + image blocks):

```php
    public function analyzeImages(AiContext $ctx, array $images, string $instruction): string
    {
        $cfg = AiProviderCredentials... // GIỐNG generateReply: resolve credentials qua $this->code()
        $model = $ctx->model ?? $cfg->defaultModel;
        if (! $this->visionEnabled($model)) {
            throw UnsupportedOperation::for($this->code(), 'analyzeImages (model không vision)');
        }
        $content = [['type' => 'text', 'text' => $instruction]];
        foreach ($images as $img) {
            $content[] = ['type' => 'image', 'source' => $this->imageSource($img)];
        }
        $payload = [
            'model' => $model,
            'max_tokens' => 300,
            'messages' => [['role' => 'user', 'content' => $content]],
        ];
        $res = Http::timeout((int) config('ai.http.reply_timeout', 60))
            ->withHeaders(['x-api-key' => $cfg->apiKey, 'anthropic-version' => '2023-06-01'])
            ->post(rtrim($cfg->baseUrl, '/').'/v1/messages', $payload);
        if (! $res->successful()) {
            throw UnsupportedOperation::for($this->code(), 'analyzeImages HTTP '.$res->status());
        }

        return (string) ($res->json('content.0.text') ?? '');
    }
```

> Engineer: thay đoạn resolve credentials/base_url/headers cho khớp 100% với `generateReply()` hiện có trong cùng file (đọc lại Task 0.1). Mục tiêu: 1 user turn, text + image blocks, trả text thô.

- [ ] **Step 5: Implement ở OpenAiConnector** — `'vision.analyze' => true` + method (content parts `{type:text}` + `{type:image_url, image_url:{url}}`, gọi `/v1/chat/completions`):

```php
    public function analyzeImages(AiContext $ctx, array $images, string $instruction): string
    {
        $cfg = ...; // GIỐNG generateReply
        $model = $ctx->model ?? $cfg->defaultModel;
        if (! $this->visionEnabled($model)) {
            throw UnsupportedOperation::for($this->code(), 'analyzeImages (model không vision)');
        }
        $parts = [['type' => 'text', 'text' => $instruction]];
        foreach ($images as $img) {
            $parts[] = ['type' => 'image_url', 'image_url' => ['url' => $img]];
        }
        $res = Http::timeout((int) config('ai.http.reply_timeout', 60))
            ->withToken($cfg->apiKey)
            ->post(rtrim($cfg->baseUrl, '/').'/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => 300,
                'messages' => [['role' => 'user', 'content' => $parts]],
            ]);
        if (! $res->successful()) {
            throw UnsupportedOperation::for($this->code(), 'analyzeImages HTTP '.$res->status());
        }

        return (string) ($res->json('choices.0.message.content') ?? '');
    }
```

- [ ] **Step 6: CustomHttp + Manual ném UnsupportedOperation** — thêm vào cả hai (capability `false`):

```php
    public function analyzeImages(\CMBcoreSeller\Integrations\Ai\DTO\AiContext $ctx, array $images, string $instruction): string
    {
        throw UnsupportedOperation::for($this->code(), 'analyzeImages');
    }
```

- [ ] **Step 7: Chạy → PASS**

Run: `cd app && php artisan test --filter=VisionAnalyzeTest`
Expected: PASS.

- [ ] **Step 8: Cả suite AI cũ vẫn xanh** (đảm bảo additive không vỡ)

Run: `cd app && php artisan test --filter=Integrations`
Expected: PASS (không regression).

- [ ] **Step 9: Pint + phpstan + commit**

```bash
cd app && vendor/bin/pint app/Integrations/Ai tests/Unit/Integrations/Ai && vendor/bin/phpstan analyse
git add app/app/Integrations/Ai tests/Unit/Integrations/Ai
git commit -m "feat(ai): capability vision.analyze (Claude/OpenAI), additive — Custom/Manual unsupported"
```

---
