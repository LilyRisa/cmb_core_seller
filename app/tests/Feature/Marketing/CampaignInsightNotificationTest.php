<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\CampaignAiInsight;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingCampaignInsightReadyNotification;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignInsightNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_uses_string_view_and_renders(): void
    {
        $tenant = Tenant::create(['name' => 'Shop X']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'name' => 'TK1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);
        $insight = CampaignAiInsight::create([
            'ad_account_id' => $account->id,
            'campaign_external_id' => 'C1',
            'payload' => [
                'score' => 72,
                'summary' => 'Chiến dịch hiệu quả tốt.',
                'assessment' => 'CTR ổn định.',
                'recommendations' => [['action' => 'Tăng ngân sách', 'rationale' => 'CTR cao']],
                'creative_review' => [['ref' => 'AD1', 'name' => 'QC', 'verdict' => 'tốt', 'issues' => [], 'suggestions' => ['Giữ nguyên']]],
            ],
            'params' => ['days' => 14, 'metrics' => ['spend'], 'include_engagement' => true],
            'provider_code' => 'cmb', 'generated_at' => now(),
        ]);

        $mail = (new MarketingCampaignInsightReadyNotification($account, $insight, 'Chiến dịch Tết'))->toMail(new \stdClass);

        $this->assertStringContainsString('Phân tích AI', $mail->subject);
        // Root cause of prod email failure: a single-element array view ['x'] makes
        // Mailer::parseView() read $view[1] → "Undefined array key 1". Must be a string.
        $this->assertIsString($mail->view);
        $this->assertSame('notifications::marketing-campaign-insight-ready', $mail->view);

        $html = view($mail->view, $mail->viewData)->render();
        $this->assertStringContainsString('Chiến dịch Tết', $html);
        $this->assertStringContainsString('Khuyến nghị', $html);
    }
}
