<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kiểm thử kiểm duyệt comment Facebook:
 * ẩn / xoá / trả lời công khai / nhắn riêng.
 *
 * Setup: tenant + facebook_page channel account + comment conversation.
 * Http::fake() stub graph.facebook.com calls.
 */
class FacebookCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Register facebook_page connector so the registry resolves it in tests.
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.graph_version' => 'v19.0',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'CommentShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_COMMENT',
            'status' => 'active',
            'access_token' => 'test_page_token',
            'messaging_enabled' => true,
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

    private function commentConv(array $attrs = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page',
            'thread_type' => 'comment',
            'external_conversation_id' => 'comment_'.uniqid(),
            'buyer_external_id' => 'buyer_fb_123',
            'status' => 'open',
            'last_message_at' => now(),
            'meta' => [
                'fb_comment_id' => 'fb_comment_abc',
                'fb_post_id' => 'fb_post_xyz',
                'fb_post_permalink' => 'https://facebook.com/post/xyz',
                'fb_post_message' => 'Bài viết test',
            ],
        ], $attrs));
    }

    private function messagingConv(array $attrs = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page',
            'thread_type' => 'message',
            'external_conversation_id' => 'psid_'.uniqid(),
            'buyer_external_id' => 'psid_buyer',
            'status' => 'open',
            'last_message_at' => now(),
            'meta' => [],
        ], $attrs));
    }

    // -----------------------------------------------------------------------
    // hide
    // -----------------------------------------------------------------------

    public function test_hide_comment_sends_post_to_graph_and_updates_meta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/hide", ['hidden' => true])
            ->assertOk()
            ->assertJsonPath('data.comment.hidden', true);

        $conv->refresh();
        $this->assertTrue((bool) ($conv->meta['comment_hidden'] ?? false));

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'graph.facebook.com') &&
                $req->method() === 'POST' &&
                str_contains($req->url(), 'fb_comment_abc');
        });
    }

    public function test_unhide_comment_sets_meta_hidden_false(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $conv = $this->commentConv([
            'meta' => [
                'fb_comment_id' => 'fb_comment_abc',
                'fb_post_id' => 'fb_post_xyz',
                'fb_post_permalink' => 'https://facebook.com/post/xyz',
                'fb_post_message' => 'Bài viết test',
                'comment_hidden' => true,
            ],
        ]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/hide", ['hidden' => false])
            ->assertOk()
            ->assertJsonPath('data.comment.hidden', false);

        $conv->refresh();
        $this->assertFalse((bool) ($conv->meta['comment_hidden'] ?? true));
    }

    // -----------------------------------------------------------------------
    // destroy
    // -----------------------------------------------------------------------

    public function test_delete_comment_sends_delete_request_and_marks_spam(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$conv->id}/comment")
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $conv->refresh();
        $this->assertTrue((bool) ($conv->meta['comment_deleted'] ?? false));
        $this->assertSame(Conversation::STATUS_SPAM, $conv->status);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'graph.facebook.com') &&
                $req->method() === 'DELETE' &&
                str_contains($req->url(), 'fb_comment_abc');
        });
    }

    // -----------------------------------------------------------------------
    // reply
    // -----------------------------------------------------------------------

    public function test_reply_to_comment_posts_to_comments_endpoint_and_stores_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'new_comment_id_999'], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/reply", ['body' => 'Cảm ơn bạn!'])
            ->assertOk()
            ->assertJsonPath('data.direction', 'outbound')
            ->assertJsonPath('data.body', 'Cảm ơn bạn!');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'external_message_id' => 'new_comment_id_999',
            'direction' => 'outbound',
            'body' => 'Cảm ơn bạn!',
        ]);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'graph.facebook.com') &&
                $req->method() === 'POST' &&
                str_contains($req->url(), 'fb_comment_abc/comments');
        });
    }

    // -----------------------------------------------------------------------
    // privateReply
    // -----------------------------------------------------------------------

    public function test_private_reply_posts_to_me_messages_and_sets_meta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'buyer_fb_123'], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-reply", ['body' => 'Mời bạn inbox nhé!'])
            ->assertOk()
            ->assertJsonPath('data.comment.private_replied', true);

        $conv->refresh();
        $this->assertNotEmpty($conv->meta['private_replied_at'] ?? null);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'graph.facebook.com') &&
                $req->method() === 'POST' &&
                str_contains($req->url(), 'me/messages') &&
                isset($req->data()['recipient']['comment_id']);
        });
    }

    // -----------------------------------------------------------------------
    // 422 NOT_A_COMMENT
    // -----------------------------------------------------------------------

    public function test_hide_on_message_thread_returns_422_not_a_comment(): void
    {
        Http::fake();

        $conv = $this->messagingConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/hide", ['hidden' => true])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'NOT_A_COMMENT');
    }

    public function test_delete_on_message_thread_returns_422_not_a_comment(): void
    {
        Http::fake();

        $conv = $this->messagingConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$conv->id}/comment")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'NOT_A_COMMENT');
    }

    public function test_reply_on_message_thread_returns_422_not_a_comment(): void
    {
        Http::fake();

        $conv = $this->messagingConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/reply", ['body' => 'hi'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'NOT_A_COMMENT');
    }

    public function test_private_reply_on_message_thread_returns_422_not_a_comment(): void
    {
        Http::fake();

        $conv = $this->messagingConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-reply", ['body' => 'hi'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'NOT_A_COMMENT');
    }

    // -----------------------------------------------------------------------
    // 403 — insufficient permission
    // -----------------------------------------------------------------------

    public function test_viewer_cannot_hide_comment(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/hide", ['hidden' => true])
            ->assertStatus(403);
    }

    public function test_viewer_cannot_delete_comment(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$conv->id}/comment")
            ->assertStatus(403);
    }

    public function test_viewer_cannot_reply_to_comment(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/reply", ['body' => 'hi'])
            ->assertStatus(403);
    }

    public function test_viewer_cannot_private_reply(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-reply", ['body' => 'hi'])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------------
    // private-reply idempotent với lỗi 10900 (Activity already replied to)
    // -----------------------------------------------------------------------

    public function test_private_reply_swallows_10900_already_replied(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => '(#10900) Activity already replied to', 'code' => 10900],
            ], 400),
        ]);

        $conv = $this->commentConv();

        // Không ném 500 — coi như đã nhắn (idempotent).
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-reply", ['body' => 'Mời inbox'])
            ->assertOk()
            ->assertJsonPath('data.comment.private_replied', true);
    }

    // -----------------------------------------------------------------------
    // like
    // -----------------------------------------------------------------------

    public function test_like_comment_posts_to_likes_endpoint(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/like", ['comment_id' => 'fb_reply_1', 'like' => true])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.like', true);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'graph.facebook.com') &&
                $req->method() === 'POST' &&
                str_contains($req->url(), 'fb_reply_1/likes');
        });
    }

    public function test_unlike_comment_sends_delete_to_likes_endpoint(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/like", ['like' => false])
            ->assertOk();

        Http::assertSent(function ($req) {
            return $req->method() === 'DELETE' && str_contains($req->url(), 'fb_comment_abc/likes');
        });
    }

    public function test_viewer_cannot_like_comment(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/like", ['like' => true])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------------
    // private-message (modal — nhiều phần, lưu PSID)
    // -----------------------------------------------------------------------

    public function test_private_message_sends_via_comment_id_and_stores_psid(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'PSID_777', 'message_id' => 'mid.1'], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => 'Xin chào!'])
            ->assertOk()
            ->assertJsonPath('data.comment.private_replied', true);

        $conv->refresh();
        $this->assertSame('PSID_777', $conv->meta['fb_private_psid'] ?? null);
        $this->assertNotEmpty($conv->meta['private_replied_at'] ?? null);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'me/messages') &&
                isset($req->data()['recipient']['comment_id']);
        });
    }

    public function test_private_message_creates_dm_thread_with_outbound_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'PSID_777', 'message_id' => 'm_mid_1'], 200),
        ]);

        $conv = $this->commentConv(['buyer_name' => 'Khách A']);

        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => 'Xin chào!'])
            ->assertOk();

        // Hộp thoại DM được tạo, keyed theo PSID, thread_type=message.
        $dm = Conversation::query()
            ->where('channel_account_id', $this->account->id)
            ->where('external_conversation_id', 'PSID_777')
            ->where('thread_type', 'message')
            ->first();
        $this->assertNotNull($dm, 'DM conversation phải được tạo sau khi nhắn riêng');
        $this->assertSame('Khách A', $dm->buyer_name, 'kế thừa tên khách từ comment thread');

        // Tin outbound được ghi vào DM thread (mid thật để echo webhook dedupe).
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $dm->id,
            'external_message_id' => 'm_mid_1',
            'direction' => 'outbound',
            'body' => 'Xin chào!',
        ]);

        // Response trả id DM để FE điều hướng.
        $res->assertJsonPath('meta.dm_conversation_id', $dm->id);
    }

    public function test_private_message_uses_stored_psid_when_present(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'PSID_777', 'message_id' => 'mid.2'], 200),
        ]);

        $conv = $this->commentConv([
            'meta' => [
                'fb_comment_id' => 'fb_comment_abc',
                'fb_post_id' => 'fb_post_xyz',
                'fb_private_psid' => 'PSID_777',
            ],
        ]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => 'Tin nhắn tiếp'])
            ->assertOk();

        // Đã có PSID ⇒ gửi thẳng recipient.id (không dùng comment_id, tránh 10900).
        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'me/messages') &&
                ($req->data()['recipient']['id'] ?? null) === 'PSID_777';
        });
    }

    public function test_private_message_blocked_returns_422_when_nothing_delivered(): void
    {
        // Facebook trả 10900 "đã nhắn riêng" cho phần đầu (chưa có PSID) ⇒ delivered=0.
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => '(#10900) Activity already replied to', 'code' => 10900],
            ], 400),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => 'Xin chào'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'PRIVATE_REPLY_BLOCKED');
    }

    public function test_private_message_follow_up_uses_message_tag(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'PSID_777', 'message_id' => 'mid'], 200),
        ]);

        $conv = $this->commentConv([
            'meta' => ['fb_comment_id' => 'fb_comment_abc', 'fb_private_psid' => 'PSID_777'],
        ]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => 'Tin tiếp'])
            ->assertOk();

        // Gửi qua PSID đã lưu ⇒ phải kèm MESSAGE_TAG (private reply không mở cửa sổ 24h).
        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'me/messages') &&
                ($req->data()['messaging_type'] ?? null) === 'MESSAGE_TAG' &&
                ($req->data()['tag'] ?? null) === 'HUMAN_AGENT';
        });
    }

    public function test_like_permission_error_returns_422(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => '(#200) Requires pages_manage_engagement permission', 'code' => 200],
            ], 403),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/like", ['like' => true])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ENGAGEMENT_PERMISSION');
    }

    public function test_private_message_empty_returns_422(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => ''])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'EMPTY_REPLY');
    }

    public function test_viewer_cannot_private_message(): void
    {
        Http::fake();

        $conv = $this->commentConv();

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/comment/private-message", ['body' => 'hi'])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------------
    // delete comment con (không spam cả hội thoại)
    // -----------------------------------------------------------------------

    public function test_delete_child_comment_keeps_conversation_open(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $conv = $this->commentConv();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$conv->id}/comment", ['comment_id' => 'fb_child_reply_9'])
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $conv->refresh();
        $this->assertSame(Conversation::STATUS_OPEN, $conv->status);
        $this->assertArrayNotHasKey('comment_deleted', (array) $conv->meta);

        Http::assertSent(function ($req) {
            return $req->method() === 'DELETE' && str_contains($req->url(), 'fb_child_reply_9');
        });
    }
}
