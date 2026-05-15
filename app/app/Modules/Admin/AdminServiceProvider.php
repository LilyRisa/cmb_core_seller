<?php

namespace CMBcoreSeller\Modules\Admin;

use Illuminate\Support\ServiceProvider;

/**
 * Admin hệ thống — SPEC 0020.
 *
 * Module mỏng: KHÔNG có bảng riêng, không event. Chỉ chứa controllers + service
 * thao tác cross-tenant cho super-admin. Phụ thuộc: Tenancy (User, Tenant, AuditLog),
 * Billing (Subscription/Plan), Channels (ChannelConnectionService — reuse force-delete).
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
        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
