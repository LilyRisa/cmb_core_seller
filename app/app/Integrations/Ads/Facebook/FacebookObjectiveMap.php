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
    /** @var array<string, array<string,mixed>> */
    private const MAP = [
        'messages' => [
            'objective' => 'OUTCOME_ENGAGEMENT',
            'optimization_goal' => 'CONVERSATIONS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => 'MESSENGER',
            'needs_promoted_object' => true,
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
            'cta_options' => ['LEARN_MORE'],
        ],
        'traffic' => [
            'objective' => 'OUTCOME_TRAFFIC',
            'optimization_goal' => 'LINK_CLICKS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => null,
            'needs_promoted_object' => false,
            'cta_options' => ['LEARN_MORE', 'SHOP_NOW'],
        ],
    ];

    /** @return array<string,mixed> */
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
