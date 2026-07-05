# Gộp kho tri thức (text+ảnh) & Phong cách chốt sale — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một loại tri thức duy nhất (text luôn embed vector vào RAG, ảnh tùy chọn, bỏ form nhập RAG text riêng) + tùy chọn "phong cách chốt sale" toàn shop trong Cài đặt AI.

**Architecture:** Xây thực thể hợp nhất trên `VisualTrainingItem` (giữ nguyên hệ khớp ảnh CLIP/findByName vừa làm cứng), **thêm** đường index text-RAG cho item bằng cách tái dùng `KnowledgeVectorIndexer`/`KnowledgeRetriever` (chunk `ai_knowledge_chunks` giờ thuộc document HOẶC item). Doc chữ cũ vẫn chạy song song (Phase 1 không migrate). Phong cách chốt sale là 1 directive tiếng Việt chèn vào `$extra` của prompt, độc lập.

**Tech Stack:** Laravel 11, PHP 8.2, PHPUnit, Qdrant (VectorStore), React 18 + Ant Design + TanStack Query, Zustand.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`.
- `config()` không `env()` ngoài file config. Tenant/dynamic settings qua `system_setting()` / bảng.
- Money = int VND; timestamps ISO-8601 UTC. Không phá bất biến `BelongsToTenant`.
- Prod baked image, `RUN_MIGRATIONS=false` → **cần migrate thủ công sau deploy**, KHÔNG backfill tự động.
- Quality gate (chạy trong `app/`): `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (level 5 + baseline — KHÔNG thêm lỗi mới), `php artisan test`, `npm run lint && npm run typecheck && npm run build`.
- **BẮT BUỘC mỗi task:** hoàn thành mục "Ảnh hưởng & lỗi ngầm" — xác nhận không phá RAG hiện có, không phá luồng gửi ảnh/khớp ảnh, không phá hệ chốt sale (persona "QUY TẮC CHỐT ĐƠN"), không rò vào bước phân loại intent.
- UI: icon @ant-design/icons (không emoji); tập chọn nhỏ dùng Radio/Segmented (không Select).

---

## File Structure (Part A — KB unification)

- Modify `app/database`… → migration mới `..._add_kb_columns_to_visual_training_items.php`, `..._add_visual_item_id_to_ai_knowledge_chunks.php` (đặt trong `app/app/Modules/Messaging/Database/Migrations` cho chunk và `app/app/Modules/VisualSearch/Database/Migrations` cho item — mỗi module tự `loadMigrationsFrom`).
- Modify `app/app/Modules/VisualSearch/Models/VisualTrainingItem.php` — cột KB mới + hằng `KB_*`.
- Modify `app/app/Modules/Messaging/Models/AiKnowledgeChunk.php` — `visual_item_id` fillable.
- Modify `app/app/Modules/Messaging/Services/KnowledgeVectorIndexer.php` — tách `upsertChunks()` dùng chung + thêm `indexItemChunks()`.
- Create `app/app/Modules/VisualSearch/Jobs/IndexKnowledgeItem.php` — chunk + index text của item.
- Create `app/app/Modules/VisualSearch/Services/ItemTextComposer.php` — dựng text nguồn từ item (thuần, test được).
- Modify `app/app/Modules/VisualSearch/Http/Controllers/TrainingItemController.php` — dispatch index sau store/update, forget sau destroy.
- Modify `app/app/Modules/Messaging/Services/KnowledgeRetriever.php` — gộp chunk item vào truy hồi (vector + keyword) với scope page/provider.
- Modify FE: `resources/js/pages/MessagingKnowledgePage.tsx` / `MessagingVisualSearchPage.tsx` — hợp nhất 1 panel; ẩn form tạo tài liệu chữ.

## File Structure (Part B — sales-closing style)

- Modify `app/app/Modules/Messaging/Http/Controllers/MessagingSettingsController.php` — validate + persist `sales_closing_style`/`sales_closing_note` vào `messaging_settings.settings`.
- Modify `app/app/Modules/Messaging/Services/AiSuggestionService.php` — `withClosingStyle()` + hằng preset, nối vào chuỗi `$extra` ở CẢ `draftAutoReply()` và `suggest()`.
- Modify FE `resources/js/pages/MessagingSettingsPage.tsx` + `resources/js/lib/messagingConfig.tsx` — UI + type.

---

# PART A — Gộp kho tri thức (Phase 1)

### Task A1: Migration — cột KB cho `visual_training_items`

**Files:**
- Create: `app/app/Modules/VisualSearch/Database/Migrations/2026_07_06_100001_add_kb_columns_to_visual_training_items.php`
- Modify: `app/app/Modules/VisualSearch/Models/VisualTrainingItem.php`

**Interfaces:**
- Produces: cột `content_text`, `source`, `url`, `storage_path`, `provider`, `kb_status`, `chunk_count`, `embedding_provider_code`, `embedding_model`, `embedding_version`, `kb_indexed_at`; hằng `VisualTrainingItem::KB_PENDING|KB_READY|KB_FAILED`, `SOURCE_INLINE|SOURCE_URL|SOURCE_UPLOAD`.

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visual_training_items', function (Blueprint $table) {
            $table->text('content_text')->nullable()->after('description');
            $table->string('source', 16)->default('inline')->after('content_text'); // inline|url|upload
            $table->string('url')->nullable()->after('source');
            $table->string('storage_path')->nullable()->after('url');
            $table->string('provider', 32)->default('facebook_page')->after('storage_path');
            $table->string('kb_status', 16)->default('pending')->after('provider'); // pending|ready|failed
            $table->unsignedInteger('chunk_count')->default(0)->after('kb_status');
            $table->string('embedding_provider_code', 64)->nullable()->after('chunk_count');
            $table->string('embedding_model', 128)->nullable()->after('embedding_provider_code');
            $table->unsignedSmallInteger('embedding_version')->default(0)->after('embedding_model');
            $table->timestamp('kb_indexed_at')->nullable()->after('embedding_version');
            $table->index(['tenant_id', 'kb_status', 'provider'], 'vti_kb_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('visual_training_items', function (Blueprint $table) {
            $table->dropIndex('vti_kb_scope_idx');
            $table->dropColumn(['content_text', 'source', 'url', 'storage_path', 'provider',
                'kb_status', 'chunk_count', 'embedding_provider_code', 'embedding_model',
                'embedding_version', 'kb_indexed_at']);
        });
    }
};
```

- [ ] **Step 2: Thêm hằng + fillable + cast vào model**

Trong `VisualTrainingItem.php`, thêm hằng cạnh các hằng status hiện có và mở rộng `$fillable`/`casts()`:

```php
public const KB_PENDING = 'pending';
public const KB_READY = 'ready';
public const KB_FAILED = 'failed';

