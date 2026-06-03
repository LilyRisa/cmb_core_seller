<?php

namespace Tests\Feature\Settings;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function bootstrap(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_index_returns_catalog_with_grouping(): void
    {
        $this->bootstrap();
        $r = $this->getJson('/api/v1/admin/system-settings?group=marketplace')->assertOk();
        $keys = collect($r->json('data'))->pluck('key')->all();
        $this->assertContains('marketplace.tiktok.app_key', $keys);
        $this->assertNotContains('notifications.brand_name', $keys);
    }

    public function test_index_marks_unconfigured_as_env_fallback(): void
    {
        $this->bootstrap();
        $r = $this->getJson('/api/v1/admin/system-settings?group=branding')->assertOk();
        $row = collect($r->json('data'))->firstWhere('key', 'notifications.brand_name');
        $this->assertNull($row['value']);
        $this->assertSame('branding', $row['group']);
    }

    public function test_secret_value_shown_unmasked_on_index(): void
    {
        // Chủ dự án yêu cầu admin hiển thị MỌI giá trị (kể cả secret) không che để tiện
        // đối chiếu/sửa cấu hình (vd so sánh app_key Lazada với console). Admin-only.
        $this->bootstrap();
        app(SystemSettingService::class)->set('marketplace.tiktok.app_secret', 'plain-secret-123');
        $r = $this->getJson('/api/v1/admin/system-settings?group=marketplace')->assertOk();
        $row = collect($r->json('data'))->firstWhere('key', 'marketplace.tiktok.app_secret');
        $this->assertSame('plain-secret-123', $row['value']);
        $this->assertTrue($row['is_secret']);
    }

    public function test_non_secret_value_shown_on_index(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('marketplace.tiktok.sandbox', true);
        $r = $this->getJson('/api/v1/admin/system-settings?group=marketplace')->assertOk();
        $row = collect($r->json('data'))->firstWhere('key', 'marketplace.tiktok.sandbox');
        $this->assertTrue($row['value']);
    }

    public function test_reveal_returns_plain_value(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('marketplace.tiktok.app_secret', 'plain');
        $this->getJson('/api/v1/admin/system-settings/marketplace.tiktok.app_secret/reveal')
            ->assertOk()->assertJsonPath('data.value', 'plain');
    }

    public function test_reveal_writes_audit_log(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('marketplace.tiktok.app_secret', 'x');
        $this->getJson('/api/v1/admin/system-settings/marketplace.tiktok.app_secret/reveal')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.setting.reveal']);
    }

    public function test_patch_persists_value(): void
    {
        $this->bootstrap();
        $this->patchJson('/api/v1/admin/system-settings/sync.poll_interval_minutes', ['value' => 12])
            ->assertOk();
        $this->assertSame(12, system_setting('sync.poll_interval_minutes'));
    }

    public function test_patch_validates_type(): void
    {
        $this->bootstrap();
        $this->patchJson('/api/v1/admin/system-settings/sync.poll_interval_minutes', ['value' => 'abc'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'SETTING_VALUE_INVALID');
    }

    public function test_patch_unknown_key_returns_422(): void
    {
        $this->bootstrap();
        $this->patchJson('/api/v1/admin/system-settings/nope.invalid', ['value' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'SETTING_KEY_NOT_ALLOWED');
    }

    public function test_delete_returns_fallback_to_env(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('sync.poll_interval_minutes', 7);
        $this->deleteJson('/api/v1/admin/system-settings/sync.poll_interval_minutes')->assertOk();
        $this->assertNull(SystemSetting::query()->where('key', 'sync.poll_interval_minutes')->first());
    }

    public function test_sync_from_env_seeds_missing_rows(): void
    {
        $this->bootstrap();
        // Override env across all dotenv adapter sources ($_ENV / $_SERVER / putenv).
        $_ENV['NOTIFICATIONS_BRAND_NAME'] = 'Hello';
        $_SERVER['NOTIFICATIONS_BRAND_NAME'] = 'Hello';
        putenv('NOTIFICATIONS_BRAND_NAME=Hello');
        try {
            $r = $this->postJson('/api/v1/admin/system-settings/sync-from-env')->assertOk();
            $this->assertGreaterThanOrEqual(1, $r->json('data.created'));
            $this->assertSame('Hello', system_setting('notifications.brand_name'));
        } finally {
            unset($_ENV['NOTIFICATIONS_BRAND_NAME'], $_SERVER['NOTIFICATIONS_BRAND_NAME']);
            putenv('NOTIFICATIONS_BRAND_NAME');
        }
    }

    public function test_sync_from_env_idempotent(): void
    {
        $this->bootstrap();
        $_ENV['NOTIFICATIONS_BRAND_NAME'] = 'First';
        $_SERVER['NOTIFICATIONS_BRAND_NAME'] = 'First';
        putenv('NOTIFICATIONS_BRAND_NAME=First');
        try {
            $this->postJson('/api/v1/admin/system-settings/sync-from-env')->assertOk();
            $first = system_setting('notifications.brand_name');
            // Đổi env, sync lại — row đã có không được ghi đè (chỉ insert key mới).
            $_ENV['NOTIFICATIONS_BRAND_NAME'] = 'Second';
            $_SERVER['NOTIFICATIONS_BRAND_NAME'] = 'Second';
            putenv('NOTIFICATIONS_BRAND_NAME=Second');
            $this->postJson('/api/v1/admin/system-settings/sync-from-env')->assertOk();
            $this->assertSame($first, system_setting('notifications.brand_name'));
        } finally {
            unset($_ENV['NOTIFICATIONS_BRAND_NAME'], $_SERVER['NOTIFICATIONS_BRAND_NAME']);
            putenv('NOTIFICATIONS_BRAND_NAME');
        }
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $this->getJson('/api/v1/admin/system-settings')->assertStatus(401);
    }
}
