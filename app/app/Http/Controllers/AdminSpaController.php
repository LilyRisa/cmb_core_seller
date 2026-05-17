<?php

namespace CMBcoreSeller\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Spec 2026-05-17 — phục vụ shell admin SPA tại `/admin/*`.
 *
 * SPA dùng React Router cho client-side routing; mọi sub-path đều trả Blade
 * `admin.blade.php` nạp bundle `resources/js/admin.tsx`. Bundle này tách hoàn
 * toàn khỏi user SPA (`app.tsx`) — không leak code admin xuống browser của
 * tenant user.
 *
 * Invokable controller (không Closure) để `php artisan route:cache` work.
 */
class AdminSpaController extends Controller
{
    public function __invoke(): View
    {
        return view('admin');
    }
}
