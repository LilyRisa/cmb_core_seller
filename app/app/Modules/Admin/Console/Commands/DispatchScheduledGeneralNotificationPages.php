<?php

namespace CMBcoreSeller\Modules\Admin\Console\Commands;

use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use Illuminate\Console\Command;

/**
 * Plan C (2026-07-23) — quét trang "Chung" đã lên lịch (scheduled_at đã tới) và đưa vào hàng
 * đợi gửi. Chạy mỗi phút (app/routes/console.php). Idempotent — job tự guard `status !== sent`.
 */
class DispatchScheduledGeneralNotificationPages extends Command
{
    protected $signature = 'notifications:dispatch-scheduled-general-pages';

    protected $description = 'Gửi các trang thông báo chung đã lên lịch mà thời điểm gửi đã tới (Plan C, 2026-07-23)';

    public function handle(): int
    {
        $due = GeneralNotificationPage::query()
            ->where('status', GeneralNotificationPage::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $page) {
            DispatchGeneralNotificationPageJob::dispatch((int) $page->getKey());
        }

        $this->info("Đã đưa {$due->count()} trang vào hàng đợi gửi.");

        return self::SUCCESS;
    }
}
