<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cho migration 2026_07_06_100002: `document_id` phải nullable
 * trên CẢ SQLite (test driver) lẫn Postgres, vì chunk của visual item
 * (kho tri thức gộp) không có document_id — chỉ có visual_item_id.
 */
class ChunkNullableDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_chunk_can_persist_with_null_document_id_and_visual_item_id(): void
    {
        $chunk = AiKnowledgeChunk::create([
            'tenant_id' => 1,
            'document_id' => null,
            'visual_item_id' => 42,
            'chunk_index' => 0,
            'chunk_text' => 'Mô tả ảnh sản phẩm — chunk thuộc visual item, không thuộc document.',
            'token_count' => 10,
        ]);

        $this->assertTrue($chunk->exists);
        $this->assertDatabaseHas('ai_knowledge_chunks', [
            'id' => $chunk->id,
            'document_id' => null,
            'visual_item_id' => 42,
        ]);
    }
}
