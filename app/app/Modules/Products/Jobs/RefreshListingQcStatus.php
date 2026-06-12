<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Jobs;

use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Re-check trạng thái xét duyệt (QC) của các bản nháp đã đẩy lên một shop và còn
 * đang chờ duyệt (REVIEWING/PUSHING). Gọi {@see ProductPublishingConnector::getListingStatus()}
 * rồi map về trạng thái nháp ({@see ListingDraftService::statusFromRaw()}).
 *
 * Chạy khi có webhook `product_update` (qua {@see MarketplaceProductUpdated}) và định
 * kỳ làm lưới an toàn (webhook untrusted ⇒ luôn có poll backup). Idempotent.
 */
class RefreshListingQcStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('listings');
    }

    public function handle(PublisherRegistry $pubs): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || ! $pubs->has($account->provider)) {
            return;
        }

        $drafts = ListingDraft::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $this->channelAccountId)
            ->whereIn('status', [ListingDraft::STATUS_REVIEWING, ListingDraft::STATUS_PUSHING])
            ->whereNotNull('external_item_id')
            ->get();

        if ($drafts->isEmpty()) {
            return;
        }

        $auth = $account->authContext();
        $connector = $pubs->for($account->provider);

        foreach ($drafts as $draft) {
            try {
                $status = $connector->getListingStatus($auth, (string) $draft->external_item_id);
                $mapped = ListingDraftService::statusFromRaw($status->rawStatus ?: $status->normalized);

                if ($mapped !== $draft->status || $draft->raw_qc_status !== $status->rawStatus) {
                    $draft->update(['status' => $mapped, 'raw_qc_status' => $status->rawStatus]);
                }
            } catch (UnsupportedOperation) {
                return; // sàn không hỗ trợ tra trạng thái — bỏ qua cả shop.
            } catch (Throwable $e) {
                Log::warning('listing.qc_refresh_failed', ['draft' => $draft->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
