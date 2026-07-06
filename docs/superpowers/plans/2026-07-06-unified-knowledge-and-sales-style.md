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
- **LUẬT MODULE (đã chốt):** mọi phụ thuộc mới một chiều Messaging→VisualSearch qua **event + Contract** (không cycle). VisualSearch KHÔNG import mới từ Messaging. Không module nào tạo migration/đụng bảng module khác.

---

## Kiến trúc phụ thuộc module (ĐÃ CHỐT — event + Contract, acyclic)

> **Quyết định (2026-07-06):** Tôn trọng luật vàng `modules.md` Rule 2/4/8. Toàn bộ phụ thuộc mới **một chiều Messaging→VisualSearch** (không cycle). VisualSearch **KHÔNG** import gì mới từ Messaging.
>
> - **Ghi (index):** job `IndexKnowledgeItem` nằm ở **Messaging** (module sở hữu `ai_knowledge_chunks` + `KnowledgeVectorIndexer`). Nó đọc text + ghi trạng thái ngược item **qua contract** `VisualSearch\Contracts\KnowledgeItemStore` (interface). Không đụng model `VisualTrainingItem` trực tiếp.
> - **Kích hoạt:** VisualSearch **phát domain event** `KnowledgeItemSaved`/`KnowledgeItemDeleted` khi CRUD item. Messaging **đăng ký listener** (pattern đã có, giống listen `Orders\OrderStatusChanged`). VisualSearch không biết Messaging tồn tại.
> - **Đọc (retrieve):** `KnowledgeRetriever` (Messaging) lấy danh sách item READY **qua contract** `KnowledgeItemStore::readyTitles()` — không import `VisualTrainingItem`.
> - **Contract mới tách riêng** `KnowledgeItemStore` (KHÔNG nhồi vào `VisualItemSearch` để giữ tách bạch khớp-ảnh vs KB-text).

## File Structure (Part A — KB unification)

- Create migration `..._add_kb_columns_to_visual_training_items.php` trong `app/app/Modules/VisualSearch/Database/Migrations`; `..._add_visual_item_id_to_ai_knowledge_chunks.php` trong `app/app/Modules/Messaging/Database/Migrations` (mỗi module tự `loadMigrationsFrom`).
- Modify `app/app/Modules/VisualSearch/Models/VisualTrainingItem.php` — cột KB mới + hằng `KB_*`/`SOURCE_*`.
- Modify `app/app/Modules/Messaging/Models/AiKnowledgeChunk.php` — `visual_item_id` fillable.
- Create `app/app/Modules/VisualSearch/Services/ItemTextComposer.php` — dựng text nguồn từ item (thuần).
- Create `app/app/Modules/VisualSearch/DTO/KnowledgeItemText.php` — DTO `{itemId, tenantId, text}` (cross-module cho phép truyền DTO).
- Create `app/app/Modules/VisualSearch/Contracts/KnowledgeItemStore.php` — cổng đọc/ghi KB item cho Messaging.
- Create `app/app/Modules/VisualSearch/Services/KnowledgeItemRepository.php` — impl `KnowledgeItemStore` (bind trong provider).
- Create `app/app/Modules/VisualSearch/Events/KnowledgeItemSaved.php`, `KnowledgeItemDeleted.php`.
- Modify `app/app/Modules/VisualSearch/VisualSearchServiceProvider.php` — bind contract.
- Modify `app/app/Modules/VisualSearch/Http/Controllers/TrainingItemController.php` — set `kb_status=pending` + phát event sau store/update; phát event delete trong destroy.
- Modify `app/app/Modules/Messaging/Services/KnowledgeVectorIndexer.php` — tách `upsertChunks()` dùng chung + thêm `indexItemChunks(int $itemId, int $tenantId, iterable $chunks)`.
- Create `app/app/Modules/Messaging/Jobs/IndexKnowledgeItem.php` — chunk + index text của item (đọc/ghi qua `KnowledgeItemStore`).
- Create `app/app/Modules/Messaging/Listeners/IndexVisualKnowledgeItem.php` + `PurgeVisualKnowledgeItem.php` — nghe event VisualSearch.
- Modify service provider Messaging (đăng ký listener theo pattern hiện có).
- Modify `app/app/Modules/Messaging/Services/KnowledgeRetriever.php` — gộp chunk item qua `KnowledgeItemStore::readyTitles()`.
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

