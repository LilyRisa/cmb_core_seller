<?php

namespace CMBcoreSeller\Modules\Admin;

use CMBcoreSeller\Modules\Admin\Notifications\Listeners\NotifyAdminsOnNewSupportConversation;
use CMBcoreSeller\Modules\Admin\Notifications\Listeners\NotifyAdminsOnUserVerified;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Admin hệ thống — SPEC 0020. Phụ thuộc: Tenancy (User, Tenant, AuditLog), Billing
 * (Subscription/Plan), Channels (ChannelConnectionService), Support (event lắng nghe).
 *
 * Tất cả routes ở `Http/routes.php` qua middleware `auth:sanctum` + `super_admin` (KHÔNG `tenant`).
 */
class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // SPEC 2026-07-15 — báo admin qua email khi có sự kiện đáng chú ý.
        Event::listen(SupportNewConversationOpened::class, NotifyAdminsOnNewSupportConversation::class);
        Event::listen(Verified::class, NotifyAdminsOnUserVerified::class);
    }
}
