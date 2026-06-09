<?php

namespace Tests\Feature\Marketing;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Marketing\Services\ScheduleOptimizer;
use Tests\TestCase;

class ScheduleOptimizerTest extends TestCase
{
    private const TZ = 'Asia/Ho_Chi_Minh';

    public function test_near_midnight_defers_start_to_next_day(): void
    {
        $now = CarbonImmutable::parse('2026-06-10 23:30', self::TZ);
        $start = (new ScheduleOptimizer)->recommendedStart($now, self::TZ);
        $this->assertSame('2026-06-11 00:00', $start->setTimezone(self::TZ)->format('Y-m-d H:i'));
    }

    public function test_daytime_starts_now(): void
    {
        $now = CarbonImmutable::parse('2026-06-10 10:00', self::TZ);
        $start = (new ScheduleOptimizer)->recommendedStart($now, self::TZ);
        $this->assertSame('2026-06-10 10:00', $start->setTimezone(self::TZ)->format('Y-m-d H:i'));
    }

    public function test_risk_warning_when_close_to_midnight(): void
    {
        $start = CarbonImmutable::parse('2026-06-10 23:30', self::TZ);
        $this->assertNotNull((new ScheduleOptimizer)->riskWarning($start, self::TZ));
    }

    public function test_no_warning_during_day(): void
    {
        $start = CarbonImmutable::parse('2026-06-10 09:00', self::TZ);
        $this->assertNull((new ScheduleOptimizer)->riskWarning($start, self::TZ));
    }
}
