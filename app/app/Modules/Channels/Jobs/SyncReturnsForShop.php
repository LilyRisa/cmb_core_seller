<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Orders\Contracts\ReturnUpsertContract;
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
 * Pull-side sync of after-sales (return + cancel) records for one channel account. Poll mỗi ~15' +
 * trigger từ webhook (return_update / order_cancel). Window = now − `returns_lookback_days` (mặc định 90)
 * theo update_time; upsert idempotent qua {@see ReturnUpsertContract}. Connector chưa hỗ trợ `returns.fetch`
 * ⇒ no-op. See SPEC 0025.
 */
class SyncReturnsForShop implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueForSeconds = 600;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('orders-sync');
    }

    public function uniqueId(): string
    {
        return "sync-returns:{$this->channelAccountId}";
    }

    public function uniqueFor(): int
    {
        return $this->uniqueForSeconds;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ChannelRegistry $registry, ReturnUpsertContract $upsert, TokenRefresher $tokens): void
    {
        /** @var ChannelAccount|null $account */
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || ! $account->isActive() || ! $registry->has($account->provider)) {
            return;
        }
        $connector = $registry->for($account->provider);
        if (! $connector->supports('returns.fetch')) {
            return; // connector chưa hỗ trợ — bỏ qua, không lỗi
        }

        $lookbackDays = (int) config('integrations.sync.returns_lookback_days', 90);
        $since = CarbonImmutable::now()->subDays(max(1, $lookbackDays));
        $maxPages = (int) config('integrations.sync.returns_max_pages', 50);

        $this->pageThrough($account, $upsert, $tokens, fn ($cursor) => $connector->fetchReturns($account->authContext(), [
            'updatedFrom' => $since, 'cursor' => $cursor, 'pageSize' => 50,
        ]), $maxPages);

        $this->pageThrough($account, $upsert, $tokens, fn ($cursor) => $connector->fetchCancellations($account->authContext(), [
            'updatedFrom' => $since, 'cursor' => $cursor, 'pageSize' => 50,
        ]), $maxPages);
    }

    /**
     * @param  callable(?string): Page  $fetch
     */
    private function pageThrough(ChannelAccount $account, ReturnUpsertContract $upsert, TokenRefresher $tokens, callable $fetch, int $maxPages): void
    {
        $cursor = null;
        for ($page = 0; $page < $maxPages; $page++) {
            try {
                $pageResult = $fetch($cursor);
            } catch (Throwable $e) {
                $authErr = method_exists($e, 'isAuthError') ? $e->isAuthError() : str_contains(strtolower($e->getMessage()), 'access_token');
                if ($authErr && $tokens->refresh($account)) {
                    $page--;

                    continue;
                }
                throw $e;
            }

            foreach ($pageResult->items as $dto) {
                /** @var ReturnDTO $dto */
                try {
                    $upsert->upsert($dto, (int) $account->tenant_id, (int) $account->getKey(), 'polling');
                } catch (Throwable $e) {
                    Log::warning('returns.upsert_failed', ['shop' => $account->external_shop_id, 'return' => $dto->externalReturnId, 'error' => $e->getMessage()]);
                }
            }

            if (! $pageResult->hasMore || ! $pageResult->nextCursor) {
                break;
            }
            $cursor = $pageResult->nextCursor;
        }
    }
}
