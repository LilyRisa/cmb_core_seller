<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\SendCommentReply;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRun;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Auto-reply cho COMMENT (bình luận): rule khai báo `thread_types=['comment']`
 * fire qua AutoReplyEngine → dispatch SendCommentReply theo đích (công khai /
 * nhắn riêng). Gating thread_type: rule DM cũ KHÔNG vô tình đăng công khai.
 */
class MessagingCommentAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'CommentShop']);
        $this->tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'page_1',
            'shop_name' => 'Page 1',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    private function rule(array $attrs): AutoReplyRule
    {
        return AutoReplyRule::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'r',
            'enabled' => true,
            'cooldown_seconds' => 0,
            'priority' => 100,
        ], $attrs));
    }

    private function commentConversation(string $ext): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'thread_type' => Conversation::THREAD_COMMENT,
            'external_conversation_id' => $ext,
            'buyer_external_id' => 'commenter_1',
            'status' => Conversation::STATUS_OPEN,
            'message_count' => 1,
            'meta' => ['fb_comment_id' => 'fbc_'.$ext],
        ]);
    }

    private function outboundCount(int $convId): int
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $convId)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->count();
    }

    public function test_comment_any_rule_dispatches_public_reply(): void
    {
        $rule = $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_COMMENT_ANY,
            'filter' => ['thread_types' => ['comment']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Cảm ơn bạn đã bình luận!', 'comment_target' => ['public' => true, 'private' => false]],
        ]);

        $conv = $this->commentConversation('cmt1');
        app(AutoReplyEngine::class)->fire($conv->fresh(), AutoReplyRule::TRIGGER_COMMENT_ANY, [
            'inbound_body' => 'sản phẩm còn hàng không shop',
            'external_message_id' => 'x1',
        ]);

        Queue::assertPushed(SendCommentReply::class, fn (SendCommentReply $job) => $job->conversationId === $conv->id
            && $job->public === true
            && $job->private === false
            && str_contains($job->body, 'Cảm ơn'));

        // Comment KHÔNG đi đường DM (queueText) ⇒ không tạo Message outbound đồng bộ.
        $this->assertSame(0, $this->outboundCount($conv->id));
        $this->assertDatabaseHas('auto_reply_runs', [
            'rule_id' => $rule->id, 'conversation_id' => $conv->id, 'status' => AutoReplyRun::STATUS_FIRED,
        ]);
    }

    public function test_comment_rule_private_target(): void
    {
        $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_COMMENT_ANY,
            'filter' => ['thread_types' => ['comment']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Mình nhắn riêng nhé', 'comment_target' => ['public' => false, 'private' => true]],
        ]);

        $conv = $this->commentConversation('cmt2');
        app(AutoReplyEngine::class)->fire($conv->fresh(), AutoReplyRule::TRIGGER_COMMENT_ANY, [
            'inbound_body' => 'cho mình hỏi giá',
            'external_message_id' => 'p1',
        ]);

        Queue::assertPushed(SendCommentReply::class, fn (SendCommentReply $job) => $job->public === false && $job->private === true);
    }

    public function test_dm_only_rule_does_not_fire_on_comment(): void
    {
        // Rule không khai báo thread_types ⇒ chỉ áp DM; comment KHÔNG được trả lời tự động.
        $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_FIRST_MESSAGE,
            'action' => ['kind' => 'raw', 'raw_text' => 'Chào DM'],
        ]);

        $conv = $this->commentConversation('cmt3');
        app(AutoReplyEngine::class)->fire($conv->fresh(), AutoReplyRule::TRIGGER_FIRST_MESSAGE, ['inbound_body' => 'hello']);

        Queue::assertNotPushed(SendCommentReply::class);
        $this->assertSame(0, $this->outboundCount($conv->id));
    }

    public function test_comment_rule_does_not_fire_on_dm(): void
    {
        // Rule chỉ cho comment ⇒ KHÔNG áp cho hội thoại tin nhắn (DM).
        $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_FIRST_MESSAGE,
            'filter' => ['thread_types' => ['comment']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Chào bình luận', 'comment_target' => ['public' => true]],
        ]);

        $dm = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'dm1',
            'buyer_external_id' => 'b',
            'status' => Conversation::STATUS_OPEN,
            'message_count' => 1,
        ]);
        app(AutoReplyEngine::class)->fire($dm->fresh(), AutoReplyRule::TRIGGER_FIRST_MESSAGE, ['inbound_body' => 'hi']);

        Queue::assertNotPushed(SendCommentReply::class);
        $this->assertSame(0, $this->outboundCount($dm->id));
    }
}
