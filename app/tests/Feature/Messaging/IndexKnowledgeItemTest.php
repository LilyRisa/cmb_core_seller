<?php

namespace Tests\Feature\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IndexKnowledgeItem — chunk + index text của 1 mục tri thức hợp nhất (visual item),
 * đọc/ghi item CHỈ qua KnowledgeItemStore contract (luật module: Messaging không
 * chạm model VisualSearch).
 */
class IndexKnowledgeItemTest extends TestCase
{
    use RefreshDatabase;

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
}
