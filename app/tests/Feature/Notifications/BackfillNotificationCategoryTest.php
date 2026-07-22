<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillNotificationCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(int $tenantId, int $userId, string $type, string $category = 'system'): Notification
    {
        return Notification::create([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'type' => $type,
            'category' => $category, 'title' => 'X',
        ]);
    }

    public function test_backfills_order_types_to_order_category(): void
    {
        $tenant = Tenant::create(['name' => 'BackfillShop']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $tid = (int) $tenant->getKey();
        $uid = (int) $user->getKey();

        $negative = $this->makeRow($tid, $uid, 'order.negative_total');
        $cancelled = $this->makeRow($tid, $uid, 'order.cancelled');
        $returnNew = $this->makeRow($tid, $uid, 'order.return_new');
        $channel = $this->makeRow($tid, $uid, 'channel.reconnect_needed');

        $this->artisan('notifications:backfill-category')->assertExitCode(0);

        $this->assertSame('order', $negative->fresh()->category);
        $this->assertSame('order', $cancelled->fresh()->category);
        $this->assertSame('order', $returnNew->fresh()->category);
        $this->assertSame('system', $channel->fresh()->category);
    }

    public function test_idempotent_second_run_changes_nothing(): void
    {
        $tenant = Tenant::create(['name' => 'BackfillShop2']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $row = $this->makeRow((int) $tenant->getKey(), (int) $user->getKey(), 'order.cancelled');

        $this->artisan('notifications:backfill-category')->assertExitCode(0);
        $this->assertSame('order', $row->fresh()->category);

        $this->artisan('notifications:backfill-category')->assertExitCode(0);
        $this->assertSame('order', $row->fresh()->category);
    }

    public function test_scans_across_all_tenants_without_scope_leak(): void
    {
        $t1 = Tenant::create(['name' => 'T1']);
        $u1 = User::factory()->create();
        $t1->users()->attach($u1->getKey(), ['role' => Role::Owner->value]);
        $t2 = Tenant::create(['name' => 'T2']);
        $u2 = User::factory()->create();
        $t2->users()->attach($u2->getKey(), ['role' => Role::Owner->value]);

        $row1 = $this->makeRow((int) $t1->getKey(), (int) $u1->getKey(), 'order.cancelled');
        $row2 = $this->makeRow((int) $t2->getKey(), (int) $u2->getKey(), 'order.negative_total');

        $this->artisan('notifications:backfill-category')->assertExitCode(0);

        $this->assertSame('order', Notification::withoutGlobalScope(TenantScope::class)->find($row1->id)->category);
        $this->assertSame('order', Notification::withoutGlobalScope(TenantScope::class)->find($row2->id)->category);
    }
}
