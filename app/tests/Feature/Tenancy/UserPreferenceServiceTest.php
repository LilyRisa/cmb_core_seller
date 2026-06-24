<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_put_many_merges_without_wiping_other_keys(): void
    {
        $user = User::factory()->create();
        $svc = app(UserPreferenceService::class);

        $svc->putMany($user->id, ['ui_shell' => 'v2']);
        $svc->putMany($user->id, ['ui_active_tab' => 'sales']);

        $all = $svc->all($user->id);
        $this->assertSame('v2', $all['ui_shell']);
        $this->assertSame('sales', $all['ui_active_tab']);
    }

    public function test_all_is_isolated_per_user(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $svc = app(UserPreferenceService::class);

        $svc->putMany($a->id, ['ui_shell' => 'v2']);

        $this->assertSame([], $svc->all($b->id));
    }
}
