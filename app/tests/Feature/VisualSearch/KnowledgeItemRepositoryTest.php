<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_titles_scopes_by_provider_and_status(): void
    {
        $ready = VisualTrainingItem::withoutGlobalScope(
            TenantScope::class
        )->create(['tenant_id' => 1, 'name' => 'Áo thun', 'kb_status' => 'ready',
            'provider' => 'facebook_page', 'applies_all_pages' => true, 'status' => 'active']);
        // provider khác + chưa ready ⇒ loại
        VisualTrainingItem::withoutGlobalScope(
            TenantScope::class
        )->create(['tenant_id' => 1, 'name' => 'Zalo item', 'kb_status' => 'ready',
            'provider' => 'zalo_oa', 'applies_all_pages' => true, 'status' => 'active']);

        $store = app(KnowledgeItemStore::class);
        $titles = $store->readyTitles(1, null, 'facebook_page');

        $this->assertSame([$ready->id => 'Áo thun'], $titles);
    }

    public function test_mark_indexed_and_failed_writeback(): void
    {
        $item = VisualTrainingItem::withoutGlobalScope(
            TenantScope::class
        )->create(['tenant_id' => 1, 'name' => 'X', 'status' => 'active', 'applies_all_pages' => true]);
        $store = app(KnowledgeItemStore::class);

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
        $store = app(KnowledgeItemStore::class);
        $this->assertNull($store->textFor(999999));
    }

    public function test_stalled_ids_returns_only_old_pending_or_failed_non_deleted_items(): void
    {
        $model = VisualTrainingItem::withoutGlobalScope(
            TenantScope::class
        );

        $stalePending = $model->create(['tenant_id' => 1, 'name' => 'Old pending', 'status' => 'active',
            'kb_status' => 'pending', 'applies_all_pages' => true]);
        $stalePending->forceFill(['updated_at' => now()->subMinutes(30)])->save();

        $staleFailed = $model->create(['tenant_id' => 1, 'name' => 'Old failed', 'status' => 'active',
            'kb_status' => 'failed', 'applies_all_pages' => true]);
        $staleFailed->forceFill(['updated_at' => now()->subMinutes(30)])->save();

        // Chưa đủ cũ ⇒ có thể job đang chạy dở, chưa nên retry.
        $freshPending = $model->create(['tenant_id' => 1, 'name' => 'Fresh pending', 'status' => 'active',
            'kb_status' => 'pending', 'applies_all_pages' => true]);

        // Đã ready ⇒ không liên quan.
        $staleReady = $model->create(['tenant_id' => 1, 'name' => 'Old ready', 'status' => 'active',
            'kb_status' => 'ready', 'applies_all_pages' => true]);
        $staleReady->forceFill(['updated_at' => now()->subMinutes(30)])->save();

        // Đã xoá mềm ⇒ bỏ qua dù pending cũ.
        $staleDeleted = $model->create(['tenant_id' => 1, 'name' => 'Old deleted', 'status' => 'active',
            'kb_status' => 'pending', 'applies_all_pages' => true]);
        $staleDeleted->forceFill(['updated_at' => now()->subMinutes(30)])->save();
        $staleDeleted->delete();

        $store = app(KnowledgeItemStore::class);
        $ids = $store->stalledIds(15);

        sort($ids);
        $expected = [$stalePending->id, $staleFailed->id];
        sort($expected);
        $this->assertSame($expected, $ids);
        $this->assertNotContains($freshPending->id, $ids);
        $this->assertNotContains($staleReady->id, $ids);
        $this->assertNotContains($staleDeleted->id, $ids);
    }
}
