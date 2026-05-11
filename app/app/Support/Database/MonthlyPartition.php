<?php

namespace CMBcoreSeller\Support\Database;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Helper for tables partitioned by month on a timestamp column (PostgreSQL
 * declarative `PARTITION BY RANGE`). High-volume tables — `orders`,
 * `order_items`, `order_status_history`, `inventory_movements`,
 * `webhook_events`, `sync_runs`, `settlement_lines`, ... — use this so the
 * scheduled `db:partitions:ensure` job can roll partitions forward and old
 * ones can be archived/detached without a schema change.
 * See docs/02-data-model/overview.md §1 rule 9.
 *
 * On non-PostgreSQL connections (SQLite/MySQL used in tests / quick local dev)
 * this degrades gracefully to a plain table — partitioning is a Postgres-only
 * optimisation, not a correctness requirement.
 */
class MonthlyPartition
{
    /**
     * Create a parent table partitioned by RANGE on $partitionColumn.
     *
     * Postgres requires the partition key to be part of every unique/primary
     * key, so a migration using this should declare its primary key explicitly
     * (e.g. `$table->primary(['id', 'created_at'])`) rather than `$table->id()`
     * alone, and unique constraints must include $partitionColumn.
     *
     * @param  Closure(Blueprint):void  $columns
     */
    public static function createTable(string $table, Closure $columns, string $partitionColumn = 'created_at'): void
    {
        if (! self::isPostgres()) {
            Schema::create($table, $columns);

            return;
        }

        $connection = Schema::getConnection();
        $grammar = $connection->getSchemaGrammar();

        // Mirror Schema::create()'s blueprint wiring, then splice PARTITION BY
        // onto the CREATE TABLE statement before executing.
        $blueprint = new Blueprint($table, function (Blueprint $bp) use ($columns) {
            $bp->create();
            $columns($bp);
        });

        foreach ($blueprint->toSql($connection, $grammar) as $sql) {
            if (str_starts_with(strtolower(ltrim($sql)), 'create table')) {
                $sql = rtrim($sql, "; \t\n\r").' partition by range ('.$grammar->wrap($partitionColumn).')';
            }

            $connection->statement($sql);
        }
    }

    /** Suffix for a partition holding one month, e.g. `_y2026m05`. */
    public static function suffixFor(CarbonInterface $month): string
    {
        return sprintf('_y%04dm%02d', $month->year, $month->month);
    }

    public static function partitionName(string $table, CarbonInterface $month): string
    {
        return $table.self::suffixFor($month);
    }

    /**
     * Ensure the partition covering $month exists (idempotent). No-op off
     * Postgres. Returns the partition table name.
     */
    public static function ensurePartition(string $table, CarbonInterface $month): string
    {
        $start = CarbonImmutable::create($month->year, $month->month, 1, 0, 0, 0);
        $end = $start->addMonth();
        $name = self::partitionName($table, $start);

        if (! self::isPostgres()) {
            return $name;
        }

        $grammar = Schema::getConnection()->getSchemaGrammar();

        DB::statement(sprintf(
            "create table if not exists %s partition of %s for values from ('%s') to ('%s')",
            $grammar->wrapTable($name),
            $grammar->wrapTable($table),
            $start->toDateString(),
            $end->toDateString(),
        ));

        return $name;
    }

    /**
     * Ensure a partition for every month in [$from, $to] inclusive.
     *
     * @return list<string> partition names ensured
     */
    public static function ensureRange(string $table, CarbonInterface $from, CarbonInterface $to): array
    {
        $cursor = CarbonImmutable::create($from->year, $from->month, 1);
        $last = CarbonImmutable::create($to->year, $to->month, 1);
        $names = [];

        while ($cursor->lessThanOrEqualTo($last)) {
            $names[] = self::ensurePartition($table, $cursor);
            $cursor = $cursor->addMonth();
        }

        return $names;
    }

    private static function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
}
