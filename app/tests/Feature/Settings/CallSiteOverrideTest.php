<?php

namespace Tests\Feature\Settings;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec 2026-05-17 — đảm bảo call-site nghiệp vụ đọc qua `system_setting()`.
 *
 * Phương pháp: set config (env fallback) trước, gọi helper, kiểm value = fallback.
 * Sau đó `set()` qua service (mô phỏng admin sửa), gọi lại → kiểm DB value win.
 */
class CallSiteOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_app_key_falls_back_to_config_when_db_empty(): void
    {
        config()->set('integrations.tiktok.app_key', 'env-key');
        $this->assertSame(
            'env-key',
            system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key'))
        );
    }

    public function test_tiktok_app_key_db_overrides_config(): void
    {
        config()->set('integrations.tiktok.app_key', 'env-key');
        app(SystemSettingService::class)->set('marketplace.tiktok.app_key', 'db-override');
        $this->assertSame(
            'db-override',
            system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key'))
        );
    }

    public function test_lazada_secret_db_overrides_config(): void
    {
        config()->set('integrations.lazada.app_secret', 'env-secret');
        app(SystemSettingService::class)->set('marketplace.lazada.app_secret', 'db-secret');
        $this->assertSame(
            'db-secret',
            system_setting('marketplace.lazada.app_secret', config('integrations.lazada.app_secret'))
        );
    }

    public function test_brand_name_db_overrides_config(): void
    {
        config()->set('notifications.brand.name', 'EnvBrand');
        app(SystemSettingService::class)->set('notifications.brand_name', 'DbBrand');
        $this->assertSame(
            'DbBrand',
            system_setting('notifications.brand_name', config('notifications.brand.name'))
        );
    }

    public function test_gotenberg_url_db_overrides_config(): void
    {
        config()->set('fulfillment.gotenberg_url', 'http://env-gotenberg');
        app(SystemSettingService::class)->set('pdf.gotenberg_url', 'http://db-gotenberg');
        $this->assertSame(
            'http://db-gotenberg',
            system_setting('pdf.gotenberg_url', config('fulfillment.gotenberg_url'))
        );
    }

    public function test_ghn_base_url_db_overrides_config(): void
    {
        config()->set('fulfillment.ghn_base_url', 'http://env-ghn');
        app(SystemSettingService::class)->set('carriers.ghn.base_url', 'http://db-ghn');
        $this->assertSame(
            'http://db-ghn',
            system_setting('carriers.ghn.base_url', config('fulfillment.ghn_base_url'))
        );
    }
}
