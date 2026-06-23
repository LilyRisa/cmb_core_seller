<?php

namespace Tests\Feature\Http;

use CMBcoreSeller\Http\Middleware\TrustHttpsFromConfig;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * SPEC 0022 — lớp phòng thủ scheme cho signed URL khi sau proxy http (Cloudflare + NPM).
 */
class TrustHttpsFromConfigTest extends TestCase
{
    public function test_forces_https_when_app_url_is_https(): void
    {
        // Middleware chỉ hoạt động ngoài local/testing ⇒ giả lập production cho test.
        $this->app['env'] = 'production';
        config(['app.url' => 'https://app.example.com']);

        $request = Request::create('http://app.example.com/api/v1/health', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'http');

        (new TrustHttpsFromConfig)->handle($request, fn ($r) => response('ok'));

        $this->assertSame('https', $request->headers->get('X-Forwarded-Proto'));
        $this->assertSame('on', $request->server->get('HTTPS'));
        $this->assertStringStartsWith('https://', url('/foo'));
    }

    public function test_noop_when_app_url_is_http(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'http://localhost']);

        $request = Request::create('http://localhost/x', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'http');

        (new TrustHttpsFromConfig)->handle($request, fn ($r) => response('ok'));

        $this->assertSame('http', $request->headers->get('X-Forwarded-Proto'));
    }

    public function test_noop_in_testing_env_even_if_https(): void
    {
        // Bảo vệ: trong suite (env=testing, app.url có thể là https) middleware phải trơ.
        config(['app.url' => 'https://app.example.com']);

        $request = Request::create('http://app.example.com/x', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'http');

        (new TrustHttpsFromConfig)->handle($request, fn ($r) => response('ok'));

        $this->assertSame('http', $request->headers->get('X-Forwarded-Proto'));
    }
}
