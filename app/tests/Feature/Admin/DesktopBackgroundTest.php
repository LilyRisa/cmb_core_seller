<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\DesktopBackground;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** SPEC 0039 — thư viện hình nền Desktop: admin CRUD + user đọc preset active + preference. */
class DesktopBackgroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_list(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')->postJson('/api/v1/admin/desktop-backgrounds', [
            'name' => 'Biển xanh',
            'image_url' => 'https://cdn.example/bg/sea.jpg',
            'image_path' => 'desktop-backgrounds/sea.jpg',
            'is_active' => true,
            'position' => 1,
        ])->assertCreated()->assertJsonPath('data.name', 'Biển xanh');

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/desktop-backgrounds')
            ->assertOk()->assertJsonPath('data.0.name', 'Biển xanh');
    }

    public function test_non_admin_cannot_access_admin_crud(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->getJson('/api/v1/admin/desktop-backgrounds')->assertStatus(401);
    }

    public function test_user_options_returns_only_active_ordered(): void
    {
        $admin = AdminUser::factory()->create();
        DesktopBackground::create(['name' => 'B', 'image_url' => 'u/b', 'image_path' => 'p/b', 'is_active' => true, 'position' => 2, 'created_by_user_id' => $admin->id]);
        DesktopBackground::create(['name' => 'A', 'image_url' => 'u/a', 'image_path' => 'p/a', 'is_active' => true, 'position' => 1, 'created_by_user_id' => $admin->id]);
        DesktopBackground::create(['name' => 'Tắt', 'image_url' => 'u/x', 'image_path' => 'p/x', 'is_active' => false, 'position' => 0, 'created_by_user_id' => $admin->id]);

        $res = $this->actingAs(User::factory()->create())->getJson('/api/v1/desktop-backgrounds');

        $res->assertOk()
            ->assertJsonCount(2, 'data')                 // preset "Tắt" bị loại
            ->assertJsonPath('data.0.name', 'A')         // position 1 trước
            ->assertJsonPath('data.1.name', 'B');
        $this->assertArrayNotHasKey('image_path', $res->json('data.0')); // chỉ id/name/image_url
    }

    public function test_ui_desktop_bg_preference_roundtrip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_desktop_bg' => 'https://cdn.example/bg/sea.jpg'])
            ->assertOk()->assertJsonPath('data.ui_desktop_bg', 'https://cdn.example/bg/sea.jpg');

        $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertJsonPath('data.preferences.ui_desktop_bg', 'https://cdn.example/bg/sea.jpg');

        // Bỏ chọn → null (gradient mặc định).
        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_desktop_bg' => null])
            ->assertOk()->assertJsonPath('data.ui_desktop_bg', null);
    }

    public function test_ui_desktop_bg_too_long_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_desktop_bg' => str_repeat('x', 2049)])
            ->assertStatus(422);
    }
}
