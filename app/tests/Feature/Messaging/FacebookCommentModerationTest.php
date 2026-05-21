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
}
