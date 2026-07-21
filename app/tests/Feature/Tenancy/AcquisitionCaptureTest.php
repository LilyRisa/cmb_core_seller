<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcquisitionCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_stores_utm_and_server_observed_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van B',
            'email' => 'b@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'event_id' => 'evt-123',
            'acquisition' => [
                'utm_source' => 'facebook',
                'utm_campaign' => 'summer_sale',
                'fbclid' => 'FBCLID_ABC',
                'landing_page' => '/pricing',
            ],
        ], ['User-Agent' => 'TestAgent/1.0']);

        $response->assertCreated();

        $tenant = Tenant::where('name', 'Nguyen Van B Shop')->firstOrFail();
        $this->assertSame('facebook', $tenant->acquisition['utm_source']);
        $this->assertSame('summer_sale', $tenant->acquisition['utm_campaign']);
        $this->assertSame('FBCLID_ABC', $tenant->acquisition['fbclid']);
        $this->assertSame('evt-123', $tenant->acquisition['event_id']);
        $this->assertSame('TestAgent/1.0', $tenant->acquisition['user_agent']);
        $this->assertNotEmpty($tenant->acquisition['ip']);
        $this->assertNotEmpty($tenant->acquisition['captured_at']);
    }

    public function test_register_without_acquisition_still_succeeds(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van C',
            'email' => 'c@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();
        $tenant = Tenant::where('name', 'Nguyen Van C Shop')->firstOrFail();
        $this->assertNull($tenant->acquisition['utm_source'] ?? null);
        $this->assertNotEmpty($tenant->acquisition['captured_at']);
    }

    /**
     * Anti-spoofing guarantee: `ip`/`user_agent` must ALWAYS come from the server-observed
     * request, never from client-supplied `acquisition.*` payload values. A client that tries
     * to smuggle spoofed values under `acquisition.ip` / `acquisition.user_agent` must have
     * them silently dropped (they aren't even in the validated field list) and overwritten by
     * the real request's IP / `User-Agent` header.
     */
    public function test_register_ignores_client_supplied_ip_and_user_agent_spoofing_attempt(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van D',
            'email' => 'd@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'ip' => '9.9.9.9',
            'acquisition' => [
                'ip' => '9.9.9.9',
                'user_agent' => 'SpoofedAgent/1.0',
                'utm_source' => 'facebook',
            ],
        ], ['User-Agent' => 'RealAgent/2.0']);

        $response->assertCreated();

        $tenant = Tenant::where('name', 'Nguyen Van D Shop')->firstOrFail();
        $this->assertNotSame('9.9.9.9', $tenant->acquisition['ip']);
        $this->assertNotSame('SpoofedAgent/1.0', $tenant->acquisition['user_agent']);
        $this->assertSame('RealAgent/2.0', $tenant->acquisition['user_agent']);
        $this->assertNotEmpty($tenant->acquisition['ip']);
    }

    /**
     * `acquisition.referrer` = `document.referrer` bắt tự động phía trình duyệt — người đăng ký
     * không nhìn thấy hay chỉnh sửa được giá trị này. Một field theo-dõi best-effort không được
     * phép chặn đăng ký thật (422) chỉ vì trang giới thiệu trả referrer dài hơn 255 ký tự; phải
     * TRUNCATE lặng lẽ về đúng 255 ký tự thay vì từ chối request.
     */
    public function test_register_truncates_overlong_referrer_instead_of_rejecting(): void
    {
        $longReferrer = str_repeat('a', 400);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van F',
            'email' => 'f@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'acquisition' => [
                'utm_source' => 'facebook',
                'referrer' => $longReferrer,
            ],
        ]);

        $response->assertCreated();

        $tenant = Tenant::where('name', 'Nguyen Van F Shop')->firstOrFail();
        $this->assertSame(255, strlen($tenant->acquisition['referrer']));
        $this->assertSame(str_repeat('a', 255), $tenant->acquisition['referrer']);
    }
}
