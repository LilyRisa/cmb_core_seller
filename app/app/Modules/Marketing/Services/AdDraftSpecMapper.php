<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;

/**
 * Translates a wizard draft's payload into the connector spec DTOs. Pure (no I/O).
 * Defensive reads — drafts may be partial; Graph rejects truly invalid specs at
 * publish (surfaced as the draft's last_error).
 */
class AdDraftSpecMapper
{
    public function campaign(AdDraft $draft): CampaignSpecDTO
    {
        return new CampaignSpecDTO(
            objective: (string) ($draft->objective ?? 'traffic'),
            name: (string) ($draft->name ?? 'Chiến dịch'),
            specialAdCategories: ['NONE'],
        );
    }

    public function adSet(AdDraft $draft, string $currency): AdSetSpecDTO
    {
        $p = (array) ($draft->payload ?? []);
        $creative = (array) ($p['creative'] ?? []);
        $budget = (array) ($p['budget'] ?? []);
        $schedule = (array) ($p['schedule'] ?? []);

        return new AdSetSpecDTO(
            name: (string) ($draft->name ?? 'Chiến dịch').' — nhóm',
            campaignExternalId: (string) $draft->campaign_external_id,
            objective: (string) ($draft->objective ?? 'traffic'),
            dailyBudgetMajor: (int) ($budget['daily_major'] ?? 0),
            currency: $currency,
            targeting: (array) ($p['targeting'] ?? []),
            pageId: isset($creative['page_id']) ? (string) $creative['page_id'] : null,
            startTime: isset($schedule['start_time']) ? (string) $schedule['start_time'] : null,
        );
    }

    public function ad(AdDraft $draft): AdSpecDTO
    {
        $c = (array) (((array) ($draft->payload ?? []))['creative'] ?? []);

        return new AdSpecDTO(
            name: (string) ($draft->name ?? 'Chiến dịch').' — quảng cáo',
            adSetExternalId: (string) $draft->adset_external_id,
            pageId: (string) ($c['page_id'] ?? ''),
            pagePostId: isset($c['page_post_id']) ? (string) $c['page_post_id'] : null,
            imageHash: isset($c['image_hash']) ? (string) $c['image_hash'] : null,
            videoId: isset($c['video_id']) ? (string) $c['video_id'] : null,
            primaryText: isset($c['primary_text']) ? (string) $c['primary_text'] : null,
            headline: isset($c['headline']) ? (string) $c['headline'] : null,
            linkUrl: isset($c['link_url']) ? (string) $c['link_url'] : null,
            cta: (string) ($c['cta'] ?? 'LEARN_MORE'),
        );
    }
}
