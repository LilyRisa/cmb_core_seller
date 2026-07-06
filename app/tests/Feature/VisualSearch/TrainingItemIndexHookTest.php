<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * A6: VisualSearch CRUD phát event (KnowledgeItemSaved/KnowledgeItemDeleted) — Messaging
 * lắng nghe (đồng bộ) để dispatch job (re)index / purge chunk RAG. Acyclic seam.
 */
class TrainingItemIndexHookTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'KbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function tenantHeader(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_store_and_update_fire_saved_event(): void
    {
        Event::fake([KnowledgeItemSaved::class]);

        $id = $this->actingAs($this->owner)->withHeaders($this->tenantHeader())
            ->postJson('/api/v1/visual-search/items', ['name' => 'Bộ thu bluetooth', 'description' => 'HIFI'])
            ->assertCreated()->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->tenantHeader())
            ->patchJson("/api/v1/visual-search/items/{$id}", ['description' => 'HIFI + AptX'])->assertOk();

        Event::assertDispatchedTimes(KnowledgeItemSaved::class, 2);
    }

    public function test_listener_dispatches_index_job(): void
    {
        Queue::fake();
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)
            ->create(['tenant_id' => 1, 'name' => 'X', 'status' => 'active', 'applies_all_pages' => true]);

        event(new KnowledgeItemSaved($item->id));

        Queue::assertPushed(IndexKnowledgeItem::class);
    }
}
