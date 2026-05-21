<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\PhoneDetector;
use Tests\TestCase;

/**
 * Unit tests cho PhoneDetector — nhận diện SĐT VN trong text.
 */
class PhoneDetectorTest extends TestCase
{
    private PhoneDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new PhoneDetector;
    }

    public function test_detects_0_prefix_phone(): void
    {
        $this->assertSame('0987654321', $this->detector->firstPhone('0987654321'));
    }

    public function test_detects_plus84_prefix_phone(): void
    {
        $this->assertSame('+84987654321', $this->detector->firstPhone('+84987654321'));
    }

    public function test_detects_84_prefix_phone(): void
    {
        $this->assertSame('84987654321', $this->detector->firstPhone('84987654321'));
    }

    public function test_detects_phone_embedded_in_text(): void
    {
        $this->assertSame('0912345678', $this->detector->firstPhone('gọi mình 0912345678 nhé'));
    }

    public function test_returns_null_for_short_numeric_string(): void
    {
        $this->assertNull($this->detector->firstPhone('123'));
    }

    public function test_returns_null_for_alphabetic_string(): void
    {
        $this->assertNull($this->detector->firstPhone('abc'));
    }

    public function test_returns_null_for_13_digit_string(): void
    {
        // 13 chữ số liên tiếp — không match vì lookbehind/lookahead (?<!\d)(?!\d)
        $this->assertNull($this->detector->firstPhone('1234567890123'));
    }

    public function test_returns_null_for_bank_account_like_long_number(): void
    {
        // Số TK ngân hàng 16 chữ số — không phải SĐT VN
        $this->assertNull($this->detector->firstPhone('9704001234567890'));
    }

    public function test_has_phone_returns_true_when_phone_present(): void
    {
        $this->assertTrue($this->detector->hasPhone('SĐT: 0987654321'));
    }

    public function test_has_phone_returns_false_when_no_phone(): void
    {
        $this->assertFalse($this->detector->hasPhone('Không có số điện thoại'));
    }

    public function test_returns_null_for_null_input(): void
    {
        $this->assertNull($this->detector->firstPhone(null));
    }

    public function test_returns_null_for_empty_string(): void
    {
        $this->assertNull($this->detector->firstPhone(''));
    }

    public function test_has_phone_false_for_null(): void
    {
        $this->assertFalse($this->detector->hasPhone(null));
    }
}
