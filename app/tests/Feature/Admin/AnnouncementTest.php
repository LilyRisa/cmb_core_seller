<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\Announcement;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SPEC 0037 — admin CRUD announcement (sanitize body, bật/tắt, upload media R2) +
 * endpoint user đọc popup active (đúng cửa sổ thời gian) + phân quyền.
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_sanitizes_body_html(): void
    {
        $admin = AdminUser::factory()->create();

        $res = $this->actingAs($admin, 'admin_web')->postJson('/api/v1/admin/announcements', [
            'title' => 'Bảo trì',
            'body_html' => '<p>Bảo trì 22h</p><script>evil()</script>',
            'is_active' => true,
        ]);

        $res->assertCreated()->assertJsonPath('data.is_active', true);
        $stored = Announcement::query()->firstOrFail();
        $this->assertStringNotContainsString('<script', $stored->body_html);
        $this->assertStringContainsString('Bảo trì 22h', $stored->body_html);
    }

    public function test_admin_can_toggle_and_delete(): void
    {
        $admin = AdminUser::factory()->create();
        $a = Announcement::create(['title' => 'X', 'body_html' => '<p>x</p>', 'is_active' => false, 'dismiss_label' => 'Đã hiểu', 'created_by_user_id' => $admin->getKey()]);

        $this->actingAs($admin, 'admin_web')->patchJson("/api/v1/admin/announcements/{$a->id}", ['is_active' => true])
            ->assertOk()->assertJsonPath('data.is_active', true);

        $this->actingAs($admin, 'admin_web')->deleteJson("/api/v1/admin/announcements/{$a->id}")->assertOk();
        $this->assertDatabaseMissing('announcements', ['id' => $a->id]);
    }

    public function test_admin_media_upload_returns_url(): void
    {
        Storage::fake('public');
        $admin = AdminUser::factory()->create();

        $res = $this->actingAs($admin, 'admin_web')->postJson('/api/v1/admin/announcements/media', [
            'file' => UploadedFile::fake()->image('promo.png'),
        ]);

        $res->assertOk();
        $this->assertNotEmpty($res->json('data.url'));
    }

    public function test_user_active_returns_only_active_within_window(): void
    {
        $admin = AdminUser::factory()->create();
        Announcement::create(['title' => 'On', 'body_html' => '<p>on</p>', 'is_active' => true, 'dismiss_label' => 'OK', 'created_by_user_id' => $admin->getKey()]);
        Announcement::create(['title' => 'Off', 'body_html' => '<p>off</p>', 'is_active' => false, 'dismiss_label' => 'OK', 'created_by_user_id' => $admin->getKey()]);
        Announcement::create(['title' => 'Future', 'body_html' => '<p>f</p>', 'is_active' => true, 'starts_at' => now()->addDay(), 'dismiss_label' => 'OK', 'created_by_user_id' => $admin->getKey()]);
        Announcement::create(['title' => 'Expired', 'body_html' => '<p>e</p>', 'is_active' => true, 'ends_at' => now()->subDay(), 'dismiss_label' => 'OK', 'created_by_user_id' => $admin->getKey()]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $res = $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->getJson('/api/v1/announcements/active');

        $res->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'On');
    }

    public function test_regular_user_cannot_access_admin_announcements(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user, 'web')->getJson('/api/v1/admin/announcements')->assertStatus(401);
    }
}
