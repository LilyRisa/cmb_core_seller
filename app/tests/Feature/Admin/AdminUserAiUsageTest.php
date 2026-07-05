<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserAiUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_list_includes_ai_usage_and_breakdown_endpoint(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $u = User::factory()->create();
        $ym = (int) now()->format('Ym');
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'user_id' => $u->id, 'period_ym' => $ym, 'feature' => 'messaging', 'count' => 4,
        ]);

        $list = $this->getJson('/api/v1/admin/users')->assertOk()->json('data');
        $row = collect($list)->firstWhere('id', $u->id);
        $this->assertSame(['this_month' => 4, 'all_time' => 4], $row['ai_usage']);

        $this->getJson("/api/v1/admin/users/{$u->id}/ai-usage")
            ->assertOk()
            ->assertJsonPath('data.all_time', 4)
            ->assertJsonPath('data.by_feature.0.feature', 'messaging');
    }
}
