<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Modules\Products\Models\ProductPushBatch;
use CMBcoreSeller\Modules\Products\Models\ProductPushJob;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushBatchModelTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'TestShop']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    public function test_recount_and_finish_sets_counts_and_marks_done(): void
    {
        /** @var ProductPushBatch $batch */
        $batch = ProductPushBatch::create([
            'type' => 'push',
            'total' => 2,
            'status' => 'running',
            'created_by' => 1,
        ]);

        $batch->jobs()->create([
            'tenant_id' => $this->tenant->getKey(),
            'listing_draft_id' => 1,
            'status' => 'success',
            'progress' => 100,
        ]);

        $batch->jobs()->create([
            'tenant_id' => $this->tenant->getKey(),
            'listing_draft_id' => 2,
            'status' => 'failed',
            'error' => ['msg' => 'x'],
            'progress' => 100,
            'step_label' => 'done',
        ]);

        $batch->recountAndFinish();

        $fresh = $batch->fresh();
        $this->assertSame(1, $fresh->succeeded);
        $this->assertSame(1, $fresh->failed);
        $this->assertSame('done', $fresh->status);
    }

    public function test_job_mark_updates_fields(): void
    {
        /** @var ProductPushBatch $batch */
        $batch = ProductPushBatch::create([
            'type' => 'push',
            'total' => 1,
            'status' => 'running',
        ]);

        /** @var ProductPushJob $job */
        $job = $batch->jobs()->create([
            'tenant_id' => $this->tenant->getKey(),
            'listing_draft_id' => 1,
        ]);

        $job->mark('success', 'upload', 100);

        $fresh = $job->fresh();
        $this->assertSame('success', $fresh->status);
        $this->assertSame('upload', $fresh->step_label);
        $this->assertSame(100, $fresh->progress);
        $this->assertNull($fresh->error);
    }

    public function test_job_mark_stores_error_array(): void
    {
        /** @var ProductPushBatch $batch */
        $batch = ProductPushBatch::create([
            'type' => 'push',
            'total' => 1,
            'status' => 'running',
        ]);

        /** @var ProductPushJob $job */
        $job = $batch->jobs()->create([
            'tenant_id' => $this->tenant->getKey(),
            'listing_draft_id' => 1,
        ]);

        $job->mark('failed', 'publish', 50, ['msg' => 'timeout']);

        $fresh = $job->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertSame(['msg' => 'timeout'], $fresh->error);
        $this->assertSame(50, $fresh->progress);
    }
}
