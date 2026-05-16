<?php

namespace CMBcoreSeller\Modules\Notifications;

use CMBcoreSeller\Modules\Notifications\Http\Middleware\EnsureEmailVerified;
use CMBcoreSeller\Modules\Notifications\Listeners\SendWelcomeEmailOnVerified;
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
        $this->loadViewsFrom(__DIR__.'/../../../resources/views/mail', 'notifications');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // Đăng ký middleware alias `verified` JSON-envelope (override Laravel default
        // EnsureEmailIsVerified — mặc định redirect HTML, ta cần 403 JSON).
        $this->app->make(Router::class)->aliasMiddleware('verified', EnsureEmailVerified::class);

        // SPEC 0022 §3.1 — sau khi user verify email ⇒ gửi welcome.
        Event::listen(Verified::class, SendWelcomeEmailOnVerified::class);
    }
}
