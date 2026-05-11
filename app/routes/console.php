<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks (run in the `scheduler` container — one instance only).
|--------------------------------------------------------------------------
| Full target list: docs/07-infra/queues-and-scheduler.md §2. Most domain
| jobs (SyncOrdersForShop, RefreshExpiringTokens, ReconcileInventory, ...)
| land in their phases; this is the Phase 0 housekeeping baseline. Heavy
| periodic work must dispatch jobs onto a queue, not run inline in the tick.
*/

// Horizon queue metrics snapshot (powers the dashboard graphs).
Schedule::command('horizon:snapshot')->everyFiveMinutes()->onOneServer();

// Roll monthly partitions forward before any writer hits a missing partition.
Schedule::command('db:partitions:ensure')->dailyAt('00:30')->onOneServer()->withoutOverlapping();

// Prune old framework rows so the DB stays lean.
Schedule::command('queue:prune-failed --hours=336')->daily()->onOneServer();      // keep 14d of failed jobs
Schedule::command('queue:prune-batches --hours=72 --unfinished=72')->daily()->onOneServer();
Schedule::command('sanctum:prune-expired --hours=24')->daily()->onOneServer();
Schedule::command('auth:clear-resets')->daily()->onOneServer();
