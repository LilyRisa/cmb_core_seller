<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillFacebookComments;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'ChanShop']);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP123',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->activatePro();
    }

    private function activatePro(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
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

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_owner_can_start_facebook_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/facebook/connect')
            ->assertOk()
            ->assertJsonPath('data.authorize_url', fn ($url) => str_contains((string) $url, 'facebook.com'));
    }

    public function test_staff_cs_cannot_start_facebook_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/facebook/connect')
            ->assertStatus(403);
    }

    public function test_index_lists_only_facebook_pages_without_token(): void
    {
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'shop_name' => 'Shop FB', 'status' => 'active',
            'access_token' => 'SECRET_PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        // 1 gian hàng sàn — KHÔNG được xuất hiện trong list facebook.
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ_1', 'shop_name' => 'Shop LZ', 'status' => 'active',
        ]);

        $res = $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_shop_id', 'PAGE_1')
            ->assertJsonPath('data.0.messaging_enabled', true)
            ->assertJsonPath('data.0.token_expired', false);

        // Không lộ token
        $this->assertStringNotContainsString('SECRET_PAGE_TOKEN', $res->getContent());
    }

    public function test_staff_cs_can_list_channels(): void
    {
        // staff_cs có messaging.view (không có messaging.connect) ⇒ vẫn xem được danh sách.
        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels')
            ->assertOk();
    }

    public function test_disconnect_deletes_page_and_cascades(): void
    {
        $disk = (string) config('messaging.media_disk', 'local');
        Storage::fake($disk);
        Storage::disk($disk)->put('tenants/x/messaging/test.jpg', 'fakebytes');

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_7', 'shop_name' => 'FB7', 'status' => 'active',
            'access_token' => 'PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_7',
            'buyer_external_id' => 'psid_7', 'status' => 'open', 'last_message_at' => now(),
        ]);
        $msg = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => 'inbound', 'kind' => 'text', 'body' => 'hi', 'delivery_status' => 'delivered',
        ]);
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'message_id' => $msg->id,
            'kind' => 'image', 'mime' => 'image/jpeg', 'status' => 'downloaded',
            'storage_path' => 'tenants/x/messaging/test.jpg',
        ]);

        Http::fake([
            'graph.facebook.com/*subscribed_apps*' => Http::response(['success' => true], 200),
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/channels/{$account->id}")
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.conversations_deleted', 1);

        $this->assertDatabaseMissing('channel_accounts', ['id' => $account->id]);
        $this->assertDatabaseMissing('conversations', ['id' => $conv->id]);
        $this->assertDatabaseMissing('messages', ['id' => $msg->id]);
        $this->assertDatabaseMissing('message_attachments', ['message_id' => $msg->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'messaging.facebook_page.disconnected']);
        Storage::disk($disk)->assertMissing('tenants/x/messaging/test.jpg');
    }

    public function test_disconnect_rejects_non_facebook_account(): void
    {
        $lz = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ_2', 'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/channels/{$lz->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('channel_accounts', ['id' => $lz->id, 'deleted_at' => null]);
    }

    public function test_staff_cs_cannot_disconnect(): void
    {
        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_8', 'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/channels/{$account->id}")
            ->assertStatus(403);
    }

    /** Tạo nhanh 1 Facebook Page cho tenant hiện tại. */
    private function fbPage(string $externalId): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => $externalId, 'shop_name' => $externalId, 'status' => 'active',
            'access_token' => 'TOKEN_'.$externalId, 'messaging_enabled' => true,
        ]);
    }

    public function test_bulk_sync_queues_and_dispatches_for_selected_pages(): void
    {
        Bus::fake([BackfillMessagingChannel::class, BackfillFacebookComments::class]);
        $a = $this->fbPage('PAGE_A');
        $b = $this->fbPage('PAGE_B');
        $c = $this->fbPage('PAGE_C'); // không chọn ⇒ không bị đụng tới.

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-sync', ['ids' => [$a->id, $b->id]])
            ->assertStatus(202)
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.processed', 2);

        foreach ([$a->id, $b->id] as $id) {
            Bus::assertDispatched(BackfillMessagingChannel::class, fn ($j) => $j->channelAccountId === $id);
            Bus::assertDispatched(BackfillFacebookComments::class, fn ($j) => $j->channelAccountId === $id);
            $this->assertSame('queued', MessagingAccountMeta::query()->find($id)?->sync_status);
        }
        Bus::assertNotDispatched(BackfillMessagingChannel::class, fn ($j) => $j->channelAccountId === $c->id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'messaging.bulk_sync']);
    }

    public function test_bulk_disconnect_deletes_selected_pages_and_cascades(): void
    {
        Http::fake([
            'graph.facebook.com/*subscribed_apps*' => Http::response(['success' => true], 200),
        ]);
        $a = $this->fbPage('PAGE_D');
        $b = $this->fbPage('PAGE_E');
        $keep = $this->fbPage('PAGE_F');
        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $a->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_d',
            'buyer_external_id' => 'psid_d', 'status' => 'open', 'last_message_at' => now(),
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-disconnect', ['ids' => [$a->id, $b->id]])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.processed', 2)
            ->assertJsonPath('data.conversations_deleted', 1);

        $this->assertDatabaseMissing('channel_accounts', ['id' => $a->id]);
        $this->assertDatabaseMissing('channel_accounts', ['id' => $b->id]);
        $this->assertDatabaseMissing('conversations', ['id' => $conv->id]);
        $this->assertDatabaseHas('channel_accounts', ['id' => $keep->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'messaging.bulk_disconnected']);
    }

    public function test_bulk_actions_ignore_non_facebook_and_unknown_ids(): void
    {
        Bus::fake([BackfillMessagingChannel::class, BackfillFacebookComments::class]);
        $fb = $this->fbPage('PAGE_G');
        $lz = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ_9', 'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-sync', ['ids' => [$fb->id, $lz->id, 999999]])
            ->assertStatus(202)
            ->assertJsonPath('data.processed', 1); // chỉ page facebook được xử lý

        $this->assertNull(MessagingAccountMeta::query()->find($lz->id));
    }

    public function test_bulk_sync_ignores_pages_of_other_tenant(): void
    {
        Bus::fake([BackfillMessagingChannel::class, BackfillFacebookComments::class]);
        $mine = $this->fbPage('PAGE_MINE');

        // Page thuộc tenant khác — không được xử lý (BelongsToTenant global scope).
        $other = Tenant::create(['name' => 'OtherShop']);
        $foreign = ChannelAccount::query()->create([
            'tenant_id' => $other->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_FOREIGN', 'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-sync', ['ids' => [$mine->id, $foreign->id]])
            ->assertStatus(202)
            ->assertJsonPath('data.processed', 1);

        Bus::assertNotDispatched(BackfillMessagingChannel::class, fn ($j) => $j->channelAccountId === $foreign->id);
    }

    public function test_bulk_sync_validates_ids_required(): void
    {
        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-sync', ['ids' => []])
            ->assertStatus(422);
    }

    public function test_staff_cs_cannot_bulk_sync_or_disconnect(): void
    {
        $page = $this->fbPage('PAGE_H');

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-sync', ['ids' => [$page->id]])
            ->assertStatus(403);

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/channels/bulk-disconnect', ['ids' => [$page->id]])
            ->assertStatus(403);
    }
}