### Task A3: VisualSearch — cổng KB (`KnowledgeItemStore` contract + `ItemTextComposer` + DTO + impl)

**Files:**
- Create: `app/app/Modules/VisualSearch/Services/ItemTextComposer.php`
- Create: `app/app/Modules/VisualSearch/DTO/KnowledgeItemText.php`
- Create: `app/app/Modules/VisualSearch/Contracts/KnowledgeItemStore.php`
- Create: `app/app/Modules/VisualSearch/Services/KnowledgeItemRepository.php`
- Modify: `app/app/Modules/VisualSearch/VisualSearchServiceProvider.php` (bind)
- Test: `app/tests/Feature/VisualSearch/ItemTextComposerTest.php`, `app/tests/Feature/VisualSearch/KnowledgeItemRepositoryTest.php`

**Interfaces:**
- Produces contract `KnowledgeItemStore`:
  - `textFor(int $itemId): ?KnowledgeItemText` — item không tồn tại ⇒ null; ngược lại DTO `{itemId, tenantId, text}` (text = `ItemTextComposer::compose`).
  - `readyTitles(int $tenantId, ?int $channelAccountId, ?string $provider): array<int,string>` — map itemId ⇒ name của item `kb_status=ready`, đúng scope provider/page (mirror `readyDocumentTitles`).
  - `markIndexed(int $itemId, int $chunkCount, ?string $embeddingModel): void` — set `kb_status=ready`, `chunk_count`, `kb_indexed_at=now`, `embedding_model`, tăng `embedding_version`.
  - `markFailed(int $itemId): void` — set `kb_status=failed`.
- `ItemTextComposer::compose(VisualTrainingItem $item): string` — ghép `name` + `ref_code` + `description` + `attributes` (key: value) + `content_text`, bỏ rỗng, trim.

- [ ] **Step 1: Test `ItemTextComposer`**

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

- [ ] **Step 2: Test `KnowledgeItemRepository` (impl contract) — readyTitles scope + writeback**

```php
public function test_ready_titles_scopes_by_provider_and_status(): void
{
    $ready = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'Áo thun', 'kb_status' => 'ready',
        'provider' => 'facebook_page', 'applies_all_pages' => true, 'status' => 'active']);
    // provider khác + chưa ready ⇒ loại
    \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'Zalo item', 'kb_status' => 'ready',
        'provider' => 'zalo_oa', 'applies_all_pages' => true, 'status' => 'active']);

    $store = app(\CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore::class);
    $titles = $store->readyTitles(1, null, 'facebook_page');

    $this->assertSame([$ready->id => 'Áo thun'], $titles);
}

public function test_mark_indexed_and_failed_writeback(): void
{
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'X', 'status' => 'active', 'applies_all_pages' => true]);
    $store = app(\CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore::class);

    $store->markIndexed($item->id, 3, 'text-embedding-3-small');
    $fresh = $item->fresh();
    $this->assertSame('ready', $fresh->kb_status);
    $this->assertSame(3, (int) $fresh->chunk_count);
    $this->assertNotNull($fresh->kb_indexed_at);

    $store->markFailed($item->id);
    $this->assertSame('failed', $item->fresh()->kb_status);
}

public function test_text_for_returns_null_for_missing_item(): void
{
    $store = app(\CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore::class);
    $this->assertNull($store->textFor(999999));
}
```

- [ ] **Step 3: Run — fail** (chưa có class).

- [ ] **Step 4: Implement**

`ItemTextComposer.php`:

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

