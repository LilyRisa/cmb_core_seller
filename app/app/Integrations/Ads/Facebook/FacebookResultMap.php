<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

/**
 * Quyết định "Kết quả" (Ads Manager → cột Results) cho 1 dòng quảng cáo Facebook.
 *
 * Facebook KHÔNG hiển thị một con số cố định — "Kết quả" = số lần xảy ra **sự kiện tối ưu**
 * của nhóm quảng cáo. Sự kiện đó suy ra từ:
 *   - `optimization_goal` (CONVERSATIONS = tin nhắn, LEAD_GENERATION = khách tiềm năng,
 *     LINK_CLICKS = traffic, POST_ENGAGEMENT = tương tác, …), VÀ
 *   - với chuyển đổi (OFFSITE_CONVERSIONS) là `promoted_object.custom_event_type`
 *     (COMPLETE_REGISTRATION → hoàn tất đăng ký trên web, PURCHASE → mua hàng, …).
 *
 * Tài liệu: Marketing API "Ads Action Stats" (action_type) + Ad Set `optimization_goal` /
 * `promoted_object.custom_event_type`. Mỗi sự kiện ↔ danh sách `action_type` (gồm biến thể
 * offsite_conversion.fb_pixel_* / omni_* / onsite_*) — đếm bằng MAX để không cộng trùng
 * (omni_* là tổng đã gộp, offsite_* là phần web).
 *
 * Đây là NGUỒN CHÂN LÝ DUY NHẤT — connector, AdsReportService, AdMonitorEvaluator và FE
 * đều dùng chung qua đây (trước kia mỗi nơi tự đoán → lệch nhau).
 */
final class FacebookResultMap
{
    /** @var array<string, array{label:string, action_types:list<string>}> */
    private const EVENTS = [
        'messaging' => [
            'label' => 'Tin nhắn',
            'action_types' => [
                'onsite_conversion.messaging_conversation_started_7d',
                'onsite_conversion.total_messaging_connection',
                'onsite_conversion.messaging_first_reply',
            ],
        ],
        'complete_registration' => [
            'label' => 'Hoàn tất đăng ký',
            'action_types' => [
                'offsite_conversion.fb_pixel_complete_registration',
                'omni_complete_registration',
                'complete_registration',
                'onsite_conversion.flow_complete',
            ],
        ],
        'purchase' => [
            'label' => 'Mua hàng',
            'action_types' => [
                'omni_purchase',
                'offsite_conversion.fb_pixel_purchase',
                'onsite_web_purchase',
                'onsite_web_app_purchase',
                'web_in_store_purchase',
                'onsite_conversion.purchase',
                'purchase',
            ],
        ],
        'lead' => [
            'label' => 'Khách tiềm năng',
            'action_types' => [
                'lead',
                'offsite_conversion.fb_pixel_lead',
                'leadgen.other',
                'onsite_conversion.lead_grouped',
            ],
        ],
        'add_to_cart' => [
            'label' => 'Thêm vào giỏ',
            'action_types' => [
                'omni_add_to_cart',
                'offsite_conversion.fb_pixel_add_to_cart',
                'add_to_cart',
            ],
        ],
        'initiate_checkout' => [
            'label' => 'Bắt đầu thanh toán',
            'action_types' => [
                'omni_initiated_checkout',
                'offsite_conversion.fb_pixel_initiate_checkout',
                'initiate_checkout',
            ],
        ],
        'view_content' => [
            'label' => 'Xem nội dung',
            'action_types' => [
                'omni_view_content',
                'offsite_conversion.fb_pixel_view_content',
                'view_content',
            ],
        ],
        'search' => [
            'label' => 'Tìm kiếm',
            'action_types' => [
                'omni_search',
                'offsite_conversion.fb_pixel_search',
                'search',
            ],
        ],
        'add_to_wishlist' => [
            'label' => 'Thêm vào yêu thích',
            'action_types' => [
                'omni_add_to_wishlist',
                'offsite_conversion.fb_pixel_add_to_wishlist',
                'add_to_wishlist',
            ],
        ],
        'link_click' => [
            'label' => 'Lượt nhấp liên kết',
            'action_types' => ['link_click'],
        ],
        'landing_page_view' => [
            'label' => 'Xem trang đích',
            'action_types' => ['landing_page_view', 'omni_view_content'],
        ],
        'post_engagement' => [
            'label' => 'Tương tác',
            'action_types' => ['post_engagement'],
        ],
        // Generic fallback — chưa biết sự kiện tối ưu ⇒ đếm theo "chuyển đổi sâu nhất"
        // (xem genericResult). action_types để rỗng làm dấu hiệu dùng nhánh generic.
        'result' => [
            'label' => 'Kết quả',
            'action_types' => [],
        ],
    ];