public const SOURCE_INLINE = 'inline';
public const SOURCE_URL = 'url';
public const SOURCE_UPLOAD = 'upload';
```

Thêm vào `$fillable`: `'content_text', 'source', 'url', 'storage_path', 'provider', 'kb_status', 'chunk_count', 'embedding_provider_code', 'embedding_model', 'embedding_version', 'kb_indexed_at'`.
Thêm vào `casts()`: `'kb_indexed_at' => 'datetime'`.

- [ ] **Step 3: Chạy migrate + kiểm tra**

Run: `cd app && php artisan migrate`
Expected: migrate OK; `php artisan test tests/Feature/VisualSearch/FindByNameTest.php` vẫn PASS (không phá visual hiện có).

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/VisualSearch/Database/Migrations app/app/Modules/VisualSearch/Models/VisualTrainingItem.php
git commit -m "feat(kb): thêm cột text-RAG cho visual_training_items"
```

**Ảnh hưởng & lỗi ngầm:** Chỉ THÊM cột nullable/default — không đổi hành vi visual hiện tại. `source` default `inline`, `provider` default `facebook_page` (khớp mặc định doc) để item cũ hợp lệ ngay. Index mới không đụng truy vấn cũ. Prod: cần chạy `php artisan migrate` sau deploy.

---

### Task A2: Migration + model — `visual_item_id` cho `ai_knowledge_chunks`

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_07_06_100002_add_visual_item_id_to_ai_knowledge_chunks.php`
- Modify: `app/app/Modules/Messaging/Models/AiKnowledgeChunk.php`

**Interfaces:**
- Produces: cột `ai_knowledge_chunks.visual_item_id` (nullable, index); `document_id` nullable; `visual_item_id` trong `$fillable`.

- [ ] **Step 1: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->change();
            $table->unsignedBigInteger('visual_item_id')->nullable()->after('document_id');
            $table->index(['tenant_id', 'visual_item_id'], 'akc_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
            $table->dropIndex('akc_item_idx');
            $table->dropColumn('visual_item_id');
            // document_id để nullable — không revert để tránh vỡ dữ liệu item.
        });
    }
};
```

Lưu ý: `->change()` cần `doctrine/dbal` (đã có nếu các migration khác dùng change). Nếu chưa có, thay bằng cách bỏ NOT NULL: trên SQLite (test) cột vốn không ràng buộc chặt; trên Postgres dùng raw `DB::statement('ALTER TABLE ai_knowledge_chunks ALTER COLUMN document_id DROP NOT NULL')` trong `up()` và bọc `try/catch` cho SQLite.

- [ ] **Step 2: Model** — thêm `'visual_item_id'` vào `$fillable` của `AiKnowledgeChunk`.

- [ ] **Step 3: Migrate + test regression**

Run: `cd app && php artisan migrate && php artisan test tests/Feature/Messaging/KnowledgeIndexingTest.php tests/Feature/Messaging/MessagingKnowledgeRagTest.php`
Expected: PASS (doc RAG cũ không đổi).

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Messaging/Database/Migrations app/app/Modules/Messaging/Models/AiKnowledgeChunk.php
git commit -m "feat(kb): chunk có thể thuộc document HOẶC visual item"
```

**Ảnh hưởng & lỗi ngầm:** `document_id` chuyển nullable — mọi truy vấn hiện tại đều `where('document_id', ...)` nên không ảnh hưởng. Chunk cũ giữ `visual_item_id=null`. Rủi ro chính: `->change()` yêu cầu dbal trên Postgres prod — nếu thiếu, dùng raw ALTER (đã nêu). Test regression bắt buộc PASS trước khi tiếp.

---

### Task A3: `KnowledgeVectorIndexer` — tách `upsertChunks()` dùng chung + `indexItemChunks()`

**Files:**
- Modify: `app/app/Modules/Messaging/Services/KnowledgeVectorIndexer.php`
- Test: `app/tests/Feature/Messaging/KnowledgeIndexingTest.php` (thêm ca item)

**Interfaces:**
- Consumes: `VisualTrainingItem` (id, tenant_id), `AiKnowledgeChunk` rows với `visual_item_id`.
- Produces: `indexItemChunks(VisualTrainingItem $item, iterable $chunks): int` — embed + upsert Qdrant payload `{tenant_id, item_id}`, cập nhật `item.embedding_*`; private `upsertChunks(int $tenantId, iterable $chunks, array $extraPayload): int` dùng chung.

- [ ] **Step 1: Test — index chunk của item ghi payload item_id + embedding**

```php
public function test_index_item_chunks_upserts_with_item_payload(): void
{
    // Fake VectorStore ghi lại payload để khẳng định dùng item_id (không document_id).
    $captured = [];
    $store = \Mockery::mock(\CMBcoreSeller\Integrations\Vector\Contracts\VectorStore::class);
    $store->shouldReceive('enabled')->andReturn(true);
    $store->shouldReceive('ensureCollection')->andReturn(null);
    $store->shouldReceive('upsert')->andReturnUsing(function ($col, $points) use (&$captured) { $captured = $points; });
    $this->app->instance(\CMBcoreSeller\Integrations\Vector\Contracts\VectorStore::class, $store);

    // Ép embed trả vector cố định (bỏ HTTP thật).
    $indexer = \Mockery::mock(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class.'[embed]', [
        app(\CMBcoreSeller\Integrations\Ai\AiAssistantRegistry::class), $store,
    ]);
    $indexer->shouldAllowMockingProtectedMethods();
    $indexer->shouldReceive('embed')->andReturn([0.1, 0.2, 0.3]);

    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'Áo thun', 'status' => 'active', 'applies_all_pages' => true]);
    $chunk = \CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::create([
        'tenant_id' => 1, 'visual_item_id' => $item->id, 'chunk_index' => 0,
        'chunk_text' => 'Áo thun cotton', 'embedding' => null, 'token_count' => 3,
    ]);

    $n = $indexer->indexItemChunks($item, [$chunk]);

    $this->assertSame(1, $n);
    $this->assertSame(['tenant_id' => 1, 'item_id' => $item->id], $captured[0]['payload']);
    $this->assertNotNull($chunk->fresh()->embedding);
}
```

- [ ] **Step 2: Run test — fail**

Run: `cd app && php artisan test tests/Feature/Messaging/KnowledgeIndexingTest.php --filter=index_item_chunks`
Expected: FAIL ("Method indexItemChunks does not exist").

- [ ] **Step 3: Refactor + implement**

Tách vòng lặp embed+upsert trong `indexChunks()` ra private `upsertChunks()`, rồi thêm `indexItemChunks()`. `indexChunks()` giữ hành vi cũ y hệt (chỉ gọi helper):

```php
public function indexChunks(AiKnowledgeDocument $doc, iterable $chunks): int
{
    if (! $this->store->enabled()) {
        return 0;
    }
    $embedded = $this->upsertChunks((int) $doc->tenant_id, $chunks, ['document_id' => (int) $doc->id]);
    if ($embedded > 0) {
        $doc->forceFill([
            'embedding_provider_code' => $this->providerCode((int) $doc->tenant_id),
            'embedding_model' => $this->model(),
            'embedding_version' => (int) $doc->embedding_version + 1,
        ])->save();
    }

    return $embedded;
}

