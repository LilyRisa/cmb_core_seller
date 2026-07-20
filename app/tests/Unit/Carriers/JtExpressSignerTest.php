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

    // Ví dụ CHÍNH THỨC từ open.jtexpress.vn/helpCenter → Authentication Tools (đọc trực tiếp 2026-07-20):
    // customerCode=084LC02438, password hồ sơ (raw)=KGC6jju1 ⇒ password gửi trong bizContent phải là
    // 4AE2DBF6527EA7C49C59EFF24F6FEA71 — trang tự liệt kê đây là "test parameters required". Khác digest
    // (chưa có golden value), đây là golden value THẬT đầu tiên xác nhận được cho J&T connector.
    public function test_hash_password_matches_jt_documented_test_credentials(): void
    {
        $this->assertSame('4AE2DBF6527EA7C49C59EFF24F6FEA71', JtExpressSigner::hashPassword('KGC6jju1'));
    }

    public function test_hash_password_is_deterministic_and_uppercase_32_hex(): void
    {
        $a = JtExpressSigner::hashPassword('any-password');
        $b = JtExpressSigner::hashPassword('any-password');

        $this->assertSame($a, $b);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{32}$/', $a);
    }
}
