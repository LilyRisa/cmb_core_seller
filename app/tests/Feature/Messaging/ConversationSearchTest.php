<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `GET /messaging/conversations?q=...` — thanh tìm kiếm hội thoại.
 * Tìm theo tên người nhắn, số điện thoại phát hiện, và NỘI DUNG tin nhắn (lịch sử).
 * Kết quả sắp xếp mới→cũ; khớp trong tin cũ trả kèm `match_snippet`.
 */
class ConversationSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $fbAccount;

    private ChannelAccount $zaloAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'SearchShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        $this->fbAccount = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_S', 'shop_name' => 'Trang S',
            'status' => 'active', 'messaging_enabled' => true,
        ]);
        $this->zaloAccount = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'zalo_oa',
            'external_shop_id' => 'OA_S', 'shop_name' => 'OA S',
            'status' => 'active', 'messaging_enabled' => true,
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

    private function seedConversation(array $attrs): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->fbAccount->id,
            'provider' => 'facebook_page',
            'external_conversation_id' => 'conv_'.uniqid(),
            'buyer_external_id' => 'buyer_'.uniqid(),
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
        ], $attrs));
    }

    private function seedMessage(Conversation $conv, string $body): Message
    {
        return Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->getKey(),
            'external_message_id' => 'msg_'.uniqid(),
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => $body,
            'delivery_status' => Message::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function test_search_by_buyer_name(): void
    {
        $match = $this->seedConversation(['buyer_name' => 'Nguyễn Văn An']);
        $this->seedConversation(['buyer_name' => 'Trần Thị Bình']);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?q=Văn An')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_by_detected_phone(): void
    {
        $match = $this->seedConversation([
            'buyer_name' => 'Khách lẻ', 'has_phone' => true, 'detected_phone' => '0912345678',
        ]);
        $this->seedConversation(['buyer_name' => 'Khách khác']);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?q=091234')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_by_message_body_returns_conversation_with_snippet(): void
    {
        $conv = $this->seedConversation([
            'buyer_name' => 'Người mua', 'last_message_preview' => 'cảm ơn shop nhé',
        ]);
        // Từ khoá chỉ nằm trong tin CŨ, không nằm ở tên/preview.
        $this->seedMessage($conv, 'Cho mình hỏi mã giảm giá SIEUKHUYENMAI còn dùng được không?');

        $this->seedConversation(['buyer_name' => 'Người khác', 'last_message_preview' => 'ok bạn']);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?q=SIEUKHUYENMAI')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conv->id);

        $this->assertStringContainsString('SIEUKHUYENMAI', (string) $res->json('data.0.match_snippet'));
    }

    public function test_single_char_query_does_not_search_message_body(): void
    {
        $conv = $this->seedConversation(['buyer_name' => 'Zzz', 'last_message_preview' => 'zzz']);
        $this->seedMessage($conv, 'X marks the spot');

        // 'X' (1 ký tự) chỉ được match tên/phone/preview, KHÔNG quét body ⇒ không trả hội thoại này.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?q=X')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_is_scoped_to_provider_filter(): void
    {
        $fb = $this->seedConversation(['buyer_name' => 'Chung Ten', 'provider' => 'facebook_page', 'channel_account_id' => $this->fbAccount->id]);
        $this->seedConversation(['buyer_name' => 'Chung Ten', 'provider' => 'zalo_oa', 'channel_account_id' => $this->zaloAccount->id]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?provider=facebook_page&q=Chung Ten')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fb->id);
    }

    public function test_results_ordered_newest_first(): void
    {
        $older = $this->seedConversation(['buyer_name' => 'Chung Ten cu', 'last_message_at' => now()->subHour()]);
        $newer = $this->seedConversation(['buyer_name' => 'Chung Ten moi', 'last_message_at' => now()]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?q=Chung Ten')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }
}