`DTO/KnowledgeItemText.php`:

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/** Text nguồn của 1 mục tri thức để Messaging index (cross-module DTO). */
final class KnowledgeItemText
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $tenantId,
        public readonly string $text,
    ) {}
}
```

`Contracts/KnowledgeItemStore.php`:

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Contracts;

use CMBcoreSeller\Modules\VisualSearch\DTO\KnowledgeItemText;

/**
 * Cổng cho Messaging index/truy hồi text của mục tri thức hợp nhất (visual item).
 * Giữ VisualSearch sở hữu model — Messaging chỉ chạm qua interface (luật module).
 */
interface KnowledgeItemStore
{
    public function textFor(int $itemId): ?KnowledgeItemText;

    /** @return array<int,string> itemId ⇒ name (item kb_status=ready, đúng scope). */
    public function readyTitles(int $tenantId, ?int $channelAccountId, ?string $provider): array;

    public function markIndexed(int $itemId, int $chunkCount, ?string $embeddingModel): void;

    public function markFailed(int $itemId): void;
}
```

`Services/KnowledgeItemRepository.php`:

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use CMBcoreSeller\Modules\VisualSearch\DTO\KnowledgeItemText;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;

class KnowledgeItemRepository implements KnowledgeItemStore
{
    public function __construct(private ItemTextComposer $composer) {}

    public function textFor(int $itemId): ?KnowledgeItemText
    {
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->find($itemId);
        if (! $item) {
            return null;
        }

        return new KnowledgeItemText((int) $item->id, (int) $item->tenant_id, $this->composer->compose($item));
    }

    public function readyTitles(int $tenantId, ?int $channelAccountId, ?string $provider): array
    {
        $q = VisualTrainingItem::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('kb_status', VisualTrainingItem::KB_READY);
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

        return $q->pluck('name', 'id')->all();
    }

    public function markIndexed(int $itemId, int $chunkCount, ?string $embeddingModel): void
    {
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->find($itemId);
        if (! $item) {
            return;
        }
        $item->forceFill([
            'kb_status' => VisualTrainingItem::KB_READY,
            'chunk_count' => $chunkCount,
            'kb_indexed_at' => now(),
            'embedding_model' => $embeddingModel,
            'embedding_version' => (int) $item->embedding_version + 1,
        ])->save();
    }

    public function markFailed(int $itemId): void
    {
        VisualTrainingItem::withoutGlobalScope(TenantScope::class)
            ->where('id', $itemId)
            ->update(['kb_status' => VisualTrainingItem::KB_FAILED]);
    }
}
```

Bind trong `VisualSearchServiceProvider::register()`:

```php
$this->app->bind(\CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore::class,
    \CMBcoreSeller\Modules\VisualSearch\Services\KnowledgeItemRepository::class);
