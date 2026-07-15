<?php

namespace CMBcoreSeller\Modules\Admin\Notifications\Listeners;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Admin\Notifications\Services\AdminNotificationDispatcher;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Nghe `Verified` (Laravel built-in) ⇒ báo admin qua email khi user xác minh xong (SPEC
 * 2026-07-15). Đăng ký cạnh `SendWelcomeEmailOnVerified` (module Notifications) — không
 * sửa module đó.
 */
class NotifyAdminsOnUserVerified implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(private readonly AdminNotificationDispatcher $dispatcher) {}

    public function handle(Verified $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        /** @var BelongsToMany<Tenant, User> $tenantsRelation */
        $tenantsRelation = $user->tenants();
        $firstTenant = $tenantsRelation->first();
        $tenantName = $firstTenant instanceof Tenant ? $firstTenant->name : '(chưa có shop)';

        $this->dispatcher->notify(NotificationTypeCatalog::AUTH_USER_VERIFIED, [
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'tenant_name' => $tenantName,
        ]);
    }
}
