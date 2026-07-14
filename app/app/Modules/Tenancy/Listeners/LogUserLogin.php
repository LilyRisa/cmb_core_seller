<?php

namespace CMBcoreSeller\Modules\Tenancy\Listeners;

use CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

/**
 * Ghi lịch sử đăng nhập CHỈ guard `web` (nhân viên tenant) — admin_web có audit log riêng, không lẫn.
 * Design 2026-07-15.
 */
class LogUserLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        UserLoginEvent::query()->create([
            'user_id' => $event->user->getAuthIdentifier(),
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::header('User-Agent'),
            'logged_in_at' => now(),
        ]);
    }
}