```

- [ ] **Step 5: Run — pass. Step 6: Pint + phpstan + commit.**

```bash
cd app && vendor/bin/pint app/Modules/VisualSearch && vendor/bin/phpstan analyse app/Modules/VisualSearch/Services app/Modules/VisualSearch/Contracts
git add app/app/Modules/VisualSearch app/tests/Feature/VisualSearch/ItemTextComposerTest.php app/tests/Feature/VisualSearch/KnowledgeItemRepositoryTest.php
git commit -m "feat(kb): cổng KnowledgeItemStore + ItemTextComposer cho mục tri thức"
```

**Ảnh hưởng & lỗi ngầm:** Toàn bộ nằm trong VisualSearch (không đụng Messaging). `ItemTextComposer` thuần; `is_scalar` bỏ attribute lồng an toàn. `readyTitles` mirror `readyDocumentTitles` — cùng luật scope provider/page. Writeback qua contract để module khác (Messaging) không chạm model. Không đụng khớp ảnh CLIP.

---

### Task A4: `KnowledgeVectorIndexer` — tách `upsertChunks()` + `indexItemChunks()`

**Files:**
- Modify: `app/app/Modules/Messaging/Services/KnowledgeVectorIndexer.php`
- Test: `app/tests/Feature/Messaging/KnowledgeIndexingTest.php` (thêm ca item)

**Interfaces:**
- Produces: `indexItemChunks(int $itemId, int $tenantId, iterable $chunks): int` — embed + upsert Qdrant payload `{tenant_id, item_id}`, lưu embedding về chunk, trả số chunk đã vector hoá. **KHÔNG import `VisualTrainingItem`** (nhận primitive để giữ luật module). Private `upsertChunks(int $tenantId, iterable $chunks, array $extraPayload): int` dùng chung cho cả doc lẫn item.

- [ ] **Step 1: Test — index chunk của item ghi payload item_id + embedding**

```php
public function test_index_item_chunks_upserts_with_item_payload(): void
{
    $captured = [];
    $store = \Mockery::mock(\CMBcoreSeller\Integrations\Vector\Contracts\VectorStore::class);
    $store->shouldReceive('enabled')->andReturn(true);
    $store->shouldReceive('ensureCollection')->andReturn(null);
    $store->shouldReceive('upsert')->andReturnUsing(function ($col, $points) use (&$captured) { $captured = $points; });
    $this->app->instance(\CMBcoreSeller\Integrations\Vector\Contracts\VectorStore::class, $store);

    $indexer = \Mockery::mock(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class.'[embed]', [
        app(\CMBcoreSeller\Integrations\Ai\AiAssistantRegistry::class), $store,
    ]);
    $indexer->shouldAllowMockingProtectedMethods();
    $indexer->shouldReceive('embed')->andReturn([0.1, 0.2, 0.3]);

    $chunk = \CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::create([
        'tenant_id' => 1, 'visual_item_id' => 55, 'chunk_index' => 0,
        'chunk_text' => 'Áo thun cotton', 'embedding' => null, 'token_count' => 3,
    ]);

    $n = $indexer->indexItemChunks(55, 1, [$chunk]);

    $this->assertSame(1, $n);
    $this->assertSame(['tenant_id' => 1, 'item_id' => 55], $captured[0]['payload']);
    $this->assertNotNull($chunk->fresh()->embedding);
}
```

- [ ] **Step 2: Run test — fail** ("Method indexItemChunks does not exist").

- [ ] **Step 3: Refactor + implement.** Tách vòng lặp embed+upsert trong `indexChunks()` ra private `upsertChunks()`. `indexChunks()` (đường doc) giữ hành vi cũ y hệt (chỉ gọi helper):

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

/** Embed + upsert chunk của 1 visual item (tri thức hợp nhất). Payload item_id. Nhận primitive (luật module). */
public function indexItemChunks(int $itemId, int $tenantId, iterable $chunks): int
{
    if (! $this->store->enabled()) {
        return 0;
    }

    return $this->upsertChunks($tenantId, $chunks, ['item_id' => $itemId]);
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

Lưu ý: bookkeeping `embedding_model` của item do `KnowledgeItemStore::markIndexed()` (Task A3) ghi qua contract — indexer KHÔNG chạm model item. Public getter `model()` đã có để job đọc.

- [ ] **Step 4: Run tests — pass** (ca item mới + ca doc cũ).

Run: `cd app && php artisan test tests/Feature/Messaging/KnowledgeIndexingTest.php`

- [ ] **Step 5: Pint + phpstan + commit.**

```bash
cd app && vendor/bin/pint app/Modules/Messaging/Services/KnowledgeVectorIndexer.php && vendor/bin/phpstan analyse app/Modules/Messaging/Services/KnowledgeVectorIndexer.php
git add app/app/Modules/Messaging/Services/KnowledgeVectorIndexer.php app/tests/Feature/Messaging/KnowledgeIndexingTest.php
git commit -m "feat(kb): indexItemChunks — embed text visual item vào RAG"
```

**Ảnh hưởng & lỗi ngầm:** `indexChunks()` (đường doc) hành vi KHÔNG đổi — chỉ chuyển thân vòng lặp sang `upsertChunks()`; test regression doc phải PASS. Chung 1 collection `messaging_kb__<model>` (khác payload key `document_id` vs `item_id`) — search filter theo `tenant_id` nên cả hai cùng ra; tầng retriever phân biệt bằng chunk row. Không import `VisualTrainingItem` (giữ acyclic). Không đụng CLIP/visual embeddings (khác collection).

---

### Task A5: `IndexKnowledgeItem` job (Messaging) — chunk + index text của item qua contract

**Files:**
- Create: `app/app/Modules/Messaging/Jobs/IndexKnowledgeItem.php`
- Test: `app/tests/Feature/Messaging/IndexKnowledgeItemTest.php`

**Interfaces:**
- Consumes: `VisualSearch\Contracts\KnowledgeItemStore` (`textFor`, `markIndexed`, `markFailed`), `KnowledgeVectorIndexer` (`indexItemChunks`, `forget`, `model`).
- Produces: `IndexKnowledgeItem::dispatch(int $itemId)` (queue `messaging-ai`), tạo `ai_knowledge_chunks` (`visual_item_id`), gọi `markIndexed`/`markFailed` qua contract.

- [ ] **Step 1: Test**

```php
public function test_indexes_item_text_into_chunks_and_marks_ready(): void
{
    // VectorStore tắt ⇒ fail-soft: vẫn tạo chunk + markIndexed (embed bỏ qua như doc).
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'Bộ thu bluetooth', 'description' => 'Kết nối 5.0 HIFI',
        'status' => 'active', 'applies_all_pages' => true, 'source' => 'inline']);

    (new \CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem($item->id))->handle(
        app(\CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore::class),
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

    (new \CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem($item->id))->handle(
        app(\CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore::class),
        app(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class),
    );

    $this->assertSame('ready', $item->fresh()->kb_status);
    $this->assertSame(0, (int) $item->fresh()->chunk_count);
}
```

- [ ] **Step 2: Run — fail.**

- [ ] **Step 3: Implement**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Index text của 1 mục tri thức hợp nhất (visual item) cho RAG: dựng text (qua KnowledgeItemStore)
 * → chunk → ghi ai_knowledge_chunks(visual_item_id) → embed Qdrant (fail-soft) → markIndexed qua
 * contract. Ảnh nằm ở pipeline CLIP riêng (EmbedTrainingImage) — job này CHỈ lo phần text.
 * Đọc/ghi item QUA contract (luật module: Messaging không chạm model VisualSearch). Queue messaging-ai, tries 2.
 */
class IndexKnowledgeItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $itemId)
    {
        $this->onQueue('messaging-ai');
    }

    public function handle(KnowledgeItemStore $items, KnowledgeVectorIndexer $vectorIndexer): void
    {
        $source = $items->textFor($this->itemId);
        if ($source === null) {
            return; // item đã bị xoá
        }

        try {
            // Xoá chunk cũ + point Qdrant (re-index idempotent).
            $oldIds = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('visual_item_id', $this->itemId)->pluck('id')->all();
            $vectorIndexer->forget($oldIds);
            AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('visual_item_id', $this->itemId)->delete();

            $chunks = $this->chunk($source->text);
            $created = [];
            foreach ($chunks as $i => $chunkText) {
                $created[] = AiKnowledgeChunk::create([
                    'tenant_id' => $source->tenantId,
                    'visual_item_id' => $this->itemId,
                    'chunk_index' => $i,
                    'chunk_text' => $chunkText,
                    'embedding' => null,
                    'token_count' => (int) ceil(mb_strlen($chunkText) / 4),
                ]);
            }

            $vectorIndexer->indexItemChunks($this->itemId, $source->tenantId, $created);
            $items->markIndexed($this->itemId, count($chunks), $vectorIndexer->model());
        } catch (\Throwable $e) {
            $items->markFailed($this->itemId);
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
cd app && vendor/bin/pint app/Modules/Messaging/Jobs/IndexKnowledgeItem.php && vendor/bin/phpstan analyse app/Modules/Messaging/Jobs/IndexKnowledgeItem.php
git add app/app/Modules/Messaging/Jobs/IndexKnowledgeItem.php app/tests/Feature/Messaging/IndexKnowledgeItemTest.php
git commit -m "feat(kb): IndexKnowledgeItem chunk+embed text mục tri thức (qua contract)"
```

