<?php

namespace Tests\Unit\Settings;

use CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog;
use InvalidArgumentException;
use Tests\TestCase;

class SystemSettingsCatalogTest extends TestCase
{
    public function test_all_groups_present(): void
    {
        $all = SystemSettingsCatalog::all();
        $this->assertNotEmpty($all);
        $groups = collect($all)->pluck('group')->unique()->values()->all();
        sort($groups);
        $this->assertSame(['ai', 'branding', 'fulfillment', 'mail', 'marketplace', 'push', 'sync'], $groups);
    }

    public function test_count_is_54(): void
    {
        // branding 5 + mail 8 + marketplace 11 + fulfillment 16 + sync 7 + push 3 + ai 4.
        // ai 4 = system_prompt + help provider_code + embedding_provider_code + embedding_model.
        $this->assertCount(54, SystemSettingsCatalog::all());
    }

    public function test_secret_count_is_11(): void
    {
        // mail.password + tiktok×2 + lazada×2 + shopee×3 + r2×2 + push.vapid_private_key.
        $secrets = collect(SystemSettingsCatalog::all())->where('is_secret', true)->keys()->all();
        $this->assertCount(11, $secrets);
    }

    public function test_require_throws_on_unknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SystemSettingsCatalog::require('nope.invalid');
    }

    public function test_validate_bool_accepts_string_true(): void
    {
        $this->assertTrue(SystemSettingsCatalog::validate('marketplace.tiktok.sandbox', 'true'));
    }

    public function test_validate_bool_accepts_native_boolean(): void
    {
        $this->assertTrue(SystemSettingsCatalog::validate('marketplace.tiktok.sandbox', false));
    }

    public function test_validate_int_accepts_numeric_string(): void
    {
        $this->assertTrue(SystemSettingsCatalog::validate('sync.poll_interval_minutes', '15'));
    }

    public function test_validate_int_rejects_letters(): void
    {
        $this->assertFalse(SystemSettingsCatalog::validate('sync.poll_interval_minutes', 'abc'));
    }

    public function test_validate_string_rejects_oversized(): void
    {
        $this->assertFalse(SystemSettingsCatalog::validate('notifications.brand_name', str_repeat('x', 5000)));
    }

    public function test_has_returns_true_only_for_whitelisted(): void
    {
        $this->assertTrue(SystemSettingsCatalog::has('marketplace.tiktok.app_key'));
        $this->assertFalse(SystemSettingsCatalog::has('not.a.real.key'));
    }
}
