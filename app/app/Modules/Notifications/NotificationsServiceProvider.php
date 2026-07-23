<?php

namespace CMBcoreSeller\Modules\Notifications;

use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Marketing\Events\AdMonitorActionTaken;
use CMBcoreSeller\Modules\Marketing\Events\AdMonitorThresholdApproaching;
use CMBcoreSeller\Modules\Notifications\Console\Commands\BackfillNotificationCategory;
use CMBcoreSeller\Modules\Notifications\Http\Middleware\EnsureEmailVerified;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAdMonitorAction;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAdMonitorApproaching;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnChannelReconnect;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnNegativeOrder;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnOrderCancelled;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnReturnNew;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnStockPushFailed;
use CMBcoreSeller\Modules\Notifications\Listeners\SendProTrialActivatedEmail;
use CMBcoreSeller\Modules\Notifications\Listeners\SendWelcomeEmailOnVerified;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Events\ReturnStatusChanged;
use Illuminate\Auth\Events\Verified;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Notifications domain module (SPEC 0022 — Phase 6.5).
 *
 * Module nền cho notifications (email/in-app/Zalo/Telegram). Lần đầu chỉ có kênh
 * `mail` với 3 notification cốt lõi (verify / welcome / reset password). Các
 * channel khác sẽ được thêm trong Phase 6.5 tiếp theo.
 *
 * Xem `docs/01-architecture/modules.md` — module này nói chuyện với module khác
 * qua event và contract, không gọi vào ruột module khác.
 */
class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/notifications.php', 'notifications');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../../../resources/views/mail', 'notifications');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // Đăng ký middleware alias `verified` JSON-envelope (override Laravel default
        // EnsureEmailIsVerified — mặc định redirect HTML, ta cần 403 JSON).
        $this->app->make(Router::class)->aliasMiddleware('verified', EnsureEmailVerified::class);

        // SPEC 0022 §3.1 — sau khi user verify email ⇒ gửi welcome.
        Event::listen(Verified::class, SendWelcomeEmailOnVerified::class);
        Event::listen(ProTrialActivated::class, SendProTrialActivatedEmail::class);

        // SPEC 0036 — thông báo in-app: nghe domain event của các module khác (kênh giao
        // tiếp hợp lệ; KHÔNG gọi Services nội bộ của chúng). Listener queued (queue
        // `notifications`, có trong supervisor-default).
        Event::listen(ChannelAccountNeedsReconnect::class, NotifyOnChannelReconnect::class);
        Event::listen(OrderUpserted::class, NotifyOnNegativeOrder::class);
        Event::listen(OrderStatusChanged::class, NotifyOnOrderCancelled::class);
        Event::listen(ReturnStatusChanged::class, NotifyOnReturnNew::class);
        Event::listen(AdMonitorThresholdApproaching::class, NotifyOnAdMonitorApproaching::class);
        Event::listen(AdMonitorActionTaken::class, NotifyOnAdMonitorAction::class);
        Event::listen(StockPushed::class, NotifyOnStockPushFailed::class);

        if ($this->app->runningInConsole()) {
            $this->commands([BackfillNotificationCategory::class]);
        }
    }
}