**Ảnh hưởng & lỗi ngầm:** Job nằm ở Messaging (sở hữu chunk + indexer), đọc/ghi item QUA `KnowledgeItemStore` — không import model VisualSearch ⇒ acyclic. Chỉ đụng chunk có `visual_item_id` đúng item (không chạm chunk document). Fail-soft giống doc: Qdrant tắt vẫn tạo chunk + markIndexed (retriever rơi keyword). `markIndexed` chạy SAU indexItemChunks nên `chunk_count` khớp. Phase 1 chưa xử `source=url/upload` (chỉ text) — để Phase 2.

---

### Task A6: Event VisualSearch + listener Messaging (kích hoạt (re)index / purge)

**Files:**
- Create: `app/app/Modules/VisualSearch/Events/KnowledgeItemSaved.php`, `KnowledgeItemDeleted.php`
- Modify: `app/app/Modules/VisualSearch/Http/Controllers/TrainingItemController.php`
- Create: `app/app/Modules/Messaging/Listeners/IndexVisualKnowledgeItem.php`, `PurgeVisualKnowledgeItem.php`
- Modify: service provider Messaging (đăng ký listener theo pattern hiện có)
- Test: `app/tests/Feature/VisualSearch/TrainingItemIndexHookTest.php`

**Interfaces:**
- VisualSearch phát `KnowledgeItemSaved(int $itemId)` sau store/update; `KnowledgeItemDeleted(int $itemId)` trong destroy (trước khi xoá item). Controller set `kb_status=pending` khi store/update.
- Messaging listen: `IndexVisualKnowledgeItem` (nghe `KnowledgeItemSaved`) → `IndexKnowledgeItem::dispatch`; `PurgeVisualKnowledgeItem` (nghe `KnowledgeItemDeleted`) → xoá chunk item + `KnowledgeVectorIndexer::forget`.

