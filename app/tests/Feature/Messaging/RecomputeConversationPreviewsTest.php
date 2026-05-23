<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `messaging:recompute-previews` sửa dữ liệu hội thoại bị clobber: tính lại
 * last_message_at + preview từ tin nhắn MỚI NHẤT thực tế.
 */
class RecomputeConversationPreviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recomputes_last_message_from_actual_messages(): void
    {
        $tenant = Tenant::create(['name' => 'RecomputeTenant']);
        app(CurrentTenant::class)->set($tenant);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_R', 'shop_name' => 'Trang R', 'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        // Conversation với header SAI (clobbered): preview = tin cũ, last_message_at = quá khứ.
        $conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page', 'external_conversation_id' => 'PSID_R', 'buyer_external_id' => 'PSID_R',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now()->subHour(),
            'last_message_preview' => 'tin cũ (sai)',
        ]);

        $base = [
            'tenant_id' => $tenant->getKey(), 'conversation_id' => $conv->getKey(),
            'direction' => Message::DIRECTION_INBOUND, 'kind' => Message::KIND_TEXT,
            'attachments_count' => 0, 'delivery_status' => Message::STATUS_SENT,
        ];
        Message::query()->create($base + ['external_message_id' => 'OLD', 'body' => 'tin cũ', 'sent_at' => now()->subHour()]);
        Message::query()->create($base + ['external_message_id' => 'NEW', 'body' => 'TIN MỚI NHẤT', 'sent_at' => now()]);

        $this->artisan('messaging:recompute-previews')->assertSuccessful();

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertSame('TIN MỚI NHẤT', $fresh->last_message_preview);
        $this->assertNotNull($fresh->last_inbound_at);
    }
}
