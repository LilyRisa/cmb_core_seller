<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;

/**
 * Maps our internal objective codes → Facebook create-spec fields. Data-driven:
 * add a marketing objective by adding ONE entry here (extensibility unit). Keeps
 * the valid (objective × optimization_goal × billing_event × CTA) combos in one
 * place so the wizard can only build combinations Graph accepts.
 */
final class FacebookObjectiveMap
{
    /** @var array<string, array{objective:string, optimization_goal:string, billing_event:string, destination_type:?string, needs_promoted_object:bool, promoted_object:?string, cta_options:list<string>}> */
    private const MAP = [
        'messages' => [
            'objective' => 'OUTCOME_ENGAGEMENT',
            'optimization_goal' => 'CONVERSATIONS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => 'MESSENGER',
            'needs_promoted_object' => true,
            'promoted_object' => 'page',   // promoted_object = { page_id }
            'cta_options' => ['MESSAGE_PAGE'],
        ],
        'engagement' => [
            // Tên "engagement" khớp đúng objective OUTCOME_ENGAGEMENT + tối ưu POST_ENGAGEMENT
            // (like/comment/share), KHÔNG phải reach/awareness — tránh hiểu nhầm cho người dùng.
            'objective' => 'OUTCOME_ENGAGEMENT',
            'optimization_goal' => 'POST_ENGAGEMENT',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => null,
            'needs_promoted_object' => false,
            'promoted_object' => null,
            'cta_options' => ['LEARN_MORE'],
        ],
        'traffic' => [
            'objective' => 'OUTCOME_TRAFFIC',
            'optimization_goal' => 'LINK_CLICKS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => null,
            'needs_promoted_object' => false,
            'promoted_object' => null,
            'cta_options' => ['LEARN_MORE', 'SHOP_NOW'],
        ],
        'conversions' => [
            // Bán hàng/chuyển đổi trên website — tối ưu theo sự kiện Pixel (offsite conversion).
            'objective' => 'OUTCOME_SALES',
            'optimization_goal' => 'OFFSITE_CONVERSIONS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => null,
            'needs_promoted_object' => true,
            'promoted_object' => 'pixel',  // promoted_object = { pixel_id, custom_event_type }
            'cta_options' => ['SHOP_NOW', 'LEARN_MORE'],
        ],
    ];

    /**
     * @return array{objective:string, optimization_goal:string, billing_event:string, destination_type:?string, needs_promoted_object:bool, promoted_object:?string, cta_options:list<string>}
     */
    public static function spec(string $objective): array
    {
        return self::MAP[$objective] ?? throw UnsupportedOperation::for('facebook', "objective({$objective})");
    }

    /** @return list<string> */
    public static function supported(): array
    {
        return array_keys(self::MAP);
    }
}