- [ ] **Step 1: Test (Event::fake khẳng định controller phát event; listener test riêng)**

```php
public function test_store_and_update_fire_saved_event(): void
{
    \Illuminate\Support\Facades\Event::fake([\CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved::class]);
    $owner = /* seed owner + tenant header như test TrainingItem khác */;

    $id = $this->actingAs($owner)->withHeaders($this->tenantHeader())
        ->postJson('/api/v1/visual-search/items', ['name' => 'Bộ thu bluetooth', 'description' => 'HIFI'])
        ->assertCreated()->json('data.id');

    $this->actingAs($owner)->withHeaders($this->tenantHeader())
        ->patchJson("/api/v1/visual-search/items/{$id}", ['description' => 'HIFI + AptX'])->assertOk();

    \Illuminate\Support\Facades\Event::assertDispatchedTimes(\CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved::class, 2);
}

public function test_listener_dispatches_index_job(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $item = \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem::withoutGlobalScope(
        \CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class
    )->create(['tenant_id' => 1, 'name' => 'X', 'status' => 'active', 'applies_all_pages' => true]);

    event(new \CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved($item->id));
    \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem::class);
}
```

(Tham chiếu `tests/Feature/VisualSearch/*` để lấy helper seed owner/tenant header.)

- [ ] **Step 2: Run — fail.**

- [ ] **Step 3: Implement**

Event (mẫu — cả 2 event cùng shape):

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Mục tri thức được tạo/sửa — Messaging nghe để (re)index text RAG. */
class KnowledgeItemSaved
{
    use Dispatchable;

    public function __construct(public int $itemId) {}
}
```

`KnowledgeItemDeleted` giống hệt (đổi tên + docblock "bị xoá — Messaging purge chunk/vector").

`TrainingItemController`:
- Cuối `store()` (sau `syncPages`, trước response): `$item->forceFill(['kb_status' => VisualTrainingItem::KB_PENDING])->save();` rồi `event(new KnowledgeItemSaved($item->id));`.
- Cuối `update()` (sau save/syncPages): `$item->forceFill(['kb_status' => VisualTrainingItem::KB_PENDING])->save();` rồi `event(new KnowledgeItemSaved($item->id));`.
- Trong `destroy()` (trước `$item->delete()`): `event(new KnowledgeItemDeleted($item->id));`.
- Thêm `use` cho 2 event class.

Listener Messaging `IndexVisualKnowledgeItem`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved;

class IndexVisualKnowledgeItem
{
    public function handle(KnowledgeItemSaved $event): void
    {
        IndexKnowledgeItem::dispatch($event->itemId);
    }
}
```

Listener `PurgeVisualKnowledgeItem` (nghe `KnowledgeItemDeleted`): xoá chunk + forget:

