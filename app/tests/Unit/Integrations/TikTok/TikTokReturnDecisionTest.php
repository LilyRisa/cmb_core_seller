<?php

namespace Tests\Unit\Integrations\TikTok;

use CMBcoreSeller\Integrations\Channels\TikTok\TikTokMappers;
use PHPUnit\Framework\TestCase;

/**
 * Pins the `decision` value chosen for TikTok 202309 return approve/reject.
 * Required field — a missing/wrong value caused `[36009004] Decision is a required field`
 * (refund-only) and would cause state errors on return-and-refund.
 */
class TikTokReturnDecisionTest extends TestCase
{
    public function test_refund_only(): void
    {
        $this->assertSame('APPROVE_REFUND', TikTokMappers::returnDecision('approve', 'REFUND', 'RETURN_OR_REFUND_REQUEST_PENDING'));
        $this->assertSame('REJECT_REFUND', TikTokMappers::returnDecision('reject', 'REFUND', 'RETURN_OR_REFUND_REQUEST_PENDING'));
    }

    public function test_return_and_refund_before_buyer_ships(): void
    {
        $this->assertSame('APPROVE_RETURN', TikTokMappers::returnDecision('approve', 'RETURN_AND_REFUND', 'RETURN_OR_REFUND_REQUEST_PENDING'));
        $this->assertSame('REJECT_RETURN', TikTokMappers::returnDecision('reject', 'RETURN_AND_REFUND', 'RETURN_OR_REFUND_REQUEST_PENDING'));
    }

    public function test_return_and_refund_after_buyer_shipped(): void
    {
        $this->assertSame('APPROVE_RECEIVED_PACKAGE', TikTokMappers::returnDecision('approve', 'RETURN_AND_REFUND', 'BUYER_SHIPPED_ITEM'));
        $this->assertSame('REJECT_RECEIVED_PACKAGE', TikTokMappers::returnDecision('reject', 'RETURN_AND_REFUND', 'BUYER_SHIPPED_ITEM'));
    }

    public function test_replacement(): void
    {
        $this->assertSame('APPROVE_REPLACEMENT', TikTokMappers::returnDecision('approve', 'REPLACEMENT', 'REPLACEMENT_REQUEST_PENDING'));
        $this->assertSame('REJECT_REPLACEMENT', TikTokMappers::returnDecision('reject', 'REPLACEMENT', 'REPLACEMENT_REQUEST_PENDING'));
    }

    public function test_defaults_to_return_when_type_unknown(): void
    {
        $this->assertSame('APPROVE_RETURN', TikTokMappers::returnDecision('approve', null, null));
        $this->assertSame('REJECT_RETURN', TikTokMappers::returnDecision('reject', '', ''));
    }
}
