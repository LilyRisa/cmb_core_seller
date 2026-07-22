<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationCategoryColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_column_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('app_notifications', 'category'));
    }

    public function test_category_defaults_to_system_when_not_set(): void
    {
        $tenant = Tenant::create(['name' => 'CatShop']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $n = Notification::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'type' => 'channel.reconnect_needed',
            'title' => 'X',
        ]);

        $this->assertSame('system', $n->fresh()->category);
    }

    public function test_category_is_mass_assignable(): void
    {
        $tenant = Tenant::create(['name' => 'CatShop2']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $n = Notification::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'type' => 'order.cancelled',
            'title' => 'X',
            'category' => 'order',
        ]);

        $this->assertSame('order', $n->fresh()->category);
    }
}
