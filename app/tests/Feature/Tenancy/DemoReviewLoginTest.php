<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * TẠM THỜI — cổng đăng nhập cho TikTok app review.
 *
 * Tài khoản demo `owner@demo.local` đăng nhập được bằng (1) mật khẩu đã lưu, HOẶC
 * (2) literal `password` (credential đã gửi trong bản phê duyệt, không sửa được).
 * Luôn bật cho đúng tài khoản demo — KHÔNG phụ thuộc cờ env. Chặn cực hẹp: CHỈ đúng
 * email demo + CHỈ đúng literal cấu hình + chỉ khi user tồn tại.
 *
 * ⚠️ XOÁ file test này + {@see AuthController::demoReviewBypass} sau khi app được duyệt.
 */
class DemoReviewLoginTest extends TestCase
{
    use RefreshDatabase;

    private function configureDemo(): void
    {
        config([
            'auth.demo_review.email' => 'owner@demo.local',
            'auth.demo_review.password' => 'password',
        ]);
    }

    public function test_demo_account_logs_in_with_literal_password_even_if_stored_differs(): void
    {
        $this->configureDemo();
        User::factory()->create(['email' => 'owner@demo.local', 'password' => Hash::make('some-other-real-pass')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'owner@demo.local', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.email', 'owner@demo.local');
    }

    public function test_literal_password_works_without_any_enable_flag(): void
    {
        // Luôn bật: dù không có cờ env nào, literal "password" vẫn vào được tài khoản demo.
        config(['auth.demo_review.enabled' => false]);
        $this->configureDemo();
        User::factory()->create(['email' => 'owner@demo.local', 'password' => Hash::make('different-stored')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'owner@demo.local', 'password' => 'password'])
            ->assertOk();
    }

    public function test_demo_account_still_logs_in_with_real_stored_password(): void
    {
        $this->configureDemo();
        User::factory()->create(['email' => 'owner@demo.local', 'password' => Hash::make('some-other-real-pass')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'owner@demo.local', 'password' => 'some-other-real-pass'])
            ->assertOk();
    }

    public function test_other_account_cannot_use_literal_password(): void
    {
        // CHỐT CHẶN LẠM DỤNG: literal password CHỈ áp cho đúng email demo.
        $this->configureDemo();
        User::factory()->create(['email' => 'real@user.com', 'password' => Hash::make('real-secret')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'real@user.com', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_demo_account_rejects_arbitrary_wrong_password(): void
    {
        $this->configureDemo();
        User::factory()->create(['email' => 'owner@demo.local', 'password' => Hash::make('some-other-real-pass')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'owner@demo.local', 'password' => 'not-the-literal'])
            ->assertStatus(422);
    }
}
