<?php

namespace Tests\Unit;

use CMBcoreSeller\Support\Database\MonthlyPartition;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyPartitionTest extends TestCase
{
    public function test_suffix_and_partition_name(): void
    {
        $may = Carbon::create(2026, 5, 17);

        $this->assertSame('_y2026m05', MonthlyPartition::suffixFor($may));
        $this->assertSame('orders_y2026m05', MonthlyPartition::partitionName('orders', $may));
        $this->assertSame('_y2026m12', MonthlyPartition::suffixFor(Carbon::create(2026, 12, 1)));
        $this->assertSame('_y2027m01', MonthlyPartition::suffixFor(Carbon::create(2027, 1, 31)));
    }

    public function test_ensure_range_covers_every_month_inclusive(): void
    {
        // The test connection is sqlite, so ensureRange() doesn't touch the DB —
        // it just yields the partition names it would create on Postgres.
        $names = MonthlyPartition::ensureRange('orders', Carbon::create(2025, 11, 10), Carbon::create(2026, 2, 1));

        $this->assertSame([
            'orders_y2025m11',
            'orders_y2025m12',
            'orders_y2026m01',
            'orders_y2026m02',
        ], $names);
    }
}
