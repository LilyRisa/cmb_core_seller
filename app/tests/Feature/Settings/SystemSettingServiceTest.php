<?php

namespace Tests\Feature\Settings;

use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class SystemSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): SystemSettingService
    {
        return app(SystemSettingService::class);
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertSame('fallback', $this->svc()->get('marketplace.tiktok.app_key', 'fallback'));
    }

    public function test_set_then_get_returns_value(): void
    {
        $this->svc()->set('sync.poll_interval_minutes', 7);
        $this->assertSame(7, $this->svc()->get('sync.poll_interval_minutes'));
    }

    public function test_set_secret_encrypts_value_in_db(): void
    {
        $this->svc()->set('marketplace.tiktok.app_secret', 'plain-secret');
        $row = SystemSetting::query()->where('key', 'marketplace.tiktok.app_secret')->first();
        $this->assertNotSame('plain-secret', $row->value);
        $this->assertSame('plain-secret', $this->svc()->get('marketplace.tiktok.app_secret'));
    }

    public function test_set_clears_cache_so_next_get_reflects_new_value(): void
    {
        $this->svc()->set('marketplace.tiktok.sandbox', true);
        $this->assertTrue($this->svc()->get('marketplace.tiktok.sandbox'));

        // Manually set a stale cache to verify forget() ran on set():
        Cache::forever('system_settings:all', [
            'marketplace.tiktok.sandbox' => ['value' => true, 'type' => 'bool', 'is_secret' => false],
        ]);
        // Now call set() — should forget the cache.
        $this->svc()->set('marketplace.tiktok.sandbox', false);
        $this->assertFalse($this->svc()->get('marketplace.tiktok.sandbox'));
    }

    public function test_set_rejects_unknown_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc()->set('nope.invalid', '1');
    }

    public function test_helper_function_returns_value(): void
    {
        $this->svc()->set('sync.backfill_days', 42);
        $this->assertSame(42, system_setting('sync.backfill_days'));
    }

    public function test_helper_returns_default_for_unwhitelisted_key(): void
    {
        $this->assertSame('def', system_setting('not.in.catalog', 'def'));
    }

    public function test_cast_bool_from_string_true(): void
    {
        $this->svc()->set('marketplace.lazada.sandbox', 'true');
        $this->assertTrue($this->svc()->get('marketplace.lazada.sandbox'));
    }

    public function test_cast_int_returns_int(): void
    {
        $this->svc()->set('throttle.tiktok_per_min', '600');
        $this->assertSame(600, $this->svc()->get('throttle.tiktok_per_min'));
    }

    public function test_forget_removes_db_row_and_returns_to_default(): void
    {
        $this->svc()->set('sync.backfill_days', 7);
        $this->assertSame(7, $this->svc()->get('sync.backfill_days'));
        $this->svc()->forget('sync.backfill_days');
        $this->assertSame(99, $this->svc()->get('sync.backfill_days', 99));
    }
}
