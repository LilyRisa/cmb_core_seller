<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pull-side order sync for one channel account: page through orders updated
 * since `last_synced_at - overlap` (or `now - backfill_days` for a backfill),
 * upsert each, record progress in sync_runs, advance last_synced_at to the run
 * start (minus overlap) so nothing is missed. Idempotent; unique per (shop, type).
 * See docs/03-domain/order-sync-pipeline.md §3.
 */
class SyncOrdersForShop implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueForSeconds = 900;

    public function __construct(
        public int $channelAccountId,
        public ?string $sinceIso = null,
        public string $type = SyncRun::TYPE_POLL,
    ) {
        $this->onQueue('orders-sync');
    }

    public function uniqueId(): string
    {
        return "sync-orders:{$this->channelAccountId}:{$this->type}";
    }

    public function uniqueFor(): int
    {
        return $this->uniqueForSeconds;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ChannelRegistry $registry, OrderUpsertService $upsert): void
    {
        /** @var ChannelAccount|null $account */
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || ! $account->isActive() || ! $registry->has($account->provider)) {
            return;
        }

        $overlapMin = (int) config('integrations.sync.poll_overlap_minutes', 5);
        $runStart = CarbonImmutable::now();
        $since = $this->sinceIso
            ? CarbonImmutable::parse($this->sinceIso)
            : ($this->type === SyncRun::TYPE_BACKFILL
                ? $runStart->subDays((int) config('integrations.sync.backfill_days', 90))
                : ($account->last_synced_at ? CarbonImmutable::parse($account->last_synced_at)->subMinutes($overlapMin) : $runStart->subDays(7)));

        $run = SyncRun::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $account->tenant_id,
            'channel_account_id' => $account->getKey(),
            'type' => $this->type,
            'status' => SyncRun::STATUS_RUNNING,
            'started_at' => $runStart,
            'stats' => ['fetched' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        ]);

        $connector = $registry->for($account->provider);
        $cursor = null;
        $maxPages = $this->type === SyncRun::TYPE_BACKFILL ? 500 : 50;

        try {
            for ($page = 0; $page < $maxPages; $page++) {
                $auth = $account->authContext();
                try {
                    $pageResult = $connector->fetchOrders($auth, ['updatedFrom' => $since, 'cursor' => $cursor, 'pageSize' => 50]);
                } catch (Throwable $e) {
                    if ($this->maybeRefreshToken($account, $e)) {
                        $page--;

                        continue; // retry this page with the fresh token
                    }
                    throw $e;
                }

                foreach ($pageResult->items as $dto) {
                    /** @var OrderDTO $dto */
                    try {
                        $status = $connector->mapStatus($dto->rawStatus, $dto->raw);
                        $existed = $upsert->upsertWithStatus($dto, (int) $account->tenant_id, (int) $account->getKey(), 'polling', $status);
                        $run->bump(['fetched' => 1, $existed->wasRecentlyCreated ? 'created' : 'updated' => 1]);
                    } catch (Throwable $e) {
                        $run->bump(['fetched' => 1, 'errors' => 1]);
                        Log::warning('sync.upsert_failed', ['shop' => $account->external_shop_id, 'order' => $dto->externalOrderId, 'error' => $e->getMessage()]);
                    }
                }
                $run->cursor = $pageResult->nextCursor;
                $run->save();

                if (! $pageResult->hasMore || ! $pageResult->nextCursor) {
                    break;
                }
                $cursor = $pageResult->nextCursor;
            }

            // Advance the watermark to the run start (minus overlap) so updates during the run aren't missed.
            $account->forceFill(['last_synced_at' => $runStart->subMinutes($overlapMin)])->save();
            $run->finish(SyncRun::STATUS_DONE);
        } catch (Throwable $e) {
            $run->finish(SyncRun::STATUS_FAILED, $e->getMessage());
            throw $e;
        }
    }

    /** If the failure looks like an expired token, try refreshing once; on success return true to retry. */
    private function maybeRefreshToken(ChannelAccount $account, Throwable $e): bool
    {
        $authErr = method_exists($e, 'isAuthError') ? $e->isAuthError() : str_contains(strtolower($e->getMessage()), 'access_token');
        if (! $authErr) {
            return false;
        }

        return app(TokenRefresher::class)->refresh($account);
    }
}
