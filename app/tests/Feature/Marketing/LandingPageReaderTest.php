<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Services\LandingPageReader;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LandingPageReaderTest extends TestCase
{
    public function test_fetches_and_strips_html(): void
    {
        Http::fake(['shop.vn/*' => Http::response('<html><body><h1>Đăng ký nhận quà</h1><p>Form khuyến mãi 50%</p><script>x()</script></body></html>', 200)]);
        $text = (new LandingPageReader)->read('https://shop.vn/dk');
        $this->assertNotNull($text);
        $this->assertStringContainsString('Đăng ký nhận quà', (string) $text);
        $this->assertStringContainsString('Form khuyến mãi 50%', (string) $text);
        $this->assertStringNotContainsString('x()', (string) $text); // script bị loại
    }

    public function test_non_200_returns_null(): void
    {
        Http::fake(['*' => Http::response('nope', 404)]);
        $this->assertNull((new LandingPageReader)->read('https://shop.vn/missing'));
    }

    public function test_invalid_url_returns_null(): void
    {
        $this->assertNull((new LandingPageReader)->read('not-a-url'));
    }

    public function test_truncates_to_max_chars(): void
    {
        Http::fake(['*' => Http::response(str_repeat('a', 10000), 200)]);
        $text = (new LandingPageReader)->read('https://shop.vn/long', 500);
        $this->assertLessThanOrEqual(500, mb_strlen((string) $text));
    }
}