/** Embed + upsert chunk của 1 visual item (tri thức hợp nhất). Payload item_id. */
public function indexItemChunks(\CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem $item, iterable $chunks): int
{
    if (! $this->store->enabled()) {
        return 0;
    }
    $embedded = $this->upsertChunks((int) $item->tenant_id, $chunks, ['item_id' => (int) $item->id]);
    if ($embedded > 0) {
        $item->forceFill([
            'embedding_provider_code' => $this->providerCode((int) $item->tenant_id),
            'embedding_model' => $this->model(),
            'embedding_version' => (int) $item->embedding_version + 1,
        ])->save();
    }

    return $embedded;
}

/**
 * Embed từng chunk + upsert Qdrant (batch 64) với payload `['tenant_id'=>...] + $extraPayload`.
 * Lưu embedding về chunk row. Trả số chunk đã vector hoá.
 *
 * @param  iterable<AiKnowledgeChunk>  $chunks
 * @param  array<string,int>  $extraPayload
 */
private function upsertChunks(int $tenantId, iterable $chunks, array $extraPayload): int
{
    $collection = $this->collection();
    $points = [];
    $embedded = 0;
    $ensured = false;

    foreach ($chunks as $chunk) {
        $vec = $this->embed($tenantId, (string) $chunk->chunk_text);
        if ($vec === null) {
            continue;
        }
        if (! $ensured) {
            $this->store->ensureCollection($collection, count($vec));
            $ensured = true;
        }
        $chunk->forceFill(['embedding' => $vec])->save();
        $points[] = [
            'id' => (string) $chunk->id,
            'vector' => $vec,
            'payload' => ['tenant_id' => $tenantId] + $extraPayload,
        ];
        $embedded++;
        if (count($points) >= 64) {
            $this->store->upsert($collection, $points);
            $points = [];
        }
    }
    if ($points !== []) {
        $this->store->upsert($collection, $points);
    }

    return $embedded;
}
```

Thêm `use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;` ở đầu file (không dùng FQCN dài).

- [ ] **Step 4: Run tests — pass**

Run: `cd app && php artisan test tests/Feature/Messaging/KnowledgeIndexingTest.php`
Expected: PASS (ca item mới + ca doc cũ).

- [ ] **Step 5: Pint + phpstan + commit**

```bash
cd app && vendor/bin/pint app/Modules/Messaging/Services/KnowledgeVectorIndexer.php && vendor/bin/phpstan analyse app/Modules/Messaging/Services/KnowledgeVectorIndexer.php
git add app/app/Modules/Messaging/Services/KnowledgeVectorIndexer.php app/tests/Feature/Messaging/KnowledgeIndexingTest.php
git commit -m "feat(kb): indexItemChunks — embed text visual item vào RAG"
```

**Ảnh hưởng & lỗi ngầm:** `indexChunks()` (đường doc) hành vi KHÔNG đổi — chỉ chuyển thân vòng lặp sang `upsertChunks()`; test regression doc phải PASS. Chung 1 collection `messaging_kb__<model>` (khác payload key `document_id` vs `item_id`) — search filter theo `tenant_id` nên cả hai cùng ra; tầng retriever phân biệt bằng chunk row. Không đụng CLIP/visual embeddings (khác collection).

---

### Task A4: `ItemTextComposer` — dựng text nguồn từ item (thuần)

**Files:**
- Create: `app/app/Modules/VisualSearch/Services/ItemTextComposer.php`
- Test: `app/tests/Feature/VisualSearch/ItemTextComposerTest.php`

**Interfaces:**
- Produces: `ItemTextComposer::compose(VisualTrainingItem $item): string` — ghép `name` + `ref_code` + `description` + `attributes` (key: value) + `content_text`, bỏ phần rỗng, trim.

- [ ] **Step 1: Test**

```php
public function test_compose_joins_name_description_attributes_content(): void
{
    $item = new \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem([
        'name' => 'Bộ thu bluetooth', 'ref_code' => 'BT01',
        'description' => 'Kết nối 5.0', 'attributes' => ['màu' => 'đen', 'bảo hành' => '12 tháng'],
        'content_text' => 'Hỗ trợ AptX. Pin 10h.',
    ]);
    $out = app(\CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer::class)->compose($item);
    $this->assertStringContainsString('Bộ thu bluetooth', $out);
    $this->assertStringContainsString('BT01', $out);
    $this->assertStringContainsString('Kết nối 5.0', $out);
    $this->assertStringContainsString('màu: đen', $out);
    $this->assertStringContainsString('Pin 10h', $out);
}

