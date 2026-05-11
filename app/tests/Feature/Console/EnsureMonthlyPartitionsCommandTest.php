<?php

namespace Tests\Feature\Console;

use CMBcoreSeller\Support\Database\PartitionRegistry;
use Tests\TestCase;

class EnsureMonthlyPartitionsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        PartitionRegistry::flush();
        parent::tearDown();
    }

    public function test_command_is_a_no_op_with_no_registered_tables(): void
    {
        PartitionRegistry::flush();

        $this->artisan('db:partitions:ensure')
            ->expectsOutputToContain('No partitioned tables registered yet')
            ->assertExitCode(0);
    }

    public function test_command_reports_ensured_partitions_for_registered_tables(): void
    {
        PartitionRegistry::flush();
        PartitionRegistry::register('orders');
        PartitionRegistry::register('webhook_events', 'received_at');

        // On sqlite (test env) nothing is actually created; the command still
        // resolves the partition names and exits cleanly.
        $this->artisan('db:partitions:ensure --months=2')
            ->assertExitCode(0);

        $this->assertArrayHasKey('orders', PartitionRegistry::all());
        $this->assertSame('received_at', PartitionRegistry::all()['webhook_events']);
    }
}
