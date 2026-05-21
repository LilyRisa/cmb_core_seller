<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingBlockTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'BlockShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'status' => 'active', 'access_token' => 'T', 'messaging_enabled' => true,
        ]);
    }

    private function actor(Role $role = Role::Owner): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function conv(array $attrs = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_'.uniqid(),
            'buyer_external_id' => 'psid', 'status' => 'open', 'last_message_at' => now(),
        ], $attrs));
    }

    public function test_block_hides_conversation_and_unblock_restores(): void
    {
        $c = $this->conv(['buyer_name' => 'Spammer']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/block")->assertOk();
        $this->assertNotNull($c->fresh()->blocked_at);

        // hidden from default inbox
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')->assertOk()->assertJsonCount(0, 'data');
        // shown in blocked tab
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?blocked=true')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$c->id}/block")->assertOk();
        $this->assertNull($c->fresh()->blocked_at);
    }

    public function test_sending_to_blocked_conversation_returns_422(): void
    {
        $c = $this->conv(['blocked_at' => now()]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/messages", ['body' => 'hi'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CONVERSATION_BLOCKED');
    }

    /**
     * Role::Viewer does not have `messaging.reply` — expects 403.
     * (StaffCs has messaging.reply in this repo, so Viewer is used here instead.)
     */
    public function test_viewer_cannot_block(): void
    {
        $c = $this->conv();
        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/block")->assertStatus(403);
    }

    public function test_ingest_into_blocked_conversation_does_not_bump_unread(): void
    {
        $c = $this->conv(['blocked_at' => now(), 'unread_count' => 0]);

        app(MessageIngestionService::class)->ingest($this->account, new MessageDTO(
            externalConversationId: $c->external_conversation_id,
            externalMessageId: 'm_blocked_1',
            buyerExternalId: $c->buyer_external_id,
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'spam again',
        ));

        $c->refresh();
        $this->assertSame(0, (int) $c->unread_count); // not bumped for blocked
        $this->assertDatabaseHas('messages', ['external_message_id' => 'm_blocked_1']); // still stored (audit)
    }

    public function test_ingest_message_with_phone_sets_has_phone_flag(): void
    {
        $c = $this->conv(['has_phone' => false, 'detected_phone' => null]);

        app(MessageIngestionService::class)->ingest($this->account, new MessageDTO(
            externalConversationId: $c->external_conversation_id,
            externalMessageId: 'msg_phone_1',
            buyerExternalId: $c->buyer_external_id,
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'Anh ơi gọi mình số 0912345678 nhé',
        ));

        $c->refresh();
        $this->assertTrue((bool) $c->has_phone);
        $this->assertSame('0912345678', $c->detected_phone);
    }

    public function test_ingest_message_without_phone_leaves_has_phone_false(): void
    {
        $c = $this->conv(['has_phone' => false, 'detected_phone' => null]);

        app(MessageIngestionService::class)->ingest($this->account, new MessageDTO(
            externalConversationId: $c->external_conversation_id,
            externalMessageId: 'msg_nophone_1',
            buyerExternalId: $c->buyer_external_id,
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'Chào shop, cho hỏi có hàng không ạ?',
        ));

        $c->refresh();
        $this->assertFalse((bool) $c->has_phone);
        $this->assertNull($c->detected_phone);
    }

    public function test_detect_phones_command_backfills_existing_conversation(): void
    {
        // Tạo conversation has_phone=false và tin nhắn chứa SĐT
        $c = $this->conv(['has_phone' => false, 'detected_phone' => null]);
        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $c->id,
            'external_message_id' => 'backfill_msg_1',
            'direction' => 'inbound',
            'kind' => 'text',
            'body' => 'SĐT của mình là 0987654321',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->artisan('messaging:detect-phones')->assertExitCode(0);

        $c->refresh();
        $this->assertTrue((bool) $c->has_phone);
        $this->assertSame('0987654321', $c->detected_phone);
    }

    public function test_detect_phones_command_skips_conversation_already_flagged(): void
    {
        // Conversation đã có has_phone=true — command không động vào
        $c = $this->conv(['has_phone' => true, 'detected_phone' => '0911111111']);
        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $c->id,
            'external_message_id' => 'backfill_msg_2',
            'direction' => 'inbound',
            'kind' => 'text',
            'body' => 'số khác 0922222222',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->artisan('messaging:detect-phones')->assertExitCode(0);

        $c->refresh();
        // detected_phone không bị ghi đè
        $this->assertSame('0911111111', $c->detected_phone);
    }
}