public function test_compose_empty_when_no_text(): void
{
    $item = new \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem(['name' => '']);
    $this->assertSame('', app(\CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer::class)->compose($item));
}
```

- [ ] **Step 2: Run — fail** (`ItemTextComposer` chưa có).

- [ ] **Step 3: Implement**

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;

/** Ghép nội dung text của 1 mục tri thức để chunk + embed RAG. Thuần, không side-effect. */
class ItemTextComposer
{
    public function compose(VisualTrainingItem $item): string
    {
        $parts = [
            trim((string) $item->name),
            trim((string) $item->ref_code),
            trim((string) $item->description),
        ];
        foreach ((array) $item->attributes as $k => $v) {
            if (is_scalar($v) && trim((string) $v) !== '') {
                $parts[] = trim((string) $k).': '.trim((string) $v);
            }
        }
        $parts[] = trim((string) $item->content_text);

        return trim(implode("\n", array_filter($parts, fn ($p) => $p !== '')));
    }
}
```

- [ ] **Step 4: Run — pass. Step 5: Pint + commit.**

```bash
cd app && vendor/bin/pint app/Modules/VisualSearch/Services/ItemTextComposer.php
git add app/app/Modules/VisualSearch/Services/ItemTextComposer.php app/tests/Feature/VisualSearch/ItemTextComposerTest.php
git commit -m "feat(kb): ItemTextComposer dựng text nguồn cho RAG"
```

**Ảnh hưởng & lỗi ngầm:** Thuần hàm, không đụng gì khác. Đảm bảo `attributes` không phải scalar (mảng lồng) bị bỏ qua an toàn (`is_scalar`).

---

### Task A5: `IndexKnowledgeItem` job — chunk + index text của item

**Files:**
- Create: `app/app/Modules/VisualSearch/Jobs/IndexKnowledgeItem.php`
- Test: `app/tests/Feature/VisualSearch/IndexKnowledgeItemTest.php`

**Interfaces:**
- Consumes: `ItemTextComposer::compose`, `KnowledgeVectorIndexer::indexItemChunks`, `KnowledgeVectorIndexer::forget`.
- Produces: `IndexKnowledgeItem::dispatch(int $itemId)` (queue `messaging-ai`), tạo `ai_knowledge_chunks` (`visual_item_id`), set `item.kb_status=ready|failed`, `chunk_count`.

- [ ] **Step 1: Test**

```php
public function test_indexes_item_text_into_chunks_and_marks_ready(): void
{
    // VectorStore tắt ⇒ fail-soft: vẫn tạo chunk + set ready (embed bỏ qua như doc).
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'Bộ thu bluetooth', 'description' => 'Kết nối 5.0 HIFI',
        'status' => 'active', 'applies_all_pages' => true, 'source' => 'inline']);

    (new \CMBcoreSeller\Modules\VisualSearch\Jobs\IndexKnowledgeItem($item->id))->handle(
        app(\CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer::class),
        app(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class),
    );

    $this->assertSame('ready', $item->fresh()->kb_status);
    $this->assertDatabaseHas('ai_knowledge_chunks', ['visual_item_id' => $item->id, 'chunk_index' => 0]);
}

public function test_empty_text_marks_ready_with_zero_chunks(): void
{
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => '', 'status' => 'active', 'applies_all_pages' => true]);

    (new \CMBcoreSeller\Modules\VisualSearch\Jobs\IndexKnowledgeItem($item->id))->handle(
        app(\CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer::class),
        app(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class),
    );

    $this->assertSame('ready', $item->fresh()->kb_status);
    $this->assertSame(0, $item->fresh()->chunk_count);
}
```

- [ ] **Step 2: Run — fail.**

- [ ] **Step 3: Implement**

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Index text của 1 mục tri thức hợp nhất (visual item) cho RAG: dựng text → chunk →
 * ghi ai_knowledge_chunks(visual_item_id) → embed Qdrant (fail-soft). Ảnh nằm ở pipeline
 * CLIP riêng (EmbedTrainingImage) — job này CHỈ lo phần text. Queue messaging-ai, tries 2.
 */
class IndexKnowledgeItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $itemId)
    {
        $this->onQueue('messaging-ai');
    }

    public function handle(ItemTextComposer $composer, KnowledgeVectorIndexer $vectorIndexer): void
    {
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->find($this->itemId);
        if (! $item) {
            return;
        }

        try {
            // Xoá chunk cũ + point Qdrant (re-index idempotent).
            $oldIds = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('visual_item_id', $item->id)->pluck('id')->all();
            $vectorIndexer->forget($oldIds);
            AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('visual_item_id', $item->id)->delete();

            $text = $composer->compose($item);
            $chunks = $this->chunk($text);
            $created = [];
            foreach ($chunks as $i => $chunkText) {
                $created[] = AiKnowledgeChunk::create([
                    'tenant_id' => $item->tenant_id,
                    'visual_item_id' => $item->id,
                    'chunk_index' => $i,
                    'chunk_text' => $chunkText,
                    'embedding' => null,
                    'token_count' => (int) ceil(mb_strlen($chunkText) / 4),
                ]);
            }

            $item->forceFill([
                'chunk_count' => count($chunks),
                'kb_status' => VisualTrainingItem::KB_READY,
                'kb_indexed_at' => now(),
            ])->save();

            $vectorIndexer->indexItemChunks($item, $created);
        } catch (\Throwable $e) {
            $item->forceFill(['kb_status' => VisualTrainingItem::KB_FAILED])->save();
        }
    }

    /** @return list<string> Cắt 800 ký tự (như doc free-text). */
    private function chunk(string $text, int $size = 800): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        return array_values(array_filter(mb_str_split($text, $size), fn ($c) => trim($c) !== ''));
    }
}
```

- [ ] **Step 4: Run — pass. Step 5: Pint + phpstan + commit.**

```bash
cd app && vendor/bin/pint app/Modules/VisualSearch/Jobs/IndexKnowledgeItem.php && vendor/bin/phpstan analyse app/Modules/VisualSearch/Jobs/IndexKnowledgeItem.php
git add app/app/Modules/VisualSearch/Jobs/IndexKnowledgeItem.php app/tests/Feature/VisualSearch/IndexKnowledgeItemTest.php
git commit -m "feat(kb): IndexKnowledgeItem chunk+embed text mục tri thức"
```

**Ảnh hưởng & lỗi ngầm:** Job CHỈ đụng chunk có `visual_item_id` của đúng item (không chạm chunk document). Fail-soft giống doc: Qdrant tắt vẫn tạo chunk + ready (retriever rơi keyword). Phase 1 chưa xử `source=url/upload` (chỉ inline `content_text`/description) — file/URL để Phase 2; item không set các trường đó nên an toàn.

---

### Task A6: Hook CRUD item → (re)index + forget

**Files:**
- Modify: `app/app/Modules/VisualSearch/Http/Controllers/TrainingItemController.php`
- Test: `app/tests/Feature/VisualSearch/TrainingItemIndexHookTest.php`

**Interfaces:**
- Consumes: `IndexKnowledgeItem::dispatch`, `KnowledgeVectorIndexer::forget`.
- Produces: sau `store()`/`update()` → `IndexKnowledgeItem::dispatch($item->id)`; sau `destroy()` → xoá chunk item + forget vector.

- [ ] **Step 1: Test (Queue::fake khẳng định dispatch)**

```php
public function test_store_and_update_dispatch_index_job(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $owner = /* seed owner + tenant header như các test TrainingItem khác */;

    $id = $this->actingAs($owner)->withHeaders($this->tenantHeader())
        ->postJson('/api/v1/visual-search/items', ['name' => 'Bộ thu bluetooth', 'description' => 'HIFI'])
        ->assertCreated()->json('data.id');
    \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\VisualSearch\Jobs\IndexKnowledgeItem::class);

    \Illuminate\Support\Facades\Queue::fake();
    $this->actingAs($owner)->withHeaders($this->tenantHeader())
        ->patchJson("/api/v1/visual-search/items/{$id}", ['description' => 'HIFI + AptX'])->assertOk();
    \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\VisualSearch\Jobs\IndexKnowledgeItem::class);
}
```

(Tham chiếu `tests/Feature/VisualSearch/*` hiện có để lấy helper seed owner/tenant header.)

- [ ] **Step 2: Run — fail.**

- [ ] **Step 3: Implement** — trong `TrainingItemController`:
  - Cuối `store()` (sau `syncPages`): `\CMBcoreSeller\Modules\VisualSearch\Jobs\IndexKnowledgeItem::dispatch($item->id);` (đặt `kb_status='pending'` khi tạo — model default đã pending).
  - Cuối `update()`: set `kb_status='pending'` rồi `IndexKnowledgeItem::dispatch($item->id);`.
  - Trong `destroy()`: trước khi xoá item → xoá chunk + forget:

```php
$chunkIds = \CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::withoutGlobalScope(
    \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
)->where('visual_item_id', $item->id)->pluck('id')->all();
app(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class)->forget($chunkIds);
\CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::withoutGlobalScope(
    \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
)->where('visual_item_id', $item->id)->delete();
```

Thêm `use` cho `IndexKnowledgeItem`, `AiKnowledgeChunk`, `TenantScope`, `KnowledgeVectorIndexer` để tránh FQCN dài. **Lưu ý module dep:** VisualSearch dùng `Messaging\Models\AiKnowledgeChunk` + `Messaging\Services\KnowledgeVectorIndexer` — đây là phụ thuộc chéo module. Kiểm tra `docs/01-architecture/modules.md`: nếu cấm, bọc qua một Contract trong Messaging (vd `KnowledgeIndexPort`) và bind; nếu cho phép (KB là hạ tầng dùng chung) thì import trực tiếp. **Xác nhận trước khi code.**

- [ ] **Step 4: Run — pass. Step 5: Pint + phpstan + commit.**

```bash
git add app/app/Modules/VisualSearch/Http/Controllers/TrainingItemController.php app/tests/Feature/VisualSearch/TrainingItemIndexHookTest.php
git commit -m "feat(kb): tạo/sửa/xoá mục tri thức tự (re)index RAG"
```

**Ảnh hưởng & lỗi ngầm:** Dispatch bất đồng bộ (queue `messaging-ai` — đã có supervisor). Không chặn request. Xoá item dọn chunk+vector tránh rác RAG. RỦI RO module-dependency (chéo Messaging↔VisualSearch) — phải kiểm luật module (đã ghi ở Step 3). Không đụng pipeline ảnh (EmbedTrainingImage vẫn chạy song song khi upload ảnh).

---

### Task A7: `KnowledgeRetriever` — gộp chunk item vào truy hồi

**Files:**
- Modify: `app/app/Modules/Messaging/Services/KnowledgeRetriever.php`
- Test: `app/tests/Feature/Messaging/KnowledgeRetrieverItemScopeTest.php`

**Interfaces:**
- Consumes: `AiKnowledgeChunk.visual_item_id`, `VisualTrainingItem` (kb_status, provider, applies_all_pages, pivot `visual_training_item_page`).
- Produces: `retrieve()` trả về chunk từ CẢ document (cũ) lẫn item (mới), đúng scope page/provider; keyword fallback tương tự.

- [ ] **Step 1: Test — item chunk được truy hồi, đúng scope**

```php
public function test_retrieve_includes_item_chunks_via_keyword(): void
{
    // Không có Qdrant ⇒ đi keyword. Tạo item ready + chunk.
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'Bộ thu bluetooth', 'kb_status' => 'ready',
        'provider' => 'facebook_page', 'applies_all_pages' => true, 'status' => 'active']);
    \CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::create([
        'tenant_id' => 1, 'visual_item_id' => $item->id, 'chunk_index' => 0,
        'chunk_text' => 'Bộ thu bluetooth kết nối 5.0 HIFI', 'token_count' => 6,
    ]);

    $kb = app(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeRetriever::class)
        ->retrieve(1, 'bluetooth hifi', 4, null, 'facebook_page');

    $this->assertNotEmpty($kb->chunks);
    $this->assertSame('Bộ thu bluetooth', $kb->chunks[0]['title']);
}

public function test_item_chunk_excluded_when_provider_mismatch(): void
{
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'X', 'kb_status' => 'ready',
        'provider' => 'zalo_oa', 'applies_all_pages' => true, 'status' => 'active']);
    \CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::create([
        'tenant_id' => 1, 'visual_item_id' => $item->id, 'chunk_index' => 0,
        'chunk_text' => 'bluetooth hifi', 'token_count' => 2,
    ]);

    $kb = app(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeRetriever::class)
        ->retrieve(1, 'bluetooth hifi', 4, null, 'facebook_page');
    $this->assertEmpty($kb->chunks);
}
```

- [ ] **Step 2: Run — fail** (item chunk chưa được gộp).

- [ ] **Step 3: Implement** — mở rộng retriever:

Thêm `readyItemTitles(tenantId, channelAccountId, provider): Collection<int,string>` (map itemId ⇒ name), mirror `readyDocumentTitles` nhưng trên `visual_training_items` (`kb_status=ready`, provider, pivot `visual_training_item_page`). Sửa `retrieve()` để lấy CẢ hai map; nếu cả hai rỗng ⇒ trả rỗng. Sửa `retrieveByVector`/`retrieveByKeyword` để nạp chunk theo `id` trong scores/tất cả, lọc `(document_id ∈ readyDocIds) OR (visual_item_id ∈ readyItemIds)`, và lấy title từ map tương ứng.

```php
public function retrieve(int $tenantId, string $query, int $topK = 4, ?int $channelAccountId = null, ?string $provider = null): KnowledgeBase
{
    $readyDocIds = $this->readyDocumentTitles($tenantId, $channelAccountId, $provider);
    $readyItemIds = $this->readyItemTitles($tenantId, $channelAccountId, $provider);
    if ($readyDocIds->isEmpty() && $readyItemIds->isEmpty()) {
        return new KnowledgeBase(chunks: []);
    }
    $viaVector = $this->retrieveByVector($tenantId, $query, $topK, $readyDocIds, $readyItemIds);
    if ($viaVector !== null) {
        return $viaVector;
    }

    return $this->retrieveByKeyword($tenantId, $query, $topK, $readyDocIds, $readyItemIds);
}

/** @return \Illuminate\Support\Collection<int,string> itemId ⇒ name */
private function readyItemTitles(int $tenantId, ?int $channelAccountId, ?string $provider): \Illuminate\Support\Collection
{
    $q = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(TenantScope::class)
        ->where('tenant_id', $tenantId)
        ->where('kb_status', \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::KB_READY);
    if ($provider !== null) {
        $q->where('provider', $provider);
    }
    if ($channelAccountId !== null) {
        $q->where(fn ($w) => $w
            ->where('applies_all_pages', true)
            ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                ->from('visual_training_item_page')
                ->whereColumn('visual_training_item_page.item_id', 'visual_training_items.id')
                ->where('visual_training_item_page.channel_account_id', $channelAccountId)));
    }

    return $q->pluck('name', 'id');
}
```

Trong `retrieveByVector`/`retrieveByKeyword`: đổi truy vấn chunk sang lọc theo cả hai nguồn và xây kết quả:

```php
$chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
    ->where('tenant_id', $tenantId)
    ->whereIn('id', array_keys($scores))            // (vector) hoặc bỏ dòng này ở keyword
    ->where(fn ($w) => $w
        ->whereIn('document_id', $readyDocIds->keys())
        ->orWhereIn('visual_item_id', $readyItemIds->keys()))
    ->get(['id', 'document_id', 'visual_item_id', 'chunk_text']);
