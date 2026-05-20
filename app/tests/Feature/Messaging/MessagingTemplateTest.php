<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test Template CRUD + send-template resolve (SPEC-0024 S3).
 *
 * Scenarios:
 *   - Owner CRUD template; vars tự suy từ body.
 *   - Code trùng (per tenant) ⇒ 422.
 *   - staff_order (messaging.view nhưng KHÔNG template.manage) ⇒ list OK, create 403.
 *   - staff_warehouse (không messaging.*) ⇒ 403.
 *   - send-template ⇒ body resolve `{{buyer.name}}` đúng + meta.template_id.
 */
class MessagingTemplateTest extends TestCase
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
        $this->tenant = Tenant::create(['name' => 'TplShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_tpl_1',
            'shop_name' => 'Tpl Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
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

    private function member(Role $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    public function test_owner_creates_template_and_vars_auto_derived(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/templates', [
                'code' => 'thanks',
                'name' => 'Cảm ơn',
                'body' => 'Cảm ơn {{customer.name}} đã mua đơn {{order.code}}',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'thanks')
            ->assertJsonPath('data.vars', ['customer.name', 'order.code']);

        $this->assertSame(1, MessageTemplate::query()->where('code', 'thanks')->count());
    }

    public function test_duplicate_code_per_tenant_rejected(): void
    {
        $payload = ['code' => 'dup', 'name' => 'A', 'body' => 'x'];
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/templates', $payload)->assertStatus(201);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/templates', $payload)->assertStatus(422);
    }

    public function test_update_body_rederives_vars(): void
    {
        $tpl = MessageTemplate::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'code' => 'greet', 'name' => 'Chào', 'body' => 'Chào {{a}}', 'vars' => ['a'],
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/templates/{$tpl->id}", ['body' => 'Chào {{x}} và {{y}}'])
            ->assertOk()
            ->assertJsonPath('data.vars', ['x', 'y']);
    }

    public function test_delete_is_soft(): void
    {
        $tpl = MessageTemplate::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'code' => 'bye', 'name' => 'Bye', 'body' => 'bye',
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/templates/{$tpl->id}")->assertOk();

        $this->assertSoftDeleted('message_templates', ['id' => $tpl->id]);
    }

    public function test_staff_order_can_list_but_not_create(): void
    {
        MessageTemplate::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'code' => 'a', 'name' => 'A', 'body' => 'x',
        ]);
        $so = $this->member(Role::StaffOrder);

        $this->actingAs($so)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/templates')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($so)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/templates', ['code' => 'b', 'name' => 'B', 'body' => 'y'])
            ->assertStatus(403);
    }

    public function test_staff_warehouse_cannot_view_templates(): void
    {
        $sw = $this->member(Role::StaffWarehouse);
        $this->actingAs($sw)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/templates')->assertStatus(403);
    }

    public function test_send_template_resolves_body(): void
    {
        Queue::fake();

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_tpl_1',
            'buyer_external_id' => 'buyer_1',
            'buyer_name' => 'Anh Khách',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(2),
        ]);

        $tpl = MessageTemplate::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'code' => 'hi', 'name' => 'Hi', 'body' => 'Chào {{buyer.name}}, shop {{shop.name}}',
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/messages/template", [
                'template_id' => $tpl->id,
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.body', 'Chào Anh Khách, shop Tpl Shop');

        $msg = Message::query()->where('conversation_id', $conv->id)->first();
        $this->assertSame($tpl->id, $msg->meta['template_id'] ?? null);
    }
}
