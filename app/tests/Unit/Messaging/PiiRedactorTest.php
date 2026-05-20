<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\PiiRedactor;
use Tests\TestCase;

/**
 * Test `PiiRedactor` — đảm bảo PII bị redact trước khi gửi qua LLM ngoài
 * (08-security-and-privacy §6b §3).
 */
class PiiRedactorTest extends TestCase
{
    public function test_redacts_vn_phone_number(): void
    {
        $r = new PiiRedactor;
        $result = $r->redact('Liên hệ 0912345678 nha');
        $this->assertStringNotContainsString('0912345678', $result->redacted);
        $this->assertStringContainsString('[PHONE_1]', $result->redacted);
        $this->assertSame('0912345678', $result->mapping['[PHONE_1]']);
        $this->assertSame(1, $result->counts['PHONE']);
    }

    public function test_redacts_email(): void
    {
        $r = new PiiRedactor;
        $result = $r->redact('Mail: shop@example.com hoặc help@example.com');
        $this->assertStringContainsString('[EMAIL_1]', $result->redacted);
        $this->assertStringContainsString('[EMAIL_2]', $result->redacted);
        $this->assertSame(2, $result->counts['EMAIL']);
    }

    public function test_redacts_bank_account_with_keyword(): void
    {
        $r = new PiiRedactor;
        $result = $r->redact('STK 1234567890 ngân hàng Vietcombank');
        $this->assertStringContainsString('[ACCOUNT_1]', $result->redacted);
        $this->assertStringNotContainsString('1234567890', $result->redacted);
        $this->assertSame('1234567890', $result->mapping['[ACCOUNT_1]']);
    }

    public function test_does_not_redact_random_digits_without_keyword(): void
    {
        $r = new PiiRedactor;
        // 10 chữ số nhưng không phải SĐT VN (prefix sai) ⇒ không redact
        $result = $r->redact('Mã đơn 1234567890 đã giao');
        $this->assertStringContainsString('1234567890', $result->redacted);
        $this->assertSame(0, $result->counts['PHONE']);
        $this->assertSame(0, $result->counts['ACCOUNT']);
    }

    public function test_restore_brings_back_original(): void
    {
        $r = new PiiRedactor;
        $original = 'Gọi 0912345678 hoặc mail shop@example.com';
        $result = $r->redact($original);
        $restored = $result->restore($result->redacted);
        $this->assertSame($original, $restored);
    }

    public function test_has_any_pii(): void
    {
        $r = new PiiRedactor;
        $this->assertFalse($r->redact('Cảm ơn anh chị')->hasAnyPii());
        $this->assertTrue($r->redact('SĐT: 0912345678')->hasAnyPii());
    }
}
