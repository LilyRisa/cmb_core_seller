<?php

namespace Tests\Unit\Channels;

use CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokMappers;
use PHPUnit\Framework\TestCase;

/**
 * SPEC 0016 — TikTokMappers::settlement đối chiếu tài liệu Finance
 * (tailieuapi_itiktok_shopee_lazada/tiktok/finance/). Xác minh transaction được tách
 * thành các dòng cấu phần đúng cho CẢ schema 202309 (phẳng) lẫn 202501 (top-level).
 */
class TikTokSettlementMapperTest extends TestCase
{
    /** @param list<SettlementLineDTO> $lines */
    private function line(array $lines, string $feeType): ?SettlementLineDTO
    {
        foreach ($lines as $l) {
            if ($l->feeType === $feeType) {
                return $l;
            }
        }

        return null;
    }

    public function test_maps_202309_flat_order_transaction_into_component_lines(): void
    {
        $st = ['id' => 'STMT1', 'statement_time' => 1685548800, 'currency' => 'GBP', 'settlement_amount' => '130'];
        $tx = [
            'id' => 'TX1', 'type' => 'ORDER', 'order_id' => 'ORD1', 'order_create_time' => 1685500000,
            'revenue_amount' => '200', 'fee_amount' => '-30', 'shipping_fee_amount' => '-40',
            'adjustment_amount' => '0', 'settlement_amount' => '130',
        ];

        $dto = TikTokMappers::settlement($st, [$tx]);

        // 3 dòng: revenue / fee_tax(commission) / shipping. adjustment=0 ⇒ bỏ.
        $this->assertCount(3, $dto->lines);
        $this->assertSame('STMT1', $dto->externalId);
        $this->assertSame('GBP', $dto->currency);
        $this->assertSame(130, $dto->totalPayout);
        $this->assertSame(200, $dto->totalRevenue);
        $this->assertSame(-30, $dto->totalFee);
        $this->assertSame(-40, $dto->totalShippingFee);

        $rev = $this->line($dto->lines, SettlementLineDTO::TYPE_REVENUE);
        $this->assertNotNull($rev);
        $this->assertSame(200, $rev->amount);
        $this->assertSame('ORD1', $rev->externalOrderId);
        $this->assertSame('TX1', $rev->externalLineId);
        $this->assertSame(-30, $this->line($dto->lines, SettlementLineDTO::TYPE_COMMISSION)?->amount);
        $this->assertSame(-40, $this->line($dto->lines, SettlementLineDTO::TYPE_SHIPPING_FEE)?->amount);
    }

    public function test_maps_202501_breakdown_order_transaction(): void
    {
        $st = ['id' => 'STMT2', 'create_time' => 1685548800, 'currency' => 'USD', 'total_settlement_amount' => '65'];
        $tx = [
            'id' => 'TX2', 'type' => 'ORDER', 'order_id' => 'ORD2', 'order_create_time' => 1685500000,
            'revenue_amount' => '100', 'fee_tax_amount' => '-20', 'shipping_cost_amount' => '-10',
            'adjustment_amount' => '-5', 'settlement_amount' => '65',
        ];

        $dto = TikTokMappers::settlement($st, [$tx]);

        // 4 dòng: revenue / fee_tax / shipping / adjustment.
        $this->assertCount(4, $dto->lines);
        $this->assertSame(65, $dto->totalPayout);
        $this->assertSame(100, $this->line($dto->lines, SettlementLineDTO::TYPE_REVENUE)?->amount);
        $this->assertSame(-20, $this->line($dto->lines, SettlementLineDTO::TYPE_COMMISSION)?->amount);
        $this->assertSame(-10, $this->line($dto->lines, SettlementLineDTO::TYPE_SHIPPING_FEE)?->amount);
        $this->assertSame(-5, $this->line($dto->lines, SettlementLineDTO::TYPE_ADJUSTMENT)?->amount);
    }

    public function test_maps_adjustment_type_transaction_into_single_line(): void
    {
        $st = ['id' => 'STMT3', 'statement_time' => 1685548800, 'currency' => 'VND', 'settlement_amount' => '-50'];
        $tx = ['id' => 'ADJ1', 'type' => 'PLATFORM_PENALTY', 'adjustment_id' => 'ADJ1', 'adjustment_order_id' => 'ORD9', 'adjustment_amount' => '-50'];

        $dto = TikTokMappers::settlement($st, [$tx]);

        $this->assertCount(1, $dto->lines);
        $line = $dto->lines[0];
        $this->assertSame(SettlementLineDTO::TYPE_OTHER, $line->feeType); // penalty không khớp nhóm cụ thể ⇒ other
        $this->assertSame(-50, $line->amount);
        $this->assertSame('ORD9', $line->externalOrderId);
        $this->assertSame('ADJ1', $line->externalLineId);
    }

    public function test_zero_amount_components_are_skipped(): void
    {
        $st = ['id' => 'STMT4', 'statement_time' => 1685548800, 'currency' => 'VND', 'settlement_amount' => '0'];
        $tx = ['id' => 'TX4', 'type' => 'ORDER', 'order_id' => 'ORD4', 'revenue_amount' => '0', 'fee_amount' => '0', 'shipping_fee_amount' => '0', 'settlement_amount' => '0'];

        $dto = TikTokMappers::settlement($st, [$tx]);

        $this->assertSame([], $dto->lines);
    }
}
