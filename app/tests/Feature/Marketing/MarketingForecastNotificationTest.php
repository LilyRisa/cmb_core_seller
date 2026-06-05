<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingForecastReadyNotification;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingForecastNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_has_subject_and_view_data(): void
    {
        $tenant = Tenant::create(['name' => 'Shop X']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'name' => 'TK1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);
        $forecast = AdForecast::create([
            'ad_account_id' => $account->id, 'payload' => [
                'forecast' => ['next_7d' => ['orders' => 5, 'spend' => 700000]],
                'strategy' => [['action' => 'maintain_budget', 'rationale' => 'ổn định']],
                'creative_review' => [['ref' => 'AD1', 'name' => 'QC', 'verdict' => 'cần cải thiện', 'issues' => [], 'suggestions' => ['Thêm CTA']]],
            ],
            'provider_code' => 'cmb', 'model' => 'x', 'generated_at' => now(),
        ]);

        $mail = (new MarketingForecastReadyNotification($account, $forecast))->toMail(new \stdClass);

        $this->assertStringContainsString('Báo cáo quảng cáo', $mail->subject);
        // Must be a STRING view — a single-element array view ['x'] makes Laravel's
        // Mailer::parseView() read $view[1] → "Undefined array key 1" and the mail never sends.
        $this->assertIsString($mail->view);
        $this->assertSame('notifications::marketing-forecast-ready', $mail->view);
        $this->assertSame($account->id, $mail->viewData['account']->id);
        $this->assertArrayHasKey('payload', $mail->viewData);
        // Render to prove the template compiles with this data (end-to-end guard).
        $html = view($mail->view, $mail->viewData)->render();
        $this->assertStringContainsString('Dự báo 7 ngày', $html);
    }
}