```php
public function handle(KnowledgeItemDeleted $event): void
{
    $ids = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
        ->where('visual_item_id', $event->itemId)->pluck('id')->all();
    app(KnowledgeVectorIndexer::class)->forget($ids);
    AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
        ->where('visual_item_id', $event->itemId)->delete();
}
```

Đăng ký listener theo pattern hiện có của Messaging (tìm nơi listen `Orders\OrderStatusChanged` hoặc event khác — có thể `Event::listen` trong `MessagingServiceProvider::boot()` hoặc mảng `$listen`). Mirror đúng nơi đó.

- [ ] **Step 4: Run — pass. Step 5: Pint + phpstan + commit.**

```bash
git add app/app/Modules/VisualSearch/Events app/app/Modules/VisualSearch/Http/Controllers/TrainingItemController.php app/app/Modules/Messaging/Listeners app/app/Modules/Messaging/MessagingServiceProvider.php app/tests/Feature/VisualSearch/TrainingItemIndexHookTest.php
git commit -m "feat(kb): CRUD mục tri thức phát event → Messaging (re)index/purge RAG"
```

**Ảnh hưởng & lỗi ngầm:** VisualSearch chỉ phát event (không biết Messaging) — acyclic. Messaging listen (pattern đã có, giống Orders event) + import VisualSearch\Events (cùng chiều, hợp lệ). Dispatch bất đồng bộ (queue `messaging-ai` — đã có supervisor); listener nếu queued PHẢI nằm trong Horizon supervisor (kiểm memory messaging-autoreply-dev-gotchas). Xoá item purge chunk+vector tránh rác RAG. Không đụng pipeline ảnh (EmbedTrainingImage vẫn chạy song song khi upload ảnh).

---

### Task A7: `KnowledgeRetriever` — gộp chunk item qua contract

**Files:**
- Modify: `app/app/Modules/Messaging/Services/KnowledgeRetriever.php`
- Test: `app/tests/Feature/Messaging/KnowledgeRetrieverItemScopeTest.php`

**Interfaces:**
- Consumes: `VisualSearch\Contracts\KnowledgeItemStore::readyTitles()` (inject vào constructor), `AiKnowledgeChunk.visual_item_id`.
- Produces: `retrieve()` trả về chunk từ CẢ document (cũ) lẫn item (mới), đúng scope page/provider; keyword fallback tương tự. **Không import `VisualTrainingItem`** (qua contract).

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

- [ ] **Step 3: Implement** — inject contract + gộp item:

Constructor: thêm `private KnowledgeItemStore $items` (đã có `private KnowledgeVectorIndexer $vector`). Sửa `retrieve()`:

```php
public function retrieve(int $tenantId, string $query, int $topK = 4, ?int $channelAccountId = null, ?string $provider = null): KnowledgeBase
{
    $readyDocIds = $this->readyDocumentTitles($tenantId, $channelAccountId, $provider);
    $readyItemIds = collect($this->items->readyTitles($tenantId, $channelAccountId, $provider));
    if ($readyDocIds->isEmpty() && $readyItemIds->isEmpty()) {
        return new KnowledgeBase(chunks: []);
    }
    $viaVector = $this->retrieveByVector($tenantId, $query, $topK, $readyDocIds, $readyItemIds);
    if ($viaVector !== null) {
        return $viaVector;
    }

    return $this->retrieveByKeyword($tenantId, $query, $topK, $readyDocIds, $readyItemIds);
}
```

`retrieveByVector`/`retrieveByKeyword` nhận thêm `Collection $readyItemIds`; đổi truy vấn chunk lọc CẢ hai nguồn và gán title theo nguồn:

