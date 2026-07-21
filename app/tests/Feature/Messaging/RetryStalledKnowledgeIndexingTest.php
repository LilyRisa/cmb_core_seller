<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Lưới an toàn: mục "Kiến thức" (visual item) có thể bị kẹt kb_status=pending/failed vĩnh viễn
 * nếu job IndexKnowledgeItem lần đầu không chạy được (vd race lúc deploy) — không có ai tự động
 * thử lại. AI RAG chỉ đọc mục kb_status=ready nên mục kẹt sẽ vô hình với AI mà không cảnh báo gì.
 */
class RetryStalledKnowledgeIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_redispatches_index_job_for_items_stalled_past_threshold(): void
    {
        Bus::fake([IndexKnowledgeItem::class]);

        $model = VisualTrainingItem::withoutGlobalScope(TenantScope::class);
        $stale = $model->create(['tenant_id' => 1, 'name' => 'D800', 'status' => 'active',
            'kb_status' => 'pending', 'applies_all_pages' => true]);
        $stale->forceFill(['updated_at' => now()->subMinutes(30)])->save();

        $fresh = $model->create(['tenant_id' => 1, 'name' => 'Vừa tạo', 'status' => 'active',
            'kb_status' => 'pending', 'applies_all_pages' => true]);

        $this->artisan('messaging:kb-retry-stalled', ['--minutes' => 15])->assertExitCode(0);

        Bus::assertDispatched(IndexKnowledgeItem::class, fn ($job) => $job->itemId === $stale->id);
        Bus::assertNotDispatched(IndexKnowledgeItem::class, fn ($job) => $job->itemId === $fresh->id);
    }

    public function test_no_stalled_items_dispatches_nothing(): void
    {
        Bus::fake([IndexKnowledgeItem::class]);

        $this->artisan('messaging:kb-retry-stalled')->assertExitCode(0);

        Bus::assertNotDispatched(IndexKnowledgeItem::class);
    }
}
