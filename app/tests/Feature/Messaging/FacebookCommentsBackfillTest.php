<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillFacebookComments;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests cho nền đồng bộ Facebook Page Comments vào messaging inbox.
 *
 * Covers:
 *  1. Schema — thread_type column exists
 *  2. fetchCommentThreads — unit-style (Http::fake), normalize đúng shape,
 *     bỏ comment của page, tập hợp replies
 *  3. BackfillFacebookComments job — upsert conversation (thread_type=comment,
 *     meta.fb_post_id), ingest comment + reply, idempotent
 */
class FacebookCommentsBackfillTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // 1. Schema
    // -----------------------------------------------------------------------

    public function test_thread_type_column_exists_in_conversations(): void
    {
        $this->assertTrue(Schema::hasColumn('conversations', 'thread_type'));
        $this->assertTrue(Schema::hasIndex('conversations', 'conversations_tenant_id_thread_type_index'));
    }

    public function test_conversation_model_constants(): void
    {
        $this->assertSame('message', Conversation::THREAD_MESSAGE);
        $this->assertSame('comment', Conversation::THREAD_COMMENT);
    }

    // -----------------------------------------------------------------------
    // 2. Connector: fetchCommentThreads
    // -----------------------------------------------------------------------

    /**
     * Feed có 1 bài, 2 comment:
     *  - comment khách (top-level, không có `parent`) → phải normalize thành item
     *  - comment của page (top-level, from.id == page) → phải bị bỏ
     *  - 1 reply từ page cho comment khách → phải nằm trong `replies[]`
     */
    public function test_fetch_comment_threads_normalizes_shape_and_skips_page_top_level(): void
    {
        Http::fake([
            'graph.facebook.com/*/feed*' => Http::response([
                'data' => [[
                    'id' => 'POST_1',
                    'message' => 'Bài viết giảm giá',
                    'permalink_url' => 'https://fb.com/post/1',
                    'created_time' => '2026-05-20T10:00:00+0000',
                    'comments' => ['data' => [
                        // top-level comment từ khách
                        [
                            'id' => 'CMT_A',
                            'message' => 'Sản phẩm còn hàng không?',
                            'created_time' => '2026-05-20T11:00:00+0000',
                            'from' => ['id' => 'BUYER_1', 'name' => 'Nguyễn Văn A'],
                        ],
                        // top-level comment từ PAGE (phải bị bỏ)
                        [
                            'id' => 'CMT_PAGE',
                            'message' => 'Cảm ơn bạn đã quan tâm!',
                            'created_time' => '2026-05-20T11:05:00+0000',
                            'from' => ['id' => 'PAGE_TEST', 'name' => 'Shop'],
                        ],
                        // reply từ page cho CMT_A (có `parent`)
                        [
                            'id' => 'REPLY_P1',
                            'message' => 'Còn hàng bạn nhé!',
                            'created_time' => '2026-05-20T11:10:00+0000',
                            'from' => ['id' => 'PAGE_TEST', 'name' => 'Shop'],
                            'parent' => ['id' => 'CMT_A'],
                        ],
                    ]],
                ]],
                'paging' => [
                    'cursors' => ['after' => 'CURSOR_NEXT'],
                    'next' => 'https://graph.facebook.com/...',
                ],
            ], 200),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1,
            provider: 'facebook_page',
            externalShopId: 'PAGE_TEST',
            accessToken: 'TOKEN',
        );

        /** @var FacebookPageConnector $connector */
        $connector = $this->makeConnector();
        $result = $connector->fetchCommentThreads($auth, ['pageSize' => 10]);

        // Pagination metadata
        $this->assertSame('CURSOR_NEXT', $result['nextCursor']);
        $this->assertTrue($result['hasMore']);

        // Only 1 item: customer comment (page's top-level skipped)
        $this->assertCount(1, $result['items']);

        $item = $result['items'][0];
        $this->assertSame('CMT_A', $item['comment_id']);
        $this->assertSame('BUYER_1', $item['commenter_id']);
        $this->assertSame('Nguyễn Văn A', $item['commenter_name']);
        $this->assertSame('Sản phẩm còn hàng không?', $item['message']);
        $this->assertNotNull($item['created_time']);
        $this->assertSame('POST_1', $item['post_id']);
        $this->assertSame('Bài viết giảm giá', $item['post_message']);
        $this->assertSame('https://fb.com/post/1', $item['post_permalink']);

        // Replies: 1 reply from page
        $this->assertCount(1, $item['replies']);
        $reply = $item['replies'][0];
        $this->assertSame('REPLY_P1', $reply['id']);
        $this->assertSame('PAGE_TEST', $reply['from_id']);
        $this->assertSame('Còn hàng bạn nhé!', $reply['message']);
    }

    public function test_fetch_comment_threads_no_paging_next_hasmore_false(): void
    {
        Http::fake([
            'graph.facebook.com/*/feed*' => Http::response([
                'data' => [],
                'paging' => ['cursors' => ['after' => null]],
            ], 200),
        ]);

        $auth = new MessagingAuthContext(1, 'facebook_page', 'PAGE_TEST', 'TOKEN');
        $result = $this->makeConnector()->fetchCommentThreads($auth);

        $this->assertFalse($result['hasMore']);
        $this->assertNull($result['nextCursor']);
        $this->assertEmpty($result['items']);
    }

    // -----------------------------------------------------------------------
    // 3. BackfillFacebookComments job
    // -----------------------------------------------------------------------

    public function test_backfill_creates_comment_conversation_and_ingests_messages(): void
    {
        [$tenant, $account] = $this->fbAccount();

        Http::fake([
            'graph.facebook.com/*/feed*' => Http::response($this->feedPayload(), 200),
        ]);

        BackfillFacebookComments::dispatchSync($account->id);

        // Conversation với thread_type=comment
        $this->assertDatabaseHas('conversations', [
            'channel_account_id' => $account->id,
            'external_conversation_id' => 'CMT_A',
            'thread_type' => 'comment',
            'buyer_external_id' => 'BUYER_1',
            'buyer_name' => 'Nguyễn Văn A',
        ]);

        // Meta has fb_post_id
        $conv = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $account->id)
            ->where('external_conversation_id', 'CMT_A')
            ->first();
        $this->assertNotNull($conv);
        $this->assertSame('POST_1', $conv->meta['fb_post_id']);
        $this->assertSame('https://fb.com/post/1', $conv->meta['fb_post_permalink']);
        $this->assertSame('CMT_A', $conv->meta['fb_comment_id']);
        // Người tham gia comment = chỉ commenter (reply của page bị loại).
        $this->assertSame(['Nguyễn Văn A'], $conv->meta['comment_participants']);

        // Messages: 1 comment (inbound) + 1 reply from page (outbound)
        $this->assertSame(2, Message::withoutGlobalScope(TenantScope::class)->count());

        $inbound = Message::withoutGlobalScope(TenantScope::class)
            ->where('external_message_id', 'CMT_A')->first();
        $this->assertNotNull($inbound);
        $this->assertSame('inbound', $inbound->direction);

        $outbound = Message::withoutGlobalScope(TenantScope::class)
            ->where('external_message_id', 'REPLY_P1')->first();
        $this->assertNotNull($outbound);
        $this->assertSame('outbound', $outbound->direction);

        // Comment sync done; message sync_status must not have been touched by this job
        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)
            ->find($account->id);
        $this->assertSame('done', $meta->comment_sync_status);
        $this->assertNotNull($meta->comment_synced_at);
        $this->assertNull($meta->comment_sync_error);
        // Message sync_status was seeded as 'idle' (DB default) — job must not have set it to 'failed'
        $this->assertNotSame('failed', $meta->sync_status);
        $this->assertNull($meta->sync_error);
    }

    public function test_backfill_aggregates_comment_participants_from_customer_replies(): void
    {
        [$tenant, $account] = $this->fbAccount();

        Http::fake([
            'graph.facebook.com/*/feed*' => Http::response([
                'data' => [[
                    'id' => 'POST_2',
                    'message' => 'Bài viết',
                    'permalink_url' => 'https://fb.com/post/2',
                    'created_time' => now()->subDay()->toIso8601String(),
                    'comments' => ['data' => [
                        ['id' => 'CMT_X', 'message' => 'Còn hàng?', 'created_time' => now()->subHours(23)->toIso8601String(), 'from' => ['id' => 'BUYER_1', 'name' => 'Nguyễn Văn A']],
                        // reply từ KHÁCH khác → thêm vào danh sách người tham gia
                        ['id' => 'REPLY_C', 'message' => 'Mình cũng hỏi', 'created_time' => now()->subHours(22)->toIso8601String(), 'from' => ['id' => 'BUYER_2', 'name' => 'Trần Văn B'], 'parent' => ['id' => 'CMT_X']],
                        // reply từ PAGE → KHÔNG tính
                        ['id' => 'REPLY_P', 'message' => 'Còn nhé', 'created_time' => now()->subHours(21)->toIso8601String(), 'from' => ['id' => 'PAGE_123', 'name' => 'Shop'], 'parent' => ['id' => 'CMT_X']],
                    ]],
                ]],
                'paging' => ['cursors' => ['after' => null]],
            ], 200),
        ]);

        BackfillFacebookComments::dispatchSync($account->id);

        $conv = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('external_conversation_id', 'CMT_X')->first();
        $this->assertSame(['Nguyễn Văn A', 'Trần Văn B'], $conv->meta['comment_participants']);
    }

    public function test_resource_exposes_comment_participants(): void
    {
        [$tenant, $account] = $this->fbAccount();

        $conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'thread_type' => 'comment',
            'external_conversation_id' => 'CMT_R', 'buyer_external_id' => 'BUYER_1',
            'buyer_name' => 'Nguyễn Văn A', 'status' => 'open', 'last_message_at' => now(),
            'meta' => ['comment_participants' => ['Nguyễn Văn A', 'Trần Văn B', 'Lê C', 'Phạm D']],
        ]);

        $data = (new \CMBcoreSeller\Modules\Messaging\Http\Resources\ConversationResource($conv))
            ->toArray(\Illuminate\Http\Request::create('/'));

        $this->assertSame(['Nguyễn Văn A', 'Trần Văn B', 'Lê C', 'Phạm D'], $data['comment']['participants']);
    }

    public function test_backfill_permission_error_sets_friendly_comment_error_and_does_not_touch_message_sync(): void
    {
        [$tenant, $account] = $this->fbAccount();

        // Facebook (#10) permission error response
        Http::fake([
            'graph.facebook.com/*/feed*' => Http::response([
                'error' => [
                    'message' => '(#10) To use Page Public Content Access, your use of this endpoint must be reviewed and approved by Facebook.',
                    'type' => 'OAuthException',
                    'code' => 10,
                    'fbtrace_id' => 'TRACE_ABC',
                ],
            ], 400),
        ]);

        BackfillFacebookComments::dispatchSync($account->id);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)
            ->find($account->id);

        $this->assertSame('failed', $meta->comment_sync_status);
        $this->assertStringContainsString('pages_read_engagement', $meta->comment_sync_error);
        $this->assertStringContainsString('kết nối lại', $meta->comment_sync_error);

        // Message sync_status must NOT be failed — job must not have touched it
        $this->assertNotSame('failed', $meta->sync_status);
        $this->assertNull($meta->sync_error);

        // No conversations or messages created
        $this->assertSame(0, Conversation::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_backfill_is_idempotent_on_rerun(): void
    {
        [$tenant, $account] = $this->fbAccount();

        Http::fake([
            'graph.facebook.com/*/feed*' => Http::response($this->feedPayload(), 200),
        ]);

        BackfillFacebookComments::dispatchSync($account->id);
        BackfillFacebookComments::dispatchSync($account->id);

        // Still only 2 messages (dedupe)
        $this->assertSame(2, Message::withoutGlobalScope(TenantScope::class)->count());
        // Only 1 conversation
        $this->assertSame(1, Conversation::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_backfill_skips_unsupported_connector(): void
    {
        [$tenant, $account] = $this->fbAccount();

        // Override config to a connector that does not support inbound.comments
        config(['integrations.messaging' => ['shopee_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);

        // Should return early without touching DB
        BackfillFacebookComments::dispatchSync($account->id);

        $this->assertSame(0, Conversation::withoutGlobalScope(TenantScope::class)->count());
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'CommentShop']);
        $this->app->make(CurrentTenant::class)->set($tenant);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_123', 'status' => 'active',
            'access_token' => 'PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $account->id, 'tenant_id' => $tenant->getKey(), 'messaging_enabled' => true,
        ]);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP',
            'integrations.messaging_facebook_page.app_secret' => 'S',
            'messaging.backfill.days' => 90,
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        return [$tenant, $account];
    }

    /**
     * Minimal feed payload: 1 post with 1 customer top-level comment + 1 page reply.
     * PAGE_123 is the shop page; BUYER_1 is the customer.
     *
     * @return array<string,mixed>
     */
    private function feedPayload(): array
    {
        return [
            'data' => [[
                'id' => 'POST_1',
                'message' => 'Flash sale 50%!',
                'permalink_url' => 'https://fb.com/post/1',
                'created_time' => now()->subDay()->toIso8601String(),
                'comments' => ['data' => [
                    [
                        'id' => 'CMT_A',
                        'message' => 'Còn hàng không ạ?',
                        'created_time' => now()->subHours(23)->toIso8601String(),
                        'from' => ['id' => 'BUYER_1', 'name' => 'Nguyễn Văn A'],
                    ],
                    [
                        'id' => 'REPLY_P1',
                        'message' => 'Còn hàng bạn nhé!',
                        'created_time' => now()->subHours(22)->toIso8601String(),
                        'from' => ['id' => 'PAGE_123', 'name' => 'Shop'],
                        'parent' => ['id' => 'CMT_A'],
                    ],
                ]],
            ]],
            'paging' => ['cursors' => ['after' => null]],
        ];
    }

    private function makeConnector(): FacebookPageConnector
    {
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP',
            'integrations.messaging_facebook_page.app_secret' => 'S',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        /** @var FacebookPageConnector $connector */
        $connector = $this->app->make(MessagingRegistry::class)->for('facebook_page');

        return $connector;
    }
}
