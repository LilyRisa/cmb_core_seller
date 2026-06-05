<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Cache;

/**
 * Ads-Manager-style report: entity metadata from DB + insights fetched ON-DEMAND
 * for a date range (cached 5'). Drill-down filters at the server. SPEC 2026-06-04.
 */
class AdsReportService
{
    public function __construct(private AdsRegistry $registry) {}

    /**
     * @param  array{campaign_ids?:list<string>, adset_ids?:list<string>, q?:string, objective?:string, id?:string}  $filters
     * @return list<array<string,mixed>>
     */
    public function report(AdAccount $account, string $level, string $since, string $until, array $filters = []): array
    {
        $entities = $this->entities($account, $level, $filters);
        $insights = $this->insights($account, $level, $since, $until);

        return $entities->map(fn (AdEntity $e) => [
            'id' => $e->id,
            'external_id' => $e->external_id,
            'parent_id' => $e->parent_external_id,
            'name' => $e->name,
            'status' => $e->status,
            'effective_status' => $e->effective_status,
            'objective' => $e->objective,
            'daily_budget' => $e->daily_budget,
            'lifetime_budget' => $e->lifetime_budget,
            'insights' => $insights[$e->external_id] ?? null,
        ])->values()->all();
    }

    /** @param array<string,mixed> $filters @return \Illuminate\Support\Collection<int,AdEntity> */
    private function entities(AdAccount $account, string $level, array $filters)
    {
        $q = AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', $level);

        if ($level === 'adset' && ! empty($filters['campaign_ids'])) {
            $q->whereIn('parent_external_id', $filters['campaign_ids']);
        }
        // Ad level: ticked adsets win (narrowest); otherwise fall back to the
        // selected campaigns' adsets so the Ad tab stays scoped to the campaign.
        if ($level === 'ad') {
            if (! empty($filters['adset_ids'])) {
                $q->whereIn('parent_external_id', $filters['adset_ids']);
            } elseif (! empty($filters['campaign_ids'])) {
                $q->whereIn('parent_external_id', $this->adsetIdsForCampaigns($account, $filters['campaign_ids']));
            }
        }
        if (! empty($filters['q'])) {
            $q->where('name', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['objective'])) {
            $q->where('objective', $filters['objective']);
        }
        if (! empty($filters['id'])) {
            $q->where('external_id', $filters['id']);
        }

        return $q->orderBy('name')->get();
    }

    /**
     * External ids of the adsets belonging to the given campaigns (one level down).
     *
     * @param  list<string>  $campaignIds
     * @return list<string>
     */
    private function adsetIdsForCampaigns(AdAccount $account, array $campaignIds): array
    {
        return AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', 'adset')
            ->whereIn('parent_external_id', $campaignIds)
            ->pluck('external_id')
            ->all();
    }

    /**
     * Insights per entity for the range — one Graph call at this level, cached 5'.
     *
     * @return array<string, array<string,mixed>> external_id => metrics
     */
    private function insights(AdAccount $account, string $level, string $since, string $until): array
    {
        if (! $this->registry->has($account->provider)) {
            return [];
        }

        $key = "ads.report.{$account->getKey()}.{$level}.{$since}.{$until}";

        return Cache::remember($key, 300, function () use ($account, $level, $since, $until) {
            $rows = $this->registry->for($account->provider)->fetchInsights(
                (string) $account->access_token,
                $account->external_account_id,
                $level,
                ['time_range' => ['since' => $since, 'until' => $until]],
            );
            $idField = ['campaign' => 'campaign_id', 'adset' => 'adset_id', 'ad' => 'ad_id'][$level] ?? 'campaign_id';

            $out = [];
            foreach ($rows as $r) {
                $eid = (string) ($r->raw[$idField] ?? $r->externalId);
                $out[$eid] = $this->metrics($r);
            }

            return $out;
        });
    }

    /** @return array<string,mixed> */
    private function metrics(AdInsightDTO $r): array
    {
        return [
            'spend' => $r->spend,
            'impressions' => $r->impressions,
            'clicks' => $r->clicks,
            'reach' => $r->reach,
            'ctr' => $r->ctr,
            'cpc' => $r->cpc,
            'cpm' => $r->cpm,
            'frequency' => $r->frequency,
            'purchase_roas' => $r->purchaseRoas,
            'messaging_conversations' => $r->messagingConversations,
            'leads' => $r->leads,
            'purchases' => $r->purchases,
            'results' => $r->results,
        ];
    }
}
