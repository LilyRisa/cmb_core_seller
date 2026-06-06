<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookResultMap as R;
use PHPUnit\Framework\TestCase;

/**
 * "Kết quả" phải khớp Ads Manager: đếm đúng sự kiện tối ưu của nhóm quảng cáo.
 * Hai ca trọng tâm người dùng nêu: chiến dịch chuyển đổi tối ưu "Hoàn tất đăng ký" ⇒
 * đếm hoàn tất đăng ký trên web; chiến dịch tin nhắn ⇒ đếm hội thoại tin nhắn.
 */
class FacebookResultMapTest extends TestCase
{
    public function test_conversion_complete_registration_counts_web_registration(): void
    {
        // OUTCOME_SALES + OFFSITE_CONVERSIONS + custom_event_type COMPLETE_REGISTRATION
        $code = R::resolveCode('OUTCOME_SALES', 'OFFSITE_CONVERSIONS', 'COMPLETE_REGISTRATION');
        $this->assertSame('complete_registration', $code);
        $this->assertSame('Hoàn tất đăng ký', R::label($code));

        // Pixel bắn cả purchase (nhiễu) lẫn complete_registration — KHÔNG được lấy purchase.
        $actions = [
            'offsite_conversion.fb_pixel_purchase' => 99,
            'offsite_conversion.fb_pixel_complete_registration' => 12,
            'lead' => 40,
        ];
        $this->assertSame(12, R::count($actions, $code));
    }

    public function test_messaging_campaign_counts_conversations(): void
    {
        // Chiến dịch mess: objective OUTCOME_ENGAGEMENT + optimization_goal CONVERSATIONS.
        $code = R::resolveCode('OUTCOME_ENGAGEMENT', 'CONVERSATIONS', null);
        $this->assertSame('messaging', $code);
        $this->assertSame('Tin nhắn', R::label($code));

        $actions = ['onsite_conversion.messaging_conversation_started_7d' => 8, 'link_click' => 200];
        $this->assertSame(8, R::count($actions, $code));
    }

    public function test_conversion_purchase_counts_purchase(): void
    {
        $code = R::resolveCode('OUTCOME_SALES', 'OFFSITE_CONVERSIONS', 'PURCHASE');
        $this->assertSame('purchase', $code);
        // omni_purchase là tổng gộp — lấy max, không cộng trùng với offsite.
        $this->assertSame(20, R::count(['omni_purchase' => 20, 'offsite_conversion.fb_pixel_purchase' => 20], $code));
    }

    public function test_lead_objective_counts_lead(): void
    {
        $code = R::resolveCode('OUTCOME_LEADS', 'LEAD_GENERATION', null);
        $this->assertSame('lead', $code);
        $this->assertSame(5, R::count(['lead' => 5], $code));
    }

    public function test_traffic_counts_link_clicks(): void
    {
        $this->assertSame('link_click', R::resolveCode('OUTCOME_TRAFFIC', 'LINK_CLICKS', null));
    }

    public function test_engagement_without_goal_falls_back_to_generic(): void
    {
        // OUTCOME_ENGAGEMENT mà thiếu optimization_goal ⇒ không phân biệt được tin nhắn/tương tác.
        $code = R::resolveCode('OUTCOME_ENGAGEMENT', null, null);
        $this->assertSame('result', $code);
    }

    public function test_generic_picks_deepest_conversion_and_types_it(): void
    {
        // Không rõ sự kiện: ưu tiên purchase → lead → đăng ký…
        $this->assertSame(['lead', 7], R::genericResultTyped(['lead' => 7, 'landing_page_view' => 300]));
        $this->assertSame(['complete_registration', 3], R::genericResultTyped(['complete_registration' => 3]));
        $this->assertSame(['result', 0], R::genericResultTyped(['impressions' => 1000]));
    }
}
