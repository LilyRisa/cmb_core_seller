<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDispatcherContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_resolves_to_notification_dispatcher(): void
    {
        $this->assertInstanceOf(NotificationDispatcher::class, app(NotificationDispatcherContract::class));
    }

    public function test_general_page_type_maps_to_general_category(): void
    {
        $this->assertSame('general', NotificationType::categoryFor(NotificationType::GENERAL_PAGE));
    }

    public function test_has_received_true_after_dispatch_to_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'HasReceivedShop']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $tenantId = (int) $tenant->getKey();

        $dispatcher = app(NotificationDispatcherContract::class);
        $dispatcher->dispatch($tenantId, [
            'type' => NotificationType::GENERAL_PAGE, 'title' => 'X', 'dedup_key' => 'general.page:999',
        ]);

        $this->assertTrue($dispatcher->hasReceived($tenantId, NotificationType::GENERAL_PAGE, 'general.page:999'));
        $this->assertFalse($dispatcher->hasReceived($tenantId, NotificationType::GENERAL_PAGE, 'general.page:other'));
        $this->assertFalse($dispatcher->hasReceived($tenantId + 999, NotificationType::GENERAL_PAGE, 'general.page:999'));
    }
}
