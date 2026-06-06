<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Services\AdsReportService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cột "Kết quả" của báo cáo phải đếm đúng sự kiện tối ưu (như Ads Manager):
 *  - Chiến dịch chuyển đổi tối ưu "Hoàn tất đăng ký" ⇒ đếm đăng ký trên web, KHÔNG phải mua hàng.
 *  - Chiến dịch tin nhắn ⇒ đếm hội thoại tin nhắn.
 */
class AdsReportResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    private function account(): AdAccount
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'status' => 'active', 'access_token' => 'T']);
    }

    /** @param list<array{action_type:string,value:string|int}> $actions */
    private function fakeInsights(string $idField, string $extId, array $actions): void
    {
        Http::fake(['graph.facebook.com/*/insights*' => Http::response(['data' => [[
            'date_start' => now()->toDateString(), 'date_stop' => now()->toDateString(),
            $idField => $extId, 'spend' => '50000', 'impressions' => '1000', 'clicks' => '30', 'reach' => '800',
            'actions' => $actions,
        ]]], 200)]);
    }

    public function test_conversion_registration_campaign_counts_web_registration_not_purchase(): void
    {
        $account = $this->account();
        // Campaign chuyển đổi + adset con tối ưu COMPLETE_REGISTRATION (custom_event_type lưu ở meta).
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Reg', 'objective' => 'OUTCOME_SALES']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'adset', 'external_id' => 'A1', 'parent_external_id' => 'C1', 'name' => 'AS', 'meta' => ['optimization_goal' => 'OFFSITE_CONVERSIONS', 'custom_event_type' => 'COMPLETE_REGISTRATION']]);

        // Pixel bắn cả purchase (nhiễu) lẫn complete_registration — Kết quả phải là đăng ký.
        $this->fakeInsights('campaign_id', 'C1', [
            ['action_type' => 'offsite_conversion.fb_pixel_purchase', 'value' => '99'],
            ['action_type' => 'offsite_conversion.fb_pixel_complete_registration', 'value' => '12'],
        ]);

        $rows = app(AdsReportService::class)->report($account, 'campaign', now()->toDateString(), now()->toDateString());

        $this->assertSame('complete_registration', $rows[0]['insights']['result_type']);
        $this->assertSame('Hoàn tất đăng ký', $rows[0]['insights']['result_label']);
        $this->assertSame(12, $rows[0]['insights']['results']);
        $this->assertArrayNotHasKey('actions', $rows[0]['insights']); // không lộ actions thô ra FE
    }

    public function test_messaging_campaign_counts_conversations(): void
    {
        $account = $this->account();
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C2', 'name' => 'Mess', 'objective' => 'OUTCOME_ENGAGEMENT']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'adset', 'external_id' => 'A2', 'parent_external_id' => 'C2', 'name' => 'AS', 'meta' => ['optimization_goal' => 'CONVERSATIONS']]);

        $this->fakeInsights('campaign_id', 'C2', [
            ['action_type' => 'onsite_conversion.messaging_conversation_started_7d', 'value' => '8'],
            ['action_type' => 'link_click', 'value' => '200'],
        ]);

        $rows = app(AdsReportService::class)->report($account, 'campaign', now()->toDateString(), now()->toDateString());

        $this->assertSame('messaging', $rows[0]['insights']['result_type']);
        $this->assertSame('Tin nhắn', $rows[0]['insights']['result_label']);
        $this->assertSame(8, $rows[0]['insights']['results']);
    }
}
