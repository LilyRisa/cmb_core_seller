<?php

namespace CMBcoreSeller\Providers;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * Spec 2026-05-17 — chỉ super-admin (bảng `admin_users`, active) xem được
     * dashboard `/horizon` ở mọi env. Local env Laravel mặc định bỏ qua gate ⇒
     * dev luôn vào được. Production: phải đăng nhập `/admin/login` trước (cookie
     * `admin_web` session) rồi mới mở được `/horizon`.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            $admin = Auth::guard('admin_web')->user();

            return $admin instanceof AdminUser && (bool) $admin->is_active;
        });
    }
}
