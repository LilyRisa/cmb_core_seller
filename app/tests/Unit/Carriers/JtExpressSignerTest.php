<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressSigner;
use Tests\TestCase;

class JtExpressSignerTest extends TestCase
{
    // J&T docs: "digest=base64(md5(business params Json+privateKey))" — first MD5 to byte array, then
    // base64-encode. No confirmed expected value exists yet (no real UAT account) — these tests assert
    // the documented PROPERTIES of the formula, not a specific golden output. See Global Constraints.

    public function test_sign_is_deterministic_for_same_input(): void
    {
        $a = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');
        $b = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');

        $this->assertSame($a, $b);
    }

    public function test_sign_changes_when_biz_content_changes(): void
    {
        $a = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');
        $b = JtExpressSigner::sign('{"customerCode":"024E000015"}', 'Z354nbj1');

        $this->assertNotSame($a, $b);
    }

    public function test_sign_changes_when_private_key_changes(): void
    {
        $a = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');
        $b = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'DIFFERENT-KEY');

        $this->assertNotSame($a, $b);
    }

    public function test_sign_returns_base64_of_a_16_byte_md5_digest(): void
    {
        $digest = JtExpressSigner::sign('{"a":1}', 'key');
        $raw = base64_decode($digest, true);

        $this->assertNotFalse($raw, 'digest phải là base64 hợp lệ');
        $this->assertSame(16, strlen($raw), 'MD5 raw digest luôn 16 byte');
    }
}
