<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeRetriever;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * KB unification (A7) — KnowledgeRetriever phải gộp chunk từ visual item (kho tri
 * thức hợp nhất), qua contract KnowledgeItemStore, cùng scope provider/page như doc.
 */
class KnowledgeRetrieverItemScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_retrieve_includes_item_chunks_via_keyword(): void
    {
        // Không có Qdrant ⇒ đi keyword. Tạo item ready + chunk.
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'name' => 'Bộ thu bluetooth', 'kb_status' => 'ready',
            'provider' => 'facebook_page', 'applies_all_pages' => true, 'status' => 'active',
        ]);
        AiKnowledgeChunk::create([
            'tenant_id' => 1, 'visual_item_id' => $item->id, 'chunk_index' => 0,
            'chunk_text' => 'Bộ thu bluetooth kết nối 5.0 HIFI', 'token_count' => 6,
        ]);

        $kb = app(KnowledgeRetriever::class)
            ->retrieve(1, 'bluetooth hifi', 4, null, 'facebook_page');

        $this->assertNotEmpty($kb->chunks);
        $this->assertSame('Bộ thu bluetooth', $kb->chunks[0]['title']);
    }

    public function test_item_chunk_excluded_when_provider_mismatch(): void
    {
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'name' => 'X', 'kb_status' => 'ready',
            'provider' => 'zalo_oa', 'applies_all_pages' => true, 'status' => 'active',
        ]);
        AiKnowledgeChunk::create([
            'tenant_id' => 1, 'visual_item_id' => $item->id, 'chunk_index' => 0,
            'chunk_text' => 'bluetooth hifi', 'token_count' => 2,
        ]);

        $kb = app(KnowledgeRetriever::class)
            ->retrieve(1, 'bluetooth hifi', 4, null, 'facebook_page');
        $this->assertEmpty($kb->chunks);
    }

    public function test_item_chunk_scoped_to_specific_page_via_pivot(): void
    {
        $pageX = ChannelAccount::query()->create([
            'tenant_id' => 1, 'provider' => 'facebook_page',
            'external_shop_id' => 'kb_item_x', 'shop_name' => 'kb_item_x',
            'status' => 'active', 'messaging_enabled' => true,
        ]);
        $pageY = ChannelAccount::query()->create([
            'tenant_id' => 1, 'provider' => 'facebook_page',
            'external_shop_id' => 'kb_item_y', 'shop_name' => 'kb_item_y',
            'status' => 'active', 'messaging_enabled' => true,
        ]);

        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'name' => 'Loa kéo mini', 'kb_status' => 'ready',
            'provider' => 'facebook_page', 'applies_all_pages' => false, 'status' => 'active',
        ]);
        $item->pages()->attach($pageX->getKey(), ['tenant_id' => 1]);
        AiKnowledgeChunk::create([
            'tenant_id' => 1, 'visual_item_id' => $item->id, 'chunk_index' => 0,
            'chunk_text' => 'loa kéo mini công suất lớn', 'token_count' => 5,
        ]);

        $forX = app(KnowledgeRetriever::class)
            ->retrieve(1, 'loa kéo mini', 4, (int) $pageX->getKey(), 'facebook_page');
        $forY = app(KnowledgeRetriever::class)
            ->retrieve(1, 'loa kéo mini', 4, (int) $pageY->getKey(), 'facebook_page');

        $this->assertNotEmpty($forX->chunks, 'item gán page X phải được truy hồi cho page X');
        $this->assertSame('Loa kéo mini', $forX->chunks[0]['title']);
        $this->assertEmpty($forY->chunks, 'item gán page X KHÔNG được rò sang page Y');
    }
}
