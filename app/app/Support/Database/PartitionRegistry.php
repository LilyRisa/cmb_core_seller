<?php

namespace CMBcoreSeller\Support\Database;

/**
 * The set of monthly-partitioned tables (table => partition column). Each
 * module registers its partitioned tables from its service provider's boot(),
 * e.g. PartitionRegistry::register('orders'); PartitionRegistry::register('webhook_events');
 *
 * The scheduled `db:partitions:ensure` command iterates this list to roll
 * partitions forward. Empty until the first high-volume table lands (Phase 1).
 * See docs/02-data-model/overview.md §1 rule 9.
 */
final class PartitionRegistry
{
    /** @var array<string, string> */
    private static array $tables = [];

    public static function register(string $table, string $partitionColumn = 'created_at'): void
    {
        self::$tables[$table] = $partitionColumn;
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::$tables;
    }

    public static function flush(): void
    {
        self::$tables = [];
    }
}
