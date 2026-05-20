<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test realtime contract (SPEC-0024 §6.3): events broadcast lên private channel
 * `tenant.{id}.messaging` — FE subscribe channel này. Driver `null` mặc định.
 */
class MessagingBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_received_broadcasts_on_tenant_channel(): void
    {
        $tenant = Tenant::create(['name' => 'BcShop']);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 's', 'shop_name' => 's', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'manual',
            'external_conversation_id' => 'c', 'buyer_external_id' => 'b', 'status' => Conversation::STATUS_OPEN,
        ]);

        $channels = (new MessageReceived(messageId: 1, conversationId: $conv->id))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-tenant.'.$tenant->getKey().'.messaging', $channels[0]->name);
    }
}
