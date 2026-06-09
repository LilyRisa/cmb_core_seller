<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Services\AdBudgetGuardrails;
use Tests\TestCase;

class AdBudgetGuardrailsTest extends TestCase
{
    public function test_clamps_below_minimum_up(): void
    {
        $this->assertSame(50000, (new AdBudgetGuardrails)->clamp(10000, 'test'));
    }

    public function test_clamps_test_above_test_max_down(): void
    {
        $g = new AdBudgetGuardrails;
        $this->assertSame($g->maxFor('test'), $g->clamp(99999999, 'test'));
    }

    public function test_scale_allows_large_budget(): void
    {
        $this->assertSame(5000000, (new AdBudgetGuardrails)->clamp(5000000, 'scale'));
    }

    public function test_zero_falls_back_to_recommended(): void
    {
        $g = new AdBudgetGuardrails;
        $this->assertSame($g->recommended('test'), $g->clamp(0, 'test'));
    }
}
