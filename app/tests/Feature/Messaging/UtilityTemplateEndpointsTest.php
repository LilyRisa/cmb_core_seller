<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC-0032 — CRUD + submit/sync utility template; cô lập theo tenant.
 */
class UtilityTemplateEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activate(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'shop_name' => 'My Page',
            'status' => 'active', 'messaging_enabled' => true, 'access_token' => 'TOK',
        ]);

        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activate(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    public function test_create_submit_sync_lifecycle(): void
    {
        // Http::fake gộp stub theo thứ tự → dùng 1 closure phân biệt create (POST) vs sync (GET).
        Http::fake(function ($request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/message_templates')) {
                return Http::response(['id' => 'tpl_ext_9', 'status' => 'PENDING'], 200);
            }

            return Http::response(['status' => 'APPROVED'], 200);
        });

        // Tạo draft.
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/utility-templates', [
                'channel_account_id' => $this->account->id,
                'code' => 'order_confirmation',
                'name' => 'Xác nhận đơn',
                'language' => 'vi',
                'body' => 'Đơn {{1}} đã xác nhận. Tra cứu: {{2}}',
                'variables' => ['order_number', 'tracking_url'],
            ])->assertCreated();

        $id = $res->json('data.id');
        $this->assertSame('draft', $res->json('data.status'));

        // Submit → Meta trả id + PENDING.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/utility-templates/{$id}/submit")
            ->assertOk()->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.external_template_id', 'tpl_ext_9');

        // Sync → Meta trả APPROVED.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/utility-templates/{$id}/sync")
            ->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_tenant_isolation(): void
    {
        $other = Tenant::create(['name' => 'OtherShop']);
        $otherAccount = ChannelAccount::query()->create([
            'tenant_id' => $other->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_X', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        UtilityTemplate::query()->create([
            'tenant_id' => $other->getKey(), 'channel_account_id' => $otherAccount->id,
            'code' => 'order_confirmation', 'name' => 'X', 'language' => 'vi',
            'body' => '{{1}}', 'status' => UtilityTemplate::STATUS_APPROVED, 'enabled' => true,
        ]);

        // Tenant hiện tại không thấy template của tenant khác.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/utility-templates')
            ->assertOk()->assertJsonCount(0, 'data');
    }
}
