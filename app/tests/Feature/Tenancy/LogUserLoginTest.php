<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_guard_login_creates_login_event(): void
    {
        $user = User::factory()->create();

        Auth::guard('web')->login($user);

        $this->assertSame(1, UserLoginEvent::query()->where('user_id', $user->getKey())->count());
    }

    /**
     * `sanctum` là stateless RequestGuard (không có login()) nên không dùng được ở đây —
     * đổi sang guard `admin_web` (cũng đã đăng ký trong config/auth.php) chỉ để chứng minh
     * listener LỌC đúng theo `$event->guard`, không quan trọng guard cụ thể nào miễn khác `web`.
     */
    public function test_other_guard_login_does_not_create_login_event(): void
    {
        $user = User::factory()->create();

        Auth::guard('admin_web')->login($user);

        $this->assertSame(0, UserLoginEvent::query()->where('user_id', $user->getKey())->count());
    }
}
