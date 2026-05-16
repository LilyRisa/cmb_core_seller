<?php

namespace CMBcoreSeller\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
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
     * Cho phép super-admin hệ thống (SPEC 0020) xem dashboard ở mọi env. Local
     * env Laravel mặc định bỏ qua gate ⇒ dev luôn vào được. Production: ai có
     * `users.is_super_admin = true` vào được `/horizon`.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return $user !== null && (bool) ($user->is_super_admin ?? false);
        });
    }
}
