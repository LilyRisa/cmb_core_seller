<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_when_empty(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/v1/me/preferences');

        $res->assertOk()->assertJsonPath('data.ui_shell', 'v1')
            ->assertJsonPath('data.ui_active_tab', null);
        $this->assertSame([], $res->json('data.ui_open_tabs'));
    }

    public function test_put_then_get_roundtrip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', [
            'ui_shell' => 'v2',
            'ui_open_tabs' => [['appKey' => 'sales', 'path' => '/orders']],
            'ui_active_tab' => 'sales',
        ])->assertOk()->assertJsonPath('data.ui_shell', 'v2');

        $this->actingAs($user)->getJson('/api/v1/me/preferences')
            ->assertJsonPath('data.ui_active_tab', 'sales')
            ->assertJsonPath('data.ui_open_tabs.0.appKey', 'sales');
    }

    public function test_invalid_shell_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_shell' => 'v9'])
            ->assertStatus(422);
    }

    public function test_me_includes_preferences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_shell' => 'v2']);

        $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertJsonPath('data.preferences.ui_shell', 'v2');
    }

    public function test_invalid_tab_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', [
            'ui_open_tabs' => [['path' => '/orders']],
        ])->assertStatus(422);
    }

    public function test_preferences_isolated_per_user_over_http(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->actingAs($a)->putJson('/api/v1/me/preferences', ['ui_shell' => 'v2'])->assertOk();

        $this->actingAs($b)->getJson('/api/v1/me/preferences')->assertJsonPath('data.ui_shell', 'v1');
    }
}
