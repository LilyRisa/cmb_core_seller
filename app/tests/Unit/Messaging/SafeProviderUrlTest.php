<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Rules\SafeProviderUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SafeProviderUrlTest extends TestCase
{
    private function fails(?string $url): bool
    {
        return Validator::make(['u' => $url], ['u' => [new SafeProviderUrl]])->fails();
    }

    public function test_accepts_public_https(): void
    {
        $this->assertFalse($this->fails('https://api.deepseek.com'));
        $this->assertFalse($this->fails(null)); // nullable: bỏ qua
    }

    public function test_rejects_http_and_internal(): void
    {
        $this->assertTrue($this->fails('http://api.openai.com'));   // không HTTPS
        $this->assertTrue($this->fails('https://localhost'));
        $this->assertTrue($this->fails('https://127.0.0.1'));
        $this->assertTrue($this->fails('https://192.168.1.10'));
        $this->assertTrue($this->fails('https://10.0.0.5'));
    }

    public function test_rejects_ipv6_loopback_literal(): void
    {
        // Literal IPv6 loopback bọc trong [ ] — trước đây lọt qua vì gethostbyname chỉ IPv4.
        $this->assertTrue($this->fails('https://[::1]'));
        $this->assertTrue($this->fails('https://[::1]:8443/v1'));
    }
}
