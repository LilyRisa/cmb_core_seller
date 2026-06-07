<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Support\Events\SupportMessageCreated;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Support\Models\SupportMessage;
use CMBcoreSeller\Modules\Support\Services\SupportConversationService;
use CMBcoreSeller\Modules\Support\Support\SupportChannelAuthorizer;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Realtime CSKH (ADR-0021): mỗi tin support (user/CSKH/đóng cuộc) phát SupportMessageCreated lên
 * private channel `tenant.{id}.support`; channel chỉ cho thành viên tenant nghe.
 */
class SupportRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_message_broadcasts_event(): void
    {
        Event::fake([SupportMessageCreated::class]);
        $tenant = Tenant::create(['name' => 'SupShop']);
        $this->app->make(CurrentTenant::class)->set($tenant);
        $user = User::factory()->create();

        $this->app->make(SupportConversationService::class)
            ->postUserMessage((int) $tenant->getKey(), (int) $user->getKey(), 'hi', []);

        Event::assertDispatched(SupportMessageCreated::class, fn ($e) => $e->tenantId === (int) $tenant->getKey()
            && $e->sender === SupportMessage::SENDER_USER);
    }

    public function test_cskh_reply_broadcasts_event(): void
    {
        Event::fake([SupportMessageCreated::class]);
        $tenant = Tenant::create(['name' => 'SupShop2']);
        $conv = SupportConversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'status' => SupportConversation::STATUS_OPEN,
        ]);

        $this->app->make(SupportConversationService::class)->postCskhMessage($conv, 1, 'reply', []);

        Event::assertDispatched(SupportMessageCreated::class, fn ($e) => $e->tenantId === (int) $tenant->getKey()
            && $e->sender === SupportMessage::SENDER_CSKH
            && $e->conversationId === (int) $conv->getKey());
    }

    public function test_channel_authorizer_allows_member_blocks_outsider(): void
    {
        $tenant = Tenant::create(['name' => 'AuthzSup']);
        $member = User::factory()->create();
        $tenant->users()->attach($member->getKey(), ['role' => Role::Owner->value]);
        $outsider = User::factory()->create();

        $authz = $this->app->make(SupportChannelAuthorizer::class);
        $this->assertTrue($authz->canViewTenantSupport($member, (int) $tenant->getKey()));
        $this->assertFalse($authz->canViewTenantSupport($outsider, (int) $tenant->getKey()));
    }
}