...
$title = $chunk->document_id !== null
    ? (string) ($readyDocIds[$chunk->document_id] ?? '')
    : (string) ($readyItemIds[$chunk->visual_item_id] ?? '');
$out[] = ['document_id' => (int) ($chunk->document_id ?? 0), 'title' => $title,
          'chunk_text' => (string) $chunk->chunk_text, 'score' => ...];
```

`retrieveByKeyword` bỏ `whereIn('id', ...)` (quét toàn bộ chunk in-scope như cũ) nhưng thêm nhánh OR item. Thêm `use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;`.

- [ ] **Step 4: Run — pass** (item mới + doc cũ). Chạy cả `KnowledgeRetrieverPageScopeTest` cũ để chắc doc scope không vỡ.

Run: `cd app && php artisan test tests/Feature/Messaging/KnowledgeRetrieverItemScopeTest.php tests/Feature/Messaging/KnowledgeRetrieverPageScopeTest.php tests/Feature/Messaging/MessagingKnowledgeRagTest.php`
Expected: PASS.

- [ ] **Step 5: Pint + phpstan + commit.**

```bash
git add app/app/Modules/Messaging/Services/KnowledgeRetriever.php app/tests/Feature/Messaging/KnowledgeRetrieverItemScopeTest.php
git commit -m "feat(kb): retriever gộp chunk mục tri thức (item) cùng doc"
```

**Ảnh hưởng & lỗi ngầm:** Đây là thay đổi RỦI RO CAO NHẤT (đụng RAG live). Bảo chứng: (1) doc path giữ nguyên điều kiện `document_id ∈ readyDocIds`; (2) item chỉ thêm qua nhánh OR — doc không set `visual_item_id` nên không lẫn; (3) scope provider/page cho item mirror doc; (4) test regression doc PASS bắt buộc. Module-dep: Messaging import `VisualSearch\Models` — kiểm luật module (như A6). Nếu cấm, đọc item qua một read-model/contract.

---

### Task A8: FE — hợp nhất 1 panel "Kiến thức", ẩn form tạo tài liệu chữ

**Files:**
- Modify: `resources/js/pages/MessagingKnowledgePage.tsx` (và/hoặc `MessagingVisualSearchPage.tsx`)
- Modify: `resources/js/lib/messagingConfig.tsx` / `resources/js/features/visual-search/*`

**Interfaces:**
- Consumes: API item hiện có (`POST/PATCH /visual-search/items`, upload ảnh), API doc cũ (chỉ để hiển thị legacy).
- Produces: Một màn "Kiến thức" — form: Tên (bắt buộc), Nội dung (textarea, map vào `description`/`content_text`), ref_code (tùy chọn), Ảnh (khu upload tùy chọn), phạm vi page, provider. Panel "Tài liệu (chữ)" cũ chuyển chế độ chỉ-đọc (badge "Cũ"), **ẩn nút Thêm tài liệu chữ**.

- [ ] **Step 1:** Thêm trường "Nội dung" (textarea dài) vào form item hiện có; nhãn Ảnh "(tùy chọn — để AI gửi ảnh cho khách)". Text = bắt buộc.
- [ ] **Step 2:** Trong trang Knowledge: gộp hiển thị — mục tri thức (item) là danh sách chính; tài liệu chữ cũ hiển thị dưới nhãn "Tài liệu cũ (chỉ xem)", **bỏ nút tạo mới** (`useCreateKnowledge` không gọi ở UI tạo). Điều hướng người dùng tạo mới qua form item hợp nhất.
- [ ] **Step 3:** `npm run lint && npm run typecheck && npm run build` — PASS.
- [ ] **Step 4: Commit.**

```bash
git add app/resources/js
git commit -m "feat(kb): 1 màn Kiến thức (text+ảnh tùy chọn), ẩn tạo tài liệu chữ"
```

**Ảnh hưởng & lỗi ngầm:** Không xoá API/dữ liệu doc cũ (chỉ ẩn nút tạo) → không mất tri thức, không vỡ hội thoại đang dùng doc cũ. Người dùng chỉ còn 1 đường tạo → hết nhầm "2 dữ liệu". Kiểm tra không component nào khác phụ thuộc nút tạo doc.

---

# PART B — Phong cách chốt sale (toàn shop)

### Task B1: Lưu + API `sales_closing_style`/`sales_closing_note`

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/MessagingSettingsController.php`
- Test: `app/tests/Feature/Messaging/SalesClosingStyleSettingTest.php`

**Interfaces:**
- Produces: `PATCH /api/v1/messaging/settings` nhận `sales_closing_style` (∈ preset), `sales_closing_note` (nullable ≤500) → lưu vào `messaging_settings.settings`; `GET` trả về trong payload.

- [ ] **Step 1: Test**

```php
public function test_saves_and_returns_sales_closing_style(): void
{
    $owner = $this->owner(); // helper sẵn có trong test settings
    $this->actingAs($owner)->withHeaders($this->tenantHeader())
        ->patchJson('/api/v1/messaging/settings', ['sales_closing_style' => 'fast_close', 'sales_closing_note' => 'Nhấn freeship'])
        ->assertOk();

    $this->actingAs($owner)->withHeaders($this->tenantHeader())
        ->getJson('/api/v1/messaging/settings')
        ->assertOk()
        ->assertJsonPath('data.sales_closing_style', 'fast_close')
        ->assertJsonPath('data.sales_closing_note', 'Nhấn freeship');
}

public function test_rejects_invalid_style(): void
{
    $this->actingAs($this->owner())->withHeaders($this->tenantHeader())
        ->patchJson('/api/v1/messaging/settings', ['sales_closing_style' => 'bogus'])
        ->assertStatus(422);
}
```

- [ ] **Step 2: Run — fail.**
- [ ] **Step 3: Implement** — trong `MessagingSettingsController`:
  - Định nghĩa preset (nên đặt hằng ở `AiSuggestionService` — xem B2 — và import, hoặc hằng riêng trong controller): `['default','consultative','fast_close','scarcity','attentive']`.
  - `update()` validate thêm: `'sales_closing_style' => ['nullable', Rule::in($presets)]`, `'sales_closing_note' => ['nullable','string','max:500']`. Lưu vào cột `settings` (merge, không ghi đè khoá khác):

```php
$settings = (array) ($setting->settings ?? []);
if ($request->has('sales_closing_style')) {
    $settings['sales_closing_style'] = (string) $request->input('sales_closing_style') ?: null;
}
if ($request->has('sales_closing_note')) {
    $settings['sales_closing_note'] = trim((string) $request->input('sales_closing_note')) ?: null;
}
$setting->settings = $settings;
```

  - `show()` payload thêm: `'sales_closing_style' => $settings['sales_closing_style'] ?? 'default'`, `'sales_closing_note' => $settings['sales_closing_note'] ?? null`.

- [ ] **Step 4: Run — pass. Step 5: Pint + phpstan + commit.**

```bash
git add app/app/Modules/Messaging/Http/Controllers/MessagingSettingsController.php app/tests/Feature/Messaging/SalesClosingStyleSettingTest.php
git commit -m "feat(sales): lưu phong cách chốt sale toàn shop trong settings"
```

**Ảnh hưởng & lỗi ngầm:** Chỉ merge vào JSON `settings` (không đụng cột khác). Mặc định `default` = giữ nguyên hành vi persona hiện tại → không thay đổi shop chưa cấu hình. Không đụng auto-mode/provider.

---

### Task B2: Chèn directive vào prompt (`withClosingStyle`)

**Files:**
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php`
- Test: `app/tests/Feature/Messaging/SalesClosingStylePromptTest.php`

**Interfaces:**
- Consumes: `MessagingSetting.settings.sales_closing_style/note` (tenant của `$conv`).
- Produces: `withClosingStyle(string $extra, Conversation $conv): string`; hằng `CLOSING_STYLES` (map style ⇒ directive VN); gọi trong CẢ `draftAutoReply()` và `suggest()`.

- [ ] **Step 1: Test** (dùng Manual provider, khẳng định directive vào system prompt qua một connector fake ghi lại `$extra`, hoặc kiểm gián tiếp bằng cách gọi `withClosingStyle` qua reflection). Ưu tiên test đơn vị hàm map:

```php
public function test_closing_style_directive_injected_for_fast_close(): void
{
    // Set tenant style = fast_close, rồi khẳng định $extra chứa từ khoá chốt nhanh.
    $conv = $this->seedConvWithStyle('fast_close'); // helper: tạo conv + MessagingSetting.settings
    $svc = app(\CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService::class);
    $extra = $this->invokePrivate($svc, 'withClosingStyle', ['', $conv]);
    $this->assertStringContainsString('chốt', mb_strtolower($extra));
    $this->assertStringContainsString('giao hàng', mb_strtolower($extra)); // fast_close mời đặt/xin thông tin
}

public function test_default_style_adds_nothing(): void
{
    $conv = $this->seedConvWithStyle('default');
    $svc = app(\CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService::class);
    $this->assertSame('', $this->invokePrivate($svc, 'withClosingStyle', ['', $conv]));
}
```

- [ ] **Step 2: Run — fail.**
- [ ] **Step 3: Implement**

```php
/** Preset phong cách chốt sale → chỉ dẫn tiếng Việt nối vào prompt (sau persona). */
private const CLOSING_STYLES = [
    'consultative' => 'Phong cách: TƯ VẤN nhẹ nhàng — ưu tiên giải đáp đúng nhu cầu, KHÔNG hối thúc mua; chỉ mời đặt khi khách đã sẵn sàng.',
    'fast_close' => 'Phong cách: CHỐT NHANH — sau khi giải đáp, CHỦ ĐỘNG mời khách đặt hàng và xin thông tin giao hàng (tên, SĐT, địa chỉ) sớm, lịch sự, không nài ép.',
    'scarcity' => 'Phong cách: TẠO QUYẾT ĐỊNH — nhấn mạnh ưu đãi/khan hiếm có thời hạn một cách trung thực (không bịa) để khuyến khích chốt sớm.',
    'attentive' => 'Phong cách: CHĂM SÓC KỸ — hỏi thêm nhu cầu, gợi ý combo/sản phẩm phù hợp (upsell nhẹ) trước khi mời chốt.',
];