    /** custom_event_type (promoted_object) → mã sự kiện nội bộ. */
    private const CUSTOM_EVENT = [
        'COMPLETE_REGISTRATION' => 'complete_registration',
        'PURCHASE' => 'purchase',
        'LEAD' => 'lead',
        'ADD_TO_CART' => 'add_to_cart',
        'INITIATE_CHECKOUT' => 'initiate_checkout',
        'VIEW_CONTENT' => 'view_content',
        'SEARCH' => 'search',
        'ADD_TO_WISHLIST' => 'add_to_wishlist',
    ];

    /**
     * Suy ra mã sự kiện "Kết quả" từ ngữ cảnh tối ưu. Ưu tiên tín hiệu cụ thể nhất:
     * optimization_goal CONVERSATIONS → tin nhắn; custom_event_type (chuyển đổi) → đúng sự kiện;
     * rồi tới optimization_goal; cuối cùng tới objective (thô); không rõ → 'result'.
     */
    public static function resolveCode(?string $objective, ?string $optimizationGoal, ?string $customEventType): string
    {
        $goal = strtoupper((string) $optimizationGoal);
        $event = strtoupper((string) $customEventType);
        $obj = strtoupper((string) $objective);

        if ($goal === 'CONVERSATIONS') {
            return 'messaging';
        }
        if ($event !== '' && isset(self::CUSTOM_EVENT[$event])) {
            return self::CUSTOM_EVENT[$event];
        }
        $byGoal = [
            'LEAD_GENERATION' => 'lead',
            'QUALITY_LEAD' => 'lead',
            'LINK_CLICKS' => 'link_click',
            'LANDING_PAGE_VIEWS' => 'landing_page_view',
            'POST_ENGAGEMENT' => 'post_engagement',
            'PAGE_LIKES' => 'post_engagement',
            'THRUPLAY' => 'post_engagement',
        ][$goal] ?? null;
        if ($byGoal !== null) {
            return $byGoal;
        }
        // objective thô (khi thiếu optimization_goal/custom_event_type, vd dòng campaign chưa re-sync).
        $byObjective = [
            'OUTCOME_LEADS' => 'lead',
            'LEAD_GENERATION' => 'lead',
            'OUTCOME_TRAFFIC' => 'link_click',
            'LINK_CLICKS' => 'link_click',
            'MESSAGES' => 'messaging',   // tên objective legacy (ODAX cũ)
        ][$obj] ?? null;
        if ($byObjective !== null) {
            return $byObjective;
        }

        // OUTCOME_SALES/CONVERSIONS không có custom_event_type rõ ⇒ generic (purchase-leaning hierarchy),
        // OUTCOME_ENGAGEMENT không có optimization_goal ⇒ không phân biệt được tin nhắn/tương tác ⇒ generic.
        return 'result';
    }

    /** Nhãn tiếng Việt cho cột "Kết quả" theo mã sự kiện. */
    public static function label(string $code): string
    {
        return self::EVENTS[$code]['label'] ?? self::EVENTS['result']['label'];
    }

    /**
     * Đếm "Kết quả" cho 1 dòng: lấy MAX qua các action_type biến thể của sự kiện. Với mã
     * 'result' (chưa biết sự kiện) → dùng hierarchy "chuyển đổi sâu nhất".
     *
     * @param  array<string,int>  $actions  action_type ⇒ value (đã index)
     */
    public static function count(array $actions, string $code): int
    {
        if ($code === 'result' || ! isset(self::EVENTS[$code])) {
            return self::genericResult($actions);
        }
        $max = 0;
        foreach (self::EVENTS[$code]['action_types'] as $type) {
            $max = max($max, (int) ($actions[$type] ?? 0));
        }

        return $max;
    }

    /**
     * "Chuyển đổi sâu nhất" khi chưa biết sự kiện tối ưu: purchase → lead → đăng ký →
     * thêm giỏ → thanh toán → tin nhắn → xem trang đích. Trả [code, value] để gắn nhãn đúng.
     *
     * @param  array<string,int>  $actions
     * @return array{0:string,1:int}
     */
    public static function genericResultTyped(array $actions): array
    {
        foreach (['purchase', 'lead', 'complete_registration', 'add_to_cart', 'initiate_checkout', 'messaging', 'landing_page_view'] as $code) {
            $v = self::count($actions, $code);
            if ($v > 0) {
                return [$code, $v];
            }
        }

        return ['result', 0];
    }

    /** @param array<string,int> $actions */
    private static function genericResult(array $actions): int
    {
        return self::genericResultTyped($actions)[1];
    }
}
