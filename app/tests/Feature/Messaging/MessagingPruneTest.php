<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test prune commands (SPEC-0024 §6.4 / §8): raw_payload hygiene + draft cleanup.
 */
class MessagingPruneTest extends TestCase
{
    use RefreshDatabase;

    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'PruneShop']);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 's', 'shop_name' => 's', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $this->conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'manual',
            'external_conversation_id' => 'c', 'buyer_external_id' => 'b', 'status' => Conversation::STATUS_OPEN,
        ]);
    }

    public function test_prune_payloads_nulls_old_raw_payload(): void
    {
        $old = Message::query()->create([
            'tenant_id' => $this->conv->tenant_id, 'conversation_id' => $this->conv->id,
            'external_message_id' => 'old', 'direction' => 'inbound', 'kind' => 'text',
            'body' => 'old', 'delivery_status' => 'sent', 'raw_payload' => ['secret' => '0912345678'],
        ]);
        Message::withoutGlobalScope(TenantScope::class)->whereKey($old->id)->update(['created_at' => now()->subDays(40)]);

        $recent = Message::query()->create([
            'tenant_id' => $this->conv->tenant_id, 'conversation_id' => $this->conv->id,
            'external_message_id' => 'new', 'direction' => 'inbound', 'kind' => 'text',
            'body' => 'new', 'delivery_status' => 'sent', 'raw_payload' => ['k' => 'v'],
        ]);

        $this->artisan('messaging:prune-payloads')->assertSuccessful();

        $this->assertNull(Message::withoutGlobalScope(TenantScope::class)->find($old->id)->raw_payload);
        $this->assertNotNull(Message::withoutGlobalScope(TenantScope::class)->find($recent->id)->raw_payload);
    }

    public function test_prune_drafts_expires_overdue_pending(): void
    {
        $draft = MessageDraft::query()->create([
            'tenant_id' => $this->conv->tenant_id, 'conversation_id' => $this->conv->id,
            'draft_text' => 'x', 'status' => MessageDraft::STATUS_PENDING,
            'expires_at' => now()->subHours(2),
        ]);

        $this->artisan('messaging:prune-drafts')->assertSuccessful();

        $this->assertSame(MessageDraft::STATUS_EXPIRED, MessageDraft::withoutGlobalScope(TenantScope::class)->find($draft->id)->status);
    }
}
