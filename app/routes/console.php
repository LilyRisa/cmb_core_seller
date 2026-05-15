<?php

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\FetchChannelListings;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Fulfillment\Jobs\SyncShipmentTracking;
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

// Every 30': pull unprocessed orders (carrier-not-yet-handed: pending / ready_to_ship / packed) by
// STATUS, ignoring time window. Catches old open orders that fall outside the 10-min time-poll
// window. See docs/03-domain/order-sync-pipeline.md §3.3.
Schedule::call(function () {
    ChannelAccount::withoutGlobalScope(TenantScope::class)
        ->where('status', ChannelAccount::STATUS_ACTIVE)
        ->orderBy('id')
        ->each(fn ($a) => SyncOrdersForShop::dispatch((int) $a->getKey(), null, SyncRun::TYPE_UNPROCESSED));
})->everyThirtyMinutes()->name('sync-unprocessed-orders')->onOneServer()->withoutOverlapping();

// Daily: refresh channel listings for shops that support it (then auto-match SKUs) — Phase 2 (SPEC 0003).
Schedule::call(function () {
    $registry = app(ChannelRegistry::class);
    ChannelAccount::withoutGlobalScope(TenantScope::class)
        ->where('status', ChannelAccount::STATUS_ACTIVE)->orderBy('id')
        ->each(function ($a) use ($registry) {
            if ($registry->has($a->provider) && $registry->for($a->provider)->supports('listings.fetch')) {
                FetchChannelListings::dispatch((int) $a->getKey());
            }
        });
})->dailyAt('03:30')->name('fetch-channel-listings')->onOneServer();

// Every 30': poll carriers for tracking updates on in-flight shipments — Phase 3 (SPEC 0006).
Schedule::job(new SyncShipmentTracking)->everyThirtyMinutes()->name('sync-shipment-tracking')->onOneServer()->withoutOverlapping();

// --- Phase 6.4: Billing SaaS (SPEC 0018) ---
// Mỗi ngày 04:00: áp luật hết hạn / grace 7 ngày / fallback trial cho subscriptions.
Schedule::command('subscriptions:check-expiring')->dailyAt('04:00')->onOneServer()->withoutOverlapping();
// Mỗi giờ: lưới an toàn — recompute usage_counters cho mọi tenant (phòng listener miss).
Schedule::command('billing:recompute-usage')->hourly()->onOneServer()->withoutOverlapping();
// SPEC 0020 — Mỗi giờ: phát hiện tenant vượt hạn mức + set timer ân hạn 2 ngày.
Schedule::command('subscriptions:check-over-quota')->hourly()->onOneServer()->withoutOverlapping();

// Prune old framework rows so the DB stays lean.
Schedule::command('queue:prune-failed --hours=336')->daily()->onOneServer();      // keep 14d of failed jobs
Schedule::command('queue:prune-batches --hours=72 --unfinished=72')->daily()->onOneServer();
Schedule::command('model:prune')->hourly()->onOneServer();                        // expired oauth_states (OAuthState is Prunable)
Schedule::command('sanctum:prune-expired --hours=24')->daily()->onOneServer();
Schedule::command('auth:clear-resets')->daily()->onOneServer();
