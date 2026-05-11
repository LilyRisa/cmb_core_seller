<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Support\Database\MonthlyPartition;
use CMBcoreSeller\Support\Database\PartitionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Ensures the current and next month's partitions exist for every table in
 * PartitionRegistry. Runs daily from the scheduler (see routes/console.php) so
 * writes never hit a missing partition; safe to run by hand and idempotent.
 * No-op on non-PostgreSQL connections. See docs/07-infra/queues-and-scheduler.md §2.
 */
class EnsureMonthlyPartitions extends Command
{
    protected $signature = 'db:partitions:ensure
        {--months=1 : How many future months to provision (in addition to the current month)}';

    protected $description = 'Create the current + upcoming monthly partitions for partitioned tables';

    public function handle(): int
    {
        $tables = PartitionRegistry::all();

        if ($tables === []) {
            $this->info('No partitioned tables registered yet — nothing to do.');

            return self::SUCCESS;
        }

        $from = Carbon::now()->startOfMonth();
        $to = $from->copy()->addMonths(max(0, (int) $this->option('months')));

        foreach ($tables as $table => $_column) {
            $names = MonthlyPartition::ensureRange($table, $from, $to);
            $this->line(sprintf('  <info>%s</info>: %s', $table, implode(', ', $names)));
        }

        $this->info(sprintf('Ensured partitions for %d table(s) through %s.', count($tables), $to->format('Y-m')));

        return self::SUCCESS;
    }
}
