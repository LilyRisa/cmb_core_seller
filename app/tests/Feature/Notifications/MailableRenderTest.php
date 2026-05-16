<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Notifications\ResetPasswordNotification;
use CMBcoreSeller\Modules\Notifications\Notifications\VerifyEmailNotification;
use CMBcoreSeller\Modules\Notifications\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0022 — đảm bảo 3 email template render được + chứa brand + CTA.
 */
class MailableRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_renders_brand_and_cta(): void
    {
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $mail = (new VerifyEmailNotification)->toMail($user);
        $html = (string) view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('CMBcoreSeller', $html);
        $this->assertStringContainsString('Xác thực email', $html);
        $this->assertStringContainsString('test@example.com', $html);
        $this->assertStringContainsString('/api/v1/auth/email/verify/', $html);
        $this->assertStringContainsString('Test User', $html);
    }

    public function test_welcome_renders_3_step_checklist(): void
    {
        $user = User::factory()->create(['name' => 'Owner X']);
        $mail = (new WelcomeNotification)->toMail($user);
        $html = (string) view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Chào mừng', $html);
        $this->assertStringContainsString('Kết nối gian hàng', $html);
        $this->assertStringContainsString('Khai báo SKU', $html);
        $this->assertStringContainsString('Mời nhân viên', $html);
        $this->assertStringContainsString('Owner X', $html);
    }

    public function test_reset_password_renders_reset_url_to_frontend(): void
    {
        $user = User::factory()->create(['email' => 'pw@example.com']);
        $mail = (new ResetPasswordNotification('abc-token-123'))->toMail($user);
        $html = (string) view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Đặt lại mật khẩu', $html);
        $this->assertStringContainsString('abc-token-123', $html);
        $this->assertStringContainsString('pw%40example.com', $html);   // urlencoded
    }
}
