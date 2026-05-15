<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Console\CheckOverQuotaCommand;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * SPEC 0020 — middleware `plan.over_quota_lock` chặn write sau 2 ngày grace.
 *
 * Flow:
 *   1. Tạo tenant ở plan Starter (limit 2 channels), gắn 4 channels (vượt mức).
 *   2. Chạy scheduler `subscriptions:check-over-quota` ⇒ set `over_quota_warned_at`.
 *   3. Test trong grace: POST tới `/api/v1/orders` vẫn OK (chưa quá 48h).
 *   4. Forward time vượt 48h ⇒ POST trả 402 PLAN_QUOTA_EXCEEDED.
 *   5. Whitelist: GET /orders vẫn 200; DELETE channel-accounts/{id} vẫn 200.
 *   6. Gỡ bớt channel ⇒ scheduler clear timer ⇒ POST OK trở lại.
 */
class OverQuotaLockTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'OverQuotaShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateStarter(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_STARTER)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    private function addChannels(int $n): array
    {
        $ids = [];
        for ($i = 0; $i < $n; $i++) {
            $a = ChannelAccount::query()->create([
                'tenant_id' => $this->tenant->getKey(),
                'provider' => 'tiktok',
                'external_shop_id' => 'shop'.$i.uniqid(),
                'shop_name' => "Shop {$i}",
                'status' => ChannelAccount::STATUS_ACTIVE,
            ]);
            $ids[] = (int) $a->getKey();
        }

        return $ids;
    }

    public function test_check_over_quota_sets_timer_when_over(): void
    {
        $this->activateStarter();
        $this->addChannels(4); // Starter limit=2 ⇒ over

        Artisan::call(CheckOverQuotaCommand::class);

        $sub = Subscription::query()->withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        $this->assertNotNull($sub->over_quota_warned_at, 'Should set over_quota_warned_at when over.');
    }

    public function test_over_quota_within_grace_does_not_block_writes(): void
    {
        $this->activateStarter();
        $this->addChannels(4);
        // Warn 12h ago — vẫn trong grace 48h.
        $sub = Subscription::query()->withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        $sub->forceFill(['over_quota_warned_at' => now()->subHours(12)])->save();

        // POST orders (write) — không bị chặn middleware over_quota.
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/orders', ['buyer' => ['name' => 'X'], 'items' => []]);
        $this->assertNotSame(402, $resp->status(), 'Middleware không nên chặn khi còn trong grace.');
    }

    public function test_over_quota_past_grace_blocks_writes_but_allows_reads_and_self_remove(): void
    {
        $this->activateStarter();
        $ids = $this->addChannels(4);
        $sub = Subscription::query()->withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        // Quá grace 48h.
        $sub->forceFill(['over_quota_warned_at' => now()->subHours(72)])->save();

        // GET vẫn OK (whitelist read methods).
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders')
            ->assertOk();

        // POST bị chặn ⇒ 402.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/orders', ['buyer' => ['name' => 'X'], 'items' => []])
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_QUOTA_EXCEEDED');

        // DELETE channel-accounts/{id} vẫn được (user thoát khoá bằng cách gỡ kênh thừa).
        $caId = $ids[0];
        $account = ChannelAccount::query()->find($caId);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->deleteJson("/api/v1/channel-accounts/{$caId}", ['confirm' => $account->effectiveName()])
            ->assertOk();

        // /billing/usage vẫn OK (user xem hạn mức để quyết định nâng cấp).
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/usage')
            ->assertOk();
    }

    public function test_check_over_quota_clears_timer_when_no_longer_over(): void
    {
        $this->activateStarter();
        $this->addChannels(1); // 1 < 2, OK
        $sub = Subscription::query()->withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        // Pre-existing timer (vd: trước đó từng over).
        $sub->forceFill(['over_quota_warned_at' => now()->subHours(72)])->save();

        Artisan::call(CheckOverQuotaCommand::class);

        $sub->refresh();
        $this->assertNull($sub->over_quota_warned_at, 'Should clear timer when no longer over.');
    }
}
