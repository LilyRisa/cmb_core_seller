<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SPEC 0031 — tạo đơn trong khung chat FB ⇒ tự gửi tin xác nhận (kèm link tra cứu)
 * cho khách khi `link-order` mang cờ `notify_customer`.
 */
class OrderConfirmationOnLinkTest extends TestCase
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
        $this->activateSubscription(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'messaging_enabled' => true,
        ]);

        // facebook_page không bật trong env test ⇒ đăng ký connector vào registry thủ công.
        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);
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
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function seedConversation(string $threadType = Conversation::THREAD_MESSAGE): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page',
            'thread_type' => $threadType,
            'external_conversation_id' => 'PSID_123',
            'buyer_external_id' => 'PSID_123',
            'buyer_name' => 'Chị Mua',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
        ]);
    }

    private function seedOrder(): Order
    {
        return Order::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M260605-CONFIRM',
            'grand_total' => 150000, 'is_cod' => true,
        ]);
    }

    private function outboundMessages(int $conversationId): Collection
    {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->get();
    }

    public function test_sends_confirmation_with_tracking_link_and_button(): void
    {
        Queue::fake();
        $conv = $this->seedConversation();
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", [
                'order_id' => $order->id, 'notify_customer' => true,
            ])->assertOk();

        $messages = $this->outboundMessages($conv->id);
        $this->assertCount(1, $messages);

        $msg = $messages->first();
        $this->assertSame(Message::KIND_INTERACTIVE, $msg->kind);
        $this->assertStringContainsString('Xác nhận đơn đặt hàng', (string) $msg->body);
        $this->assertStringContainsString('/tracking?code=M260605-CONFIRM', (string) $msg->body);
        $this->assertSame('POST_PURCHASE_UPDATE', $msg->meta['message_tag'] ?? null);
        $this->assertSame('order_confirmation', $msg->meta['system_kind'] ?? null);
        $this->assertSame($order->id, $msg->meta['order_id'] ?? null);
        // Nút web_url trỏ tới link tra cứu.
        $this->assertStringContainsString('/tracking?code=M260605-CONFIRM', $msg->meta['interactive']['buttons'][0]['url'] ?? '');

        // Đánh dấu idempotency trên hội thoại + job gửi đã được đẩy.
        $this->assertContains($order->id, (array) ($conv->fresh()->meta['order_confirmation_order_ids'] ?? []));
        Queue::assertPushed(SendMessage::class);
    }

    public function test_idempotent_on_repeated_link(): void
    {
        Queue::fake();
        $conv = $this->seedConversation();
        $order = $this->seedOrder();

        $payload = ['order_id' => $order->id, 'notify_customer' => true];
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", $payload)->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", $payload)->assertOk();

        $this->assertCount(1, $this->outboundMessages($conv->id));
    }

    public function test_does_not_send_without_flag(): void
    {
        Queue::fake();
        $conv = $this->seedConversation();
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", ['order_id' => $order->id])
            ->assertOk();

        $this->assertCount(0, $this->outboundMessages($conv->id));
    }

    public function test_does_not_send_on_comment_thread(): void
    {
        Queue::fake();
        $conv = $this->seedConversation(Conversation::THREAD_COMMENT);
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", [
                'order_id' => $order->id, 'notify_customer' => true,
            ])->assertOk();

        $this->assertCount(0, $this->outboundMessages($conv->id));
    }
}