```php
$chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
    ->where('tenant_id', $tenantId)
    ->whereIn('id', array_keys($scores))            // (vector) — keyword bỏ dòng này
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

`retrieveByKeyword` bỏ `whereIn('id', ...)` (quét toàn bộ chunk in-scope như cũ) nhưng thêm nhánh OR item. Import `use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;` (contract, KHÔNG model).

- [ ] **Step 4: Run — pass** (item mới + doc cũ). Chạy cả test doc scope cũ để chắc không vỡ.

Run: `cd app && php artisan test tests/Feature/Messaging/KnowledgeRetrieverItemScopeTest.php tests/Feature/Messaging/KnowledgeRetrieverPageScopeTest.php tests/Feature/Messaging/MessagingKnowledgeRagTest.php`
Expected: PASS.

- [ ] **Step 5: Pint + phpstan + commit.**

```bash
git add app/app/Modules/Messaging/Services/KnowledgeRetriever.php app/tests/Feature/Messaging/KnowledgeRetrieverItemScopeTest.php
git commit -m "feat(kb): retriever gộp chunk mục tri thức (item) cùng doc, qua contract"
```

**Ảnh hưởng & lỗi ngầm:** Đây là thay đổi RỦI RO CAO NHẤT (đụng RAG live). Bảo chứng: (1) doc path giữ nguyên điều kiện `document_id ∈ readyDocIds`; (2) item chỉ thêm qua nhánh OR — doc không set `visual_item_id` nên không lẫn; (3) scope provider/page cho item mirror doc (trong `KnowledgeItemStore::readyTitles`); (4) test regression doc PASS bắt buộc; (5) đọc item qua contract — Messaging KHÔNG import model VisualSearch ⇒ acyclic. `KnowledgeItemStore` bind sẵn (Task A3) nên DI resolve được trong test/CI.

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
- **P2:** Import file/URL vào form Kiến thức (tái dùng `DocumentTextExtractor`, set `source=url/upload`, `IndexKnowledgeItem`/`KnowledgeItemStore::textFor` mở rộng nhánh trích xuất giống `IndexKnowledgeDoc`). Command migrate `AiKnowledgeDocument`→item; gỡ UI legacy. Mở rộng `messaging:kb-reindex` gồm item.
- **P3:** Phong cách chốt sale per-page/multi-page: thêm khoá `sales_closing_style` vào `messaging_account_meta.business_info`; `withClosingStyle` resolve page→shop→default.

## Rà soát cuối (self-review đã làm)
- Bao phủ spec: gộp KB (A1–A8), ảnh tùy chọn (item không ảnh vẫn ready — A5), text embed vector (A4/A5/A7), bỏ form RAG (A8), chốt sale toàn shop (B1–B3), chừa per-page (P3). ✔
- Luật module: acyclic — Messaging→VisualSearch qua event + `KnowledgeItemStore` contract; VisualSearch không import mới từ Messaging. Không đụng bảng module khác (mỗi migration ở module sở hữu). ✔
- Nhất quán tên: `indexItemChunks`, `IndexKnowledgeItem`, `ItemTextComposer`, `KnowledgeItemStore`, `KnowledgeItemRepository`, `KnowledgeItemText`, `readyTitles`, `withClosingStyle`, hằng `KB_READY`/`CLOSING_STYLES`. ✔

## Deploy
- Sau deploy: `php artisan migrate` (A1, A2). Không backfill. Item cũ (đã tạo trước) sẽ `kb_status=pending` → cần chạy một lệnh re-index text (P2) HOẶC sửa/lưu lại để phát `KnowledgeItemSaved` → `IndexKnowledgeItem`. Ghi chú cho vận hành.
- **Qdrant prod: ĐÃ CÓ** (service `qdrant` trong `docker-compose.prod.yml`, `QDRANT_URL=http://qdrant:6333`). Collection text mới `messaging_kb__<model>` tự tạo khi index (khác `visual_training__*` của ảnh CLIP).
- **Vector RAG text BẬT/keyword** phụ thuộc endpoint embedding: `KnowledgeVectorIndexer` dùng `help_assistant.embedding_*` (DB `/admin/ai-support`) → fallback env `HELP_ASSISTANT_EMBEDDING_*` (mặc định TRỐNG trong compose prod). Đã cấu hình embedding cho Hỏi AI ⇒ messaging KB tự dùng lại (vector ngữ nghĩa). Trống ⇒ fail-soft: vẫn tạo chunk + `kb_status=ready`, retrieval rơi keyword. Feature chạy được cả hai đường.
- Listener A6 để ĐỒNG BỘ (không `ShouldQueue`) nên KHÔNG cần thêm Horizon supervisor. Job `IndexKnowledgeItem` chạy trên queue `messaging-ai` (đã có supervisor).
