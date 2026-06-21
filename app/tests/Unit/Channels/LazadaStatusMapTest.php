<?php

namespace Tests\Unit\Channels;

use CMBcoreSeller\Integrations\Channels\Lazada\LazadaStatusMap;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Tests\TestCase;

/**
 * Đối chiếu sơ đồ "Order Status Flow" chính chủ Lazada (open.lazada.com) — các status hậu-giao / reverse
 * trước đây map nhầm về Pending (gây "lùi trạng thái bất thường") hoặc Shipped (trả hàng không vào Trả/hoàn).
 */
class LazadaStatusMapTest extends TestCase
{
    /**
     * @dataProvider statusCases
     */
    public function test_to_standard_maps_real_lazada_statuses(string $raw, S $expected): void
    {
        $this->assertSame($expected, LazadaStatusMap::toStandard($raw), "Lazada '{$raw}' phải map về {$expected->value}");
    }

    /** @return array<string,array{0:string,1:S}> */
    public static function statusCases(): array
    {
        return [
            // forward
            'unpaid' => ['unpaid', S::Unpaid],
            'pending' => ['pending', S::Pending],
            'repacked' => ['repacked', S::Processing],          // trước: ReadyToShip (sai)
            'packed' => ['packed', S::Processing],
            'ready_to_ship' => ['ready_to_ship', S::ReadyToShip],
            'ready_to_ship_pending' => ['ready_to_ship_pending', S::ReadyToShip],
            'shipped' => ['shipped', S::Shipped],
            'delivered' => ['delivered', S::Delivered],
            'confirmed' => ['confirmed', S::Completed],
            // delivery problems
            'failed_delivery' => ['failed_delivery', S::DeliveryFailed],
            'lost_by_3pl' => ['lost_by_3pl', S::DeliveryFailed],   // trước: Pending (sai)
            'damaged_by_3pl' => ['damaged_by_3pl', S::DeliveryFailed], // trước: Pending (sai)
            // reverse / trả-hoàn — phải vào Trả/hoàn
            'shipped_back' => ['shipped_back', S::Returning],
            'shipped_back_failed' => ['shipped_back_failed', S::Returning],
            'shipped_back_success' => ['shipped_back_success', S::ReturnedRefunded], // trước: Shipped (sai)
            'returned' => ['returned', S::ReturnedRefunded],
            'package_scrapped' => ['package_scrapped', S::ReturnedRefunded],         // trước: Pending (sai)
            'canceled' => ['canceled', S::Cancelled],
        ];
    }

    public function test_collapse_treats_reverse_success_as_reverse(): void
    {
        // Đơn nhiều item đều đã trả về thành công ⇒ collapse phải chọn reverse (không rơi về forward/pending).
        $this->assertSame('shipped_back_success', LazadaStatusMap::collapse(['shipped_back_success', 'returned']));
        $this->assertSame(S::ReturnedRefunded, LazadaStatusMap::toStandard(LazadaStatusMap::collapse(['shipped_back_success', 'shipped_back_success'])));
    }
}
