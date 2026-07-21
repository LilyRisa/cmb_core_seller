<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * QUEUE_CONNECTION=sync trong phpunit.xml ⇒ listener `ShouldQueue` chạy ngay trong
 * cùng request lúc test — không cần giả lập queue riêng.
 */
class RegisterReportsToMetaCapiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_reports_complete_registration_when_growth_pixel_enabled(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van D', 'email' => 'd@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v25.0/PIXEL_1/events'
            && $request->data()['data'][0]['user_data']['em'][0] === hash('sha256', 'd@example.com'));
    }

    public function test_register_does_not_call_meta_when_growth_pixel_disabled(): void
    {
        Http::fake();
        app(SystemSettingService::class)->set('growth.facebook.enabled', false);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van E', 'email' => 'e@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();

        Http::assertNothingSent();
    }
}
