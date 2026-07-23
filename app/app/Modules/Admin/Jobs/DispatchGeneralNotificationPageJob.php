<?php

namespace CMBcoreSeller\Modules\Admin\Jobs;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Plan C (2026-07-23) — chạy fan-out ngoài request HTTP (audience có thể hàng nghìn tenant).
 * Dùng cho cả "Gửi ngay" (controller dispatch job) lẫn lịch gửi (scheduled command dispatch job
 * cho từng page đến hạn). Guard `status !== sent` tránh gửi trùng nếu job chạy lại (retry/race).
 */
class DispatchGeneralNotificationPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public readonly int $pageId)
    {
        $this->queue = 'notifications';
    }

    public function handle(GeneralNotificationPageService $service): void
    {
        $page = GeneralNotificationPage::query()->find($this->pageId);
        if ($page === null || $page->status === GeneralNotificationPage::STATUS_SENT) {
            return;
        }
        $service->dispatch($page);
    }
}
