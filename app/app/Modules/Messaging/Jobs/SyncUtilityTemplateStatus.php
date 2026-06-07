<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use CMBcoreSeller\Modules\Messaging\Services\UtilityTemplateService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Đồng bộ trạng thái duyệt của các utility template đang `pending` (SPEC-0032).
 * Webhook duyệt của Meta không đảm bảo ⇒ poll định kỳ (scheduler ~15 phút).
 * Idempotent; lỗi 1 template không chặn các template khác.
 *
 * Queue: `messaging`. Chạy KHÔNG có CurrentTenant ⇒ bỏ TenantScope khi quét.
 */
class SyncUtilityTemplateStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('messaging');
    }

    public function handle(UtilityTemplateService $service): void
    {
        UtilityTemplate::withoutGlobalScope(TenantScope::class)
            ->where('status', UtilityTemplate::STATUS_PENDING)
            ->whereNotNull('external_template_id')
            ->chunkById(100, function ($templates) use ($service) {
                foreach ($templates as $template) {
                    try {
                        $service->syncStatus($template);
                    } catch (Throwable $e) {
                        Log::warning('utility_template.sync.failed', [
                            'utility_template_id' => $template->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
