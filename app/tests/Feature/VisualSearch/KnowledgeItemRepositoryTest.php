<?php

namespace Tests\Feature\VisualSearch;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

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
}