private function withClosingStyle(string $extra, Conversation $conv): string
{
    $settings = (array) (MessagingSetting::withoutGlobalScope(TenantScope::class)
        ->where('tenant_id', (int) $conv->tenant_id)->value('settings') ?? []);
    $style = (string) ($settings['sales_closing_style'] ?? 'default');
    $note = trim((string) ($settings['sales_closing_note'] ?? ''));

    $directive = self::CLOSING_STYLES[$style] ?? '';
    if ($note !== '') {
        $directive = trim($directive."\nGhi chú chốt sale của shop: ".$note);
    }
    if ($directive === '') {
        return $extra;
    }

    return $extra !== '' ? $extra."\n\n".$directive : $directive;
}
```

Nối vào chuỗi `$extra` ở **cả hai** chỗ (`draftAutoReply` ~L237 và `suggest` ~L299), bọc NGOÀI cùng để đứng sau business info:

```php
$extra = $this->withClosingStyle(
    $this->withBusinessInfo($this->withAdContext($this->withVisualContext($this->baseSystemExtra(), $conv, $tenantId, $providerCode, $provider?->default_model), $conv), $conv),
    $conv,
);
```

`MessagingSetting` + `TenantScope` đã được import trong file (đã dùng ở `resolveProviderCode`).

- [ ] **Step 4: Run — pass. Step 5: Pint + phpstan + commit.**

```bash
git add app/app/Modules/Messaging/Services/AiSuggestionService.php app/tests/Feature/Messaging/SalesClosingStylePromptTest.php
git commit -m "feat(sales): chèn directive phong cách chốt sale vào prompt (auto+suggest)"
```

**Ảnh hưởng & lỗi ngầm — HỆ CHỐT SALE:** (1) Directive chỉ THÊM vào `$extra`, đứng SAU persona "QUY TẮC CHỐT ĐƠN" nên STEER chứ không xoá quy tắc gốc; wording chọn kiểu bổ sung, tránh mâu thuẫn (không nói "đừng mời đặt"). (2) `default`/chưa cấu hình ⇒ trả `$extra` nguyên vẹn ⇒ 0 thay đổi cho shop hiện tại. (3) TUYỆT ĐỐI không đụng bước phân loại intent (classify KHÔNG nhận `$extra`; đã kiểm ở `IntentClassifier`) — test khẳng định directive chỉ vào reply. (4) Áp cho cả auto lẫn suggest để nhất quán.

---

### Task B3: FE — chọn phong cách chốt sale trong Cài đặt AI

**Files:**
- Modify: `resources/js/pages/MessagingSettingsPage.tsx`, `resources/js/lib/messagingConfig.tsx`

- [ ] **Step 1:** Thêm vào type `MessagingSettings`: `sales_closing_style?: string; sales_closing_note?: string;`.
- [ ] **Step 2:** UI: mục "Phong cách chốt sale" dùng `Radio.Group` (Mặc định / Tư vấn nhẹ nhàng / Chốt nhanh / Khan hiếm-ưu đãi / Chăm sóc kỹ) + `Input.TextArea` ghi chú (tùy chọn, max 500). Lưu qua `useSaveMessagingSettings`.
- [ ] **Step 3:** `npm run lint && typecheck && build` PASS. **Step 4: Commit.**

```bash
git add app/resources/js
git commit -m "feat(sales): UI chọn phong cách chốt sale trong Cài đặt AI"
```

**Ảnh hưởng & lỗi ngầm:** Chỉ thêm field; không đụng field provider/auto-mode. Radio theo quy ước UI (không Select).

---

## Phase 2 / 3 (ngoài phạm vi lần này — ghi để nhớ)
- **P2:** Import file/URL vào form Kiến thức (tái dùng `DocumentTextExtractor`, set `source=url/upload`, `IndexKnowledgeItem` mở rộng nhánh trích xuất giống `IndexKnowledgeDoc`). Command migrate `AiKnowledgeDocument`→item; gỡ UI legacy. Mở rộng `messaging:kb-reindex` gồm item.
- **P3:** Phong cách chốt sale per-page/multi-page: thêm khoá `sales_closing_style` vào `messaging_account_meta.business_info`; `withClosingStyle` resolve page→shop→default.

## Rà soát cuối (self-review đã làm)
- Bao phủ spec: gộp KB (A1–A8), ảnh tùy chọn (item không ảnh vẫn ready — A5), text embed vector (A3/A5/A7), bỏ form RAG (A8), chốt sale toàn shop (B1–B3), chừa per-page (P3). ✔
- Không placeholder; code đủ ở mỗi step code. ✔
- Nhất quán tên: `indexItemChunks`, `IndexKnowledgeItem`, `ItemTextComposer`, `withClosingStyle`, `readyItemTitles`, hằng `KB_READY`/`CLOSING_STYLES`. ✔
- Điểm cần XÁC NHẬN trước khi code: luật phụ thuộc chéo module Messaging↔VisualSearch (A6/A7) — nếu cấm, chèn Contract port.

## Deploy
- Sau deploy: `php artisan migrate` (A1, A2). Không backfill. Item cũ (đã tạo trước) sẽ `kb_status=pending` → cần chạy `visualsearch:reindex` hoặc một lệnh re-index text (P2) HOẶC sửa/lưu lại để trigger `IndexKnowledgeItem`. Ghi chú cho vận hành.
