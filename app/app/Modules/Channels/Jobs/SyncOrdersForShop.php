<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
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
 * Pull-side order sync for one channel account. Three modes (see docs/03-domain/order-sync-pipeline.md §3):
 *
 *  - `poll`        — time-window incremental (updatedFrom = last_synced_at − overlap). Default.
 *  - `backfill`    — time-window wide (now − backfill_days). Triggered on first connect.
 *  - `unprocessed` — status-based: iterate `connector.unprocessedRawStatuses()`, page each. NO time
 *                    window — for pulling all open orders (carrier-not-yet-handed) regardless of age.
 *
 * Idempotent; unique per (shop, type). `unprocessed` does NOT bump `last_synced_at` (it's not a
 * time-window sync).
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

        $runStart = CarbonImmutable::now();
        $run = SyncRun::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $account->tenant_id,
            'channel_account_id' => $account->getKey(),
            'type' => $this->type,
            'status' => SyncRun::STATUS_RUNNING,
            'started_at' => $runStart,
            'stats' => ['fetched' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        ]);

        try {
            if ($this->type === SyncRun::TYPE_UNPROCESSED) {
                $this->runUnprocessed($account, $registry, $upsert, $run);
            } else {
                $this->runTimeWindow($account, $registry, $upsert, $run, $runStart);
            }
            $run->finish(SyncRun::STATUS_DONE);
        } catch (Throwable $e) {
            $run->finish(SyncRun::STATUS_FAILED, $e->getMessage());
            throw $e;
        }
    }

    /** Original time-window pull (poll/backfill). */
    private function runTimeWindow(ChannelAccount $account, ChannelRegistry $registry, OrderUpsertService $upsert, SyncRun $run, CarbonImmutable $runStart): void
    {
        $overlapMin = (int) config('integrations.sync.poll_overlap_minutes', 5);
        $since = $this->sinceIso
            ? CarbonImmutable::parse($this->sinceIso)
            : ($this->type === SyncRun::TYPE_BACKFILL
                ? $runStart->subDays((int) config('integrations.sync.backfill_days', 90))
                : ($account->last_synced_at ? CarbonImmutable::parse($account->last_synced_at)->subMinutes($overlapMin) : $runStart->subDays(7)));

        $connector = $registry->for($account->provider);
        $maxPages = $this->type === SyncRun::TYPE_BACKFILL ? 500 : 50;
        $this->pageThrough($account, $connector, $upsert, $run, fn ($cursor) => $connector->fetchOrders($account->authContext(), [
            'updatedFrom' => $since, 'cursor' => $cursor, 'pageSize' => 50,
        ]), $maxPages);

        // Advance the watermark to the run start (minus overlap) so updates during the run aren't missed.
        // Chỉ áp cho time-window sync — unprocessed không update để khỏi nhiễu poll thường.
        $account->forceFill(['last_synced_at' => $runStart->subMinutes($overlapMin)])->save();
    }

    /**
     * Status-based pull — iterate `connector.unprocessedRawStatuses()`, page each. Lazada/TikTok
     * yêu cầu **một trong** `update_after`/`created_after` ⇒ ta cấp `updatedFrom` lùi rất xa
     * (`unprocessed_lookback_days`, mặc định 365 ngày) — đủ phủ mọi đơn còn đang xử lý. Lazada
     * `/orders/get?status=X` chỉ nhận 1 status/lần ⇒ phải lặp ngoài. Connector return `[]` ⇒ no-op.
     */
    private function runUnprocessed(ChannelAccount $account, ChannelRegistry $registry, OrderUpsertService $upsert, SyncRun $run): void
    {
        $connector = $registry->for($account->provider);
        $statuses = $connector->unprocessedRawStatuses();
        if ($statuses === []) {
            return; // connector chưa support — bỏ qua, không lỗi
        }
        $lookbackDays = (int) config('integrations.sync.unprocessed_lookback_days', 365);
        $since = CarbonImmutable::now()->subDays(max(1, $lookbackDays));
        $perStatusMaxPages = (int) config('integrations.sync.unprocessed_max_pages_per_status', 200);
        foreach ($statuses as $status) {
            $this->pageThrough($account, $connector, $upsert, $run, fn ($cursor) => $connector->fetchOrders($account->authContext(), [
                'statuses' => [$status], 'updatedFrom' => $since, 'cursor' => $cursor, 'pageSize' => 50,
            ]), $perStatusMaxPages);
        }
    }

    /**
     * Common pagination loop: gọi `$fetch($cursor)` cho tới khi hết page hoặc đạt `$maxPages`. Mỗi
     * page → upsert từng order. Auto-refresh token khi gặp lỗi auth. Lỗi từng đơn không chặn batch.
     *
     * @param  callable(?string): Page  $fetch
     */
    private function pageThrough(ChannelAccount $account, $connector, OrderUpsertService $upsert, SyncRun $run, callable $fetch, int $maxPages): void
    {
        $cursor = null;
        for ($page = 0; $page < $maxPages; $page++) {
            try {
                $pageResult = $fetch($cursor);
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
