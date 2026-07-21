<?php

namespace CMBcoreSeller\Modules\Tenancy\Listeners;

use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use CMBcoreSeller\Modules\Tenancy\Services\FacebookCapiReporter;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Khi tenant mới tạo ⇒ báo sự kiện CompleteRegistration về Meta Conversions API (đo lường
 * hiệu quả quảng cáo Facebook dẫn khách đăng ký — SPEC 2026-07-22).
 *
 * Best-effort: `FacebookCapiReporter` tự no-op nếu chưa cấu hình Pixel/CAPI. Queue `billing`
 * — TÁI DÙNG queue đã wired trong Horizon supervisor (cùng chỗ `StartTrialSubscription` nghe
 * event này) thay vì tự đặt tên queue mới không ai lắng nghe.
 */
class ReportSignupToMetaCapi implements ShouldQueue
{
    public string $queue = 'billing';

    public int $tries = 3;

    public function __construct(protected FacebookCapiReporter $reporter) {}

    public function handle(TenantCreated $event): void
    {
        $tenant = $event->tenant;
        $owner = $tenant->users()->wherePivot('role', Role::Owner->value)->first();
        if ($owner === null || ! $owner->email) {
            return;
        }

        $this->reporter->reportCompleteRegistration($tenant, $owner->email);
    }
}
