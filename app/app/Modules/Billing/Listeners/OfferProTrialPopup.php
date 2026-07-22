<?php

namespace CMBcoreSeller\Modules\Billing\Listeners;

use CMBcoreSeller\Modules\Billing\Services\ProTrialService;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Khi tenant mới tạo ⇒ đánh dấu thuộc diện được mời popup trải nghiệm Pro (không phụ thuộc
 * `ProTrialSettings::enabled()` tại thời điểm này — việc có thực sự hiện popup hay không được
 * quyết định LIVE ở `ProTrialService::eligibility()` mỗi lần tenant tải trang).
 *
 * Idempotent (`ProTrialService::offer()` no-op nếu đã có row) — an toàn nếu event dispatch lại do retry.
 * Queue `billing` — cùng chỗ `StartTrialSubscription`/`ReportSignupToMetaCapi` đang nghe event này.
 */
class OfferProTrialPopup implements ShouldQueue
{
    public string $queue = 'billing';

    public int $tries = 3;

    public function __construct(protected ProTrialService $service) {}

    public function handle(TenantCreated $event): void
    {
        $this->service->offer((int) $event->tenant->getKey());
    }
}
