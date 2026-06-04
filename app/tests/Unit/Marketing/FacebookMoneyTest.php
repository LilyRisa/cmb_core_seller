<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookMoney;
use PHPUnit\Framework\TestCase;

class FacebookMoneyTest extends TestCase
{
    public function test_zero_decimal_currency_passes_through(): void
    {
        $this->assertSame('150000', FacebookMoney::toMinorUnits(150000, 'VND'));
        $this->assertSame('1000', FacebookMoney::toMinorUnits(1000, 'JPY'));
    }

    public function test_two_decimal_currency_scaled_by_100(): void
    {
        $this->assertSame('1000', FacebookMoney::toMinorUnits(10, 'USD'));
    }

    public function test_unknown_currency_defaults_to_two_decimal(): void
    {
        $this->assertSame('500', FacebookMoney::toMinorUnits(5, 'XYZ'));
    }

    public function test_currency_code_is_case_insensitive(): void
    {
        $this->assertSame('150000', FacebookMoney::toMinorUnits(150000, 'vnd'));
    }
}
