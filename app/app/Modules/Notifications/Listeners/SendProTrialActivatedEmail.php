<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Notifications\Notifications\ProTrialActivatedNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Nghe `Billing\Events\ProTrialActivated` ⇒ gửi mail xác nhận kích hoạt tới chủ shop.
 * Kênh giao tiếp hợp lệ giữa module (event, không đụng Services nội bộ Billing).
 *
 * Queue `notifications`, tries 3.
 */
class SendProTrialActivatedEmail implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(ProTrialActivated $event): void
    {
        $tenant = Tenant::query()->find($event->tenantId);
        if ($tenant === null) {
            return;
        }

        /** @var User|null $owner */
        $owner = $tenant->users()->wherePivot('role', Role::Owner->value)->first();
        if ($owner === null || ! $owner->email) {
            return;
        }

        $owner->notify(new ProTrialActivatedNotification($event->grantedAt, $event->expiresAt));
    }
}
