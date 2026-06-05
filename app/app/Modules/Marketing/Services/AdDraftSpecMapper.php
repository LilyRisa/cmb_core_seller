<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Support\AdDraftTree;

/**
 * Translates a wizard draft's payload tree into the connector spec DTOs. Pure.
 * Tree-aware: one campaign → N ad sets → N ads. Legacy flat drafts are normalized
 * to a single ad set + ad by {@see AdDraftTree::normalize}. Defensive reads.
 */
class AdDraftSpecMapper
{
    public function campaign(AdDraft $draft, string $currency): CampaignSpecDTO
    {
        $campaign = (array) (((array) ($draft->payload ?? []))['campaign'] ?? []);
        $cbo = ($campaign['budget_mode'] ?? 'adset') === 'campaign';

        return new CampaignSpecDTO(
            objective: (string) ($draft->objective ?? 'traffic'),
            name: (string) ($draft->name ?? 'Chiến dịch'),
            specialAdCategories: ['NONE'],
            dailyBudgetMajor: $cbo ? (int) ($campaign['daily_budget_major'] ?? 0) : null,
            currency: $cbo ? $currency : null,
        );
    }

    /** @return list<array<string,mixed>> ad set nodes (each with an `ads` list) */
    public function adsetNodes(AdDraft $draft): array
    {
        return AdDraftTree::normalize((array) ($draft->payload ?? []))['adsets'];
    }

    /** @param array<string,mixed> $node an ad set node */
    public function adSet(AdDraft $draft, array $node, string $campaignExternalId, string $currency): AdSetSpecDTO
    {
        $budget = (array) ($node['budget'] ?? []);
        $schedule = (array) ($node['schedule'] ?? []);
        $conversion = (array) ($node['conversion'] ?? []);
        $firstAd = (array) ($node['ads'][0] ?? []);
        $firstCreative = (array) ($firstAd['creative'] ?? []);
        $campaign = (array) (((array) ($draft->payload ?? []))['campaign'] ?? []);
        $cbo = ($campaign['budget_mode'] ?? 'adset') === 'campaign';

        return new AdSetSpecDTO(
            name: (string) ($node['name'] ?? 'Nhóm'),
            campaignExternalId: $campaignExternalId,
            objective: (string) ($draft->objective ?? 'traffic'),
            dailyBudgetMajor: $cbo ? 0 : (int) ($budget['daily_major'] ?? 0),
            currency: $currency,
            targeting: (array) ($node['targeting'] ?? []),
            pageId: isset($firstCreative['page_id']) ? (string) $firstCreative['page_id'] : null,
            startTime: isset($schedule['start_time']) ? (string) $schedule['start_time'] : null,
            placementConfig: isset($node['placement_config']) && is_array($node['placement_config']) ? $node['placement_config'] : null,
            endTime: isset($schedule['end_time']) ? (string) $schedule['end_time'] : null,
            pixelId: isset($conversion['pixel_id']) ? (string) $conversion['pixel_id'] : null,
            conversionEvent: isset($conversion['custom_event_type']) ? (string) $conversion['custom_event_type'] : null,
        );
    }

    /** @param array<string,mixed> $node an ad node */
    public function ad(AdDraft $draft, array $node, string $adSetExternalId): AdSpecDTO
    {
        $c = (array) ($node['creative'] ?? []);

        return new AdSpecDTO(
            name: (string) ($node['name'] ?? 'Quảng cáo'),
            adSetExternalId: $adSetExternalId,
            pageId: (string) ($c['page_id'] ?? ''),
            pagePostId: isset($c['page_post_id']) ? (string) $c['page_post_id'] : null,
            imageHash: isset($c['image_hash']) ? (string) $c['image_hash'] : null,
            videoId: isset($c['video_id']) ? (string) $c['video_id'] : null,
            primaryText: isset($c['primary_text']) ? (string) $c['primary_text'] : null,
            headline: isset($c['headline']) ? (string) $c['headline'] : null,
            linkUrl: isset($c['link_url']) ? (string) $c['link_url'] : null,
            cta: (string) ($c['cta'] ?? 'LEARN_MORE'),
            standardEnhancements: (bool) ($c['standard_enhancements'] ?? false),
        );
    }
}
