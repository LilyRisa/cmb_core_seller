<?php

namespace Tests\Unit\Customers;

use CMBcoreSeller\Modules\Customers\Support\ReputationCalculator;
use Tests\TestCase;

class ReputationCalculatorTest extends TestCase
{
    public function test_clean_buyer_is_ok_with_full_score(): void
    {
        $r = ReputationCalculator::evaluate(['orders_completed' => 5, 'orders_cancelled' => 0, 'orders_total' => 5]);
        $this->assertSame(100, $r['score']);
        $this->assertSame('ok', $r['label']);
    }

    public function test_mixed_buyer_is_watch(): void
    {
        // 100 + min(30, 2*4) - 15*2 = 78
        $r = ReputationCalculator::evaluate(['orders_completed' => 4, 'orders_cancelled' => 2, 'orders_total' => 6]);
        $this->assertSame(78, $r['score']);
        $this->assertSame('watch', $r['label']);
    }

    public function test_serial_canceller_is_risk(): void
    {
        $r = ReputationCalculator::evaluate(['orders_cancelled' => 5, 'orders_total' => 5]);
        $this->assertSame(25, $r['score']);   // 100 - 75
        $this->assertSame('risk', $r['label']);
    }

    public function test_completed_bonus_is_capped(): void
    {
        $r = ReputationCalculator::evaluate(['orders_completed' => 20, 'orders_total' => 20]);
        $this->assertSame(100, $r['score']);  // 100 + min(30, 40) -> clamp 100
        $this->assertTrue($r['is_vip']);      // >=10 completed, 0% cancel rate
    }

    public function test_blocked_overrides_label(): void
    {
        $r = ReputationCalculator::evaluate(['orders_completed' => 5, 'orders_total' => 5], isBlocked: true);
        $this->assertSame('blocked', $r['label']);
    }

    public function test_vip_needs_low_cancellation_rate(): void
    {
        $r = ReputationCalculator::evaluate(['orders_completed' => 12, 'orders_cancelled' => 3, 'orders_total' => 15]);
        $this->assertFalse($r['is_vip']);     // 3/15 = 20% > 5%
    }
}
