<?php

use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
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

// --- Phase 1: marketplace order sync (see docs/03-domain/order-sync-pipeline.md) ---
// Polling backup: every ~10' dispatch SyncOrdersForShop for each active channel account
// (ShouldBeUnique guards against overlap). Heavy work happens in the jobs, not here.
Schedule::call(function () {
    ChannelAccount::withoutGlobalScope(TenantScope::class)
        ->where('status', ChannelAccount::STATUS_ACTIVE)
        ->orderBy('id')
        ->each(fn ($a) => SyncOrdersForShop::dispatch((int) $a->getKey()));
})->everyTenMinutes()->name('dispatch-order-sync')->onOneServer()->withoutOverlapping();

// Refresh tokens that expire soon (a stalled token kills sync).
Schedule::command('channels:refresh-expiring-tokens')->everyThirtyMinutes()->onOneServer();

// --- Phase 2: customer registry (SPEC 0002) ---
// Eventual-consistency safety net: recompute customers whose orders changed in the
// last couple of hours, in case the OrderUpserted listener failed.
Schedule::command('customers:recompute-stale --hours=2')->hourly()->onOneServer()->withoutOverlapping();

// Daily safety-net backfill: re-sync the last few days for every active shop.
Schedule::call(function () {
    ChannelAccount::withoutGlobalScope(TenantScope::class)
        ->where('status', ChannelAccount::STATUS_ACTIVE)
        ->orderBy('id')
        ->each(fn ($a) => SyncOrdersForShop::dispatch((int) $a->getKey(), now()->subDays(3)->toIso8601String(), 'poll'));
})->dailyAt('02:00')->name('backfill-recent-orders')->onOneServer();

// Prune old framework rows so the DB stays lean.
Schedule::command('queue:prune-failed --hours=336')->daily()->onOneServer();      // keep 14d of failed jobs
Schedule::command('queue:prune-batches --hours=72 --unfinished=72')->daily()->onOneServer();
Schedule::command('model:prune')->hourly()->onOneServer();                        // expired oauth_states (OAuthState is Prunable)
Schedule::command('sanctum:prune-expired --hours=24')->daily()->onOneServer();
Schedule::command('auth:clear-resets')->daily()->onOneServer();
