<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test Zalo OA tier/permission block detection in SendMessage job.
 * SPEC-0039 Phase 1+ — khi OA gửi tin trả về lỗi -224 (cần nâng gói OA Tier):
 *   - Message bị đánh fail với failure_code='provider_permission'
 *   - ChannelAccount.meta lưu cờ zalo_send_blocked=true + lý do + thời điểm
 *   - Job KHÔNG retry (return sớm, không throw)
 *   - Khi gửi thành công sau đó: cờ zalo_send_blocked tự xoá (self-recovery)
 */
class ZaloTierBlockedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mirrors ZaloOaWebhookTest config setup.
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', [
            'app_id'       => 'app_123',
            'app_secret'   => 'sec',
            'oa_secret'    => 'oa_secret_xyz',
            'redirect_uri' => 'https://x.test/cb',
        ]);
        // Clear singleton so registry picks up new config.
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function makeAccount(Tenant $tenant, array $extra = []): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id'         => $tenant->id,
            'provider'          => 'zalo_oa',
            'external_shop_id'  => 'OA_TIER_'.uniqid(),
            'shop_name'         => 'Tier Test OA',
            'access_token'      => 'TKN_TIER',
            'status'            => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ], $extra));
    }

    private function makeConversation(Tenant $tenant, ChannelAccount $account, string $uid = 'USER_TIER'): Conversation
    {
        return Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id'                  => $tenant->id,
            'channel_account_id'         => $account->id,
            'provider'                   => 'zalo_oa',
            'external_conversation_id'   => $uid,
            'buyer_external_id'          => $uid,
        ]);
    }

    private function makeMessage(Tenant $tenant, Conversation $conversation): Message
    {
        return Message::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id'       => $tenant->id,
            'conversation_id' => $conversation->id,
            'direction'       => Message::DIRECTION_OUTBOUND,
            'kind'            => Message::KIND_TEXT,
            'body'            => 'Hello',
            'delivery_status' => Message::STATUS_PENDING,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------------------

    /**
     * Zalo error -224 → message fail 'provider_permission', account flagged, no retry.
     */
    public function test_zalo_tier_block_marks_message_failed_and_sets_account_flag(): void
    {
        Http::fake([
            'openapi.zalo.me/*' => Http::response([
                'error'   => -224,
                'message' => 'The OA needs to upgrade OA Tier Package to use this feature. See more on https://zalo.cloud/oa/pricing',
            ], 200),
        ]);

        $tenant      = Tenant::factory()->create();
        $account     = $this->makeAccount($tenant);
        $conversation = $this->makeConversation($tenant, $account);
        $message     = $this->makeMessage($tenant, $conversation);

        // dispatchSync runs the job synchronously (no queue) — mimics test environment.
        SendMessage::dispatchSync($message->id);

        $message->refresh();
        $this->assertEquals(Message::STATUS_FAILED, $message->delivery_status);
        $this->assertEquals('provider_permission', $message->failure_code);

        $account->refresh();
        $meta = $account->meta ?? [];
        $this->assertTrue((bool) ($meta['zalo_send_blocked'] ?? false), 'zalo_send_blocked should be true');
        $this->assertNotEmpty($meta['zalo_send_blocked_reason'] ?? '', 'zalo_send_blocked_reason should be set');
        $this->assertNotEmpty($meta['zalo_send_blocked_at'] ?? '', 'zalo_send_blocked_at should be set');
        $this->assertStringContainsString('OA Tier', $meta['zalo_send_blocked_reason']);
    }

    /**
     * Job returns (no throw) ⇒ no retry; the sync queue never calls handle() again.
     * We verify indirectly: message is in a terminal state after exactly 1 call.
     */
    public function test_zalo_tier_block_does_not_retry(): void
    {
        Http::fake([
            'openapi.zalo.me/*' => Http::response([
                'error'   => -224,
                'message' => 'The OA needs to upgrade OA Tier Package to use this feature.',
            ], 200),
        ]);

        $tenant      = Tenant::factory()->create();
        $account     = $this->makeAccount($tenant, ['external_shop_id' => 'OA_RETRY_1']);
        $conversation = $this->makeConversation($tenant, $account, 'USER_RETRY');
        $message     = $this->makeMessage($tenant, $conversation);

        // If the job re-threw, dispatchSync would propagate the exception here.
        // No exception means no retry path was triggered.
        $exceptionThrown = false;
        try {
            SendMessage::dispatchSync($message->id);
        } catch (\Throwable) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'Job should not throw (no retry) for tier-blocked error');
        $this->assertEquals('provider_permission', $message->fresh()->failure_code);
    }

    /**
     * Successful send after a prior block → zalo_send_blocked flags cleared from meta.
     */
    public function test_successful_send_clears_zalo_send_blocked_flag(): void
    {
        Http::fake([
            'openapi.zalo.me/*' => Http::response([
                'error'   => 0,
                'message' => 'Success',
                'data'    => ['message_id' => 'MSG_OK_SELF_RECOVERY'],
            ], 200),
        ]);

        $tenant  = Tenant::factory()->create();
        $account = $this->makeAccount($tenant, [
            'external_shop_id' => 'OA_RECOVERY_1',
            'meta' => [
                'zalo_send_blocked'        => true,
                'zalo_send_blocked_reason' => 'The OA needs to upgrade OA Tier Package',
                'zalo_send_blocked_at'     => now()->subHour()->toIso8601String(),
            ],
        ]);
        $conversation = $this->makeConversation($tenant, $account, 'USER_RECOVERY');
        $message      = $this->makeMessage($tenant, $conversation);

        SendMessage::dispatchSync($message->id);

        $message->refresh();
        $this->assertEquals(Message::STATUS_SENT, $message->delivery_status);

        $account->refresh();
        $meta = $account->meta ?? [];
        $this->assertArrayNotHasKey('zalo_send_blocked', $meta, 'zalo_send_blocked should be cleared');
        $this->assertArrayNotHasKey('zalo_send_blocked_reason', $meta, 'zalo_send_blocked_reason should be cleared');
        $this->assertArrayNotHasKey('zalo_send_blocked_at', $meta, 'zalo_send_blocked_at should be cleared');
    }
}
