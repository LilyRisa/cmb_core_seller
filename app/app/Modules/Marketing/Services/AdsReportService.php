<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookResultMap;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Ads-Manager-style report: entity metadata from DB + insights fetched ON-DEMAND
 * for a date range (cached 5'). Drill-down filters at the server. SPEC 2026-06-04.
 */
class AdsReportService
{
    public function __construct(private AdsRegistry $registry) {}

    private static function versionKey(int $accountId): string
    {
        return "ads.report.ver.{$accountId}";
    }

    /** Invalidate the on-demand report cache for an account (used by "Làm mới"). */
    public static function bumpCache(int $accountId): void
    {
        $key = self::versionKey($accountId);
        Cache::put($key, ((int) Cache::get($key, 0)) + 1, now()->addDays(30));
    }

    /**
     * @param  array{campaign_ids?:list<string>, adset_ids?:list<string>, q?:string, objective?:string, id?:string}  $filters
     * @return list<array<string,mixed>>
     */
    public function report(AdAccount $account, string $level, string $since, string $until, array $filters = []): array
    {
        $entities = $this->entities($account, $level, $filters);
        $insights = $this->insights($account, $level, $since, $until);
        // Ngữ cảnh tối ưu (objective + optimization_goal + custom_event_type) cho từng dòng,
        // để tính "Kết quả" đúng sự kiện như Ads Manager (campaign suy từ adset).
        $resultCtx = $this->resultContext($account, $level, $entities);

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
            'insights' => $this->withResult($insights[$e->external_id] ?? null, $resultCtx[$e->external_id] ?? []),
        ])->values()->all();
    }

    /**
     * Gắn "Kết quả" đúng sự kiện tối ưu vào metrics: results (số) + result_type (mã) + result_label
     * (nhãn VN). Dùng FacebookResultMap. Bỏ `actions` thô khỏi payload trả về FE.
     *
     * @param  array<string,mixed>|null  $metrics
     * @param  array{objective?:?string, goal?:?string, event?:?string}  $ctx
     * @return array<string,mixed>|null
     */
    private function withResult(?array $metrics, array $ctx): ?array
    {
        if ($metrics === null) {
            return null;
        }
        /** @var array<string,int> $actions */
        $actions = (array) ($metrics['actions'] ?? []);
        unset($metrics['actions']);

        // Provider KHÔNG có breakdown `actions` (vd TikTok) ⇒ FacebookResultMap không áp dụng;
        // dùng `results` connector đã tính sẵn (vd conversion). FB luôn có actions nên không vào nhánh này.
        if ($actions === []) {
            $metrics['results'] = (int) ($metrics['results'] ?? 0);
            $metrics['result_type'] = null;
            $metrics['result_label'] = null;

            return $metrics;
        }

        $code = FacebookResultMap::resolveCode($ctx['objective'] ?? null, $ctx['goal'] ?? null, $ctx['event'] ?? null);
        if ($code === 'result') {
            // Chưa biết sự kiện ⇒ lấy "chuyển đổi sâu nhất" và gắn nhãn theo đúng loại tìm được.
            [$code, $value] = FacebookResultMap::genericResultTyped($actions);
        } else {
            $value = FacebookResultMap::count($actions, $code);
        }
        $known = $code !== 'result';
        $metrics['results'] = $value;
        $metrics['result_type'] = $known ? $code : null;
        $metrics['result_label'] = $known ? FacebookResultMap::label($code) : null;

        return $metrics;
    }

    /**
     * external_id ⇒ {objective, goal, event} dùng để chọn "Kết quả".
     *   - adset: optimization_goal + custom_event_type ở meta của chính nó.
     *   - campaign: objective của nó + suy goal/event từ các adset con (đại diện).
     *   - ad: kế thừa goal/event từ adset cha.
     *
     * @param  Collection<int,AdEntity>  $entities
     * @return array<string, array{objective:?string, goal:?string, event:?string}>
     */
    private function resultContext(AdAccount $account, string $level, $entities): array
    {
        if ($level === 'adset') {
            $out = [];
            foreach ($entities as $e) {
                $meta = (array) ($e->meta ?? []);
                $out[$e->external_id] = ['objective' => $e->objective, 'goal' => $meta['optimization_goal'] ?? null, 'event' => $meta['custom_event_type'] ?? null];
            }

            return $out;
        }

        if ($level === 'campaign') {
            $campaignExtIds = $entities->pluck('external_id')->all();
            $byCampaign = $this->adsetOptimizationByCampaign($account, $campaignExtIds);
            $out = [];
            foreach ($entities as $e) {
                $opt = $byCampaign[$e->external_id] ?? ['goal' => null, 'event' => null];
                $out[$e->external_id] = ['objective' => $e->objective, 'goal' => $opt['goal'], 'event' => $opt['event']];
            }

            return $out;
        }

        // ad: kế thừa từ adset cha (parent_external_id = adset external_id).
        $parentIds = $entities->pluck('parent_external_id')->filter()->unique()->values()->all();
        $adsetMeta = $this->optimizationByExternalId($account, 'adset', $parentIds);
        $out = [];
        foreach ($entities as $e) {
            $opt = $adsetMeta[(string) $e->parent_external_id] ?? ['goal' => null, 'event' => null];
            $out[$e->external_id] = ['objective' => null, 'goal' => $opt['goal'], 'event' => $opt['event']];
        }

        return $out;
    }

    /**
     * campaign external_id ⇒ {goal, event} đại diện, lấy từ adset con đầu tiên có dữ liệu
     * (campaign thường đồng nhất sự kiện tối ưu giữa các adset).
     *
     * @param  list<string>  $campaignExtIds
     * @return array<string, array{goal:?string, event:?string}>
     */
    private function adsetOptimizationByCampaign(AdAccount $account, array $campaignExtIds): array
    {
        if ($campaignExtIds === []) {
            return [];
        }
        $out = [];
        AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', 'adset')
            ->whereIn('parent_external_id', $campaignExtIds)
            ->get(['parent_external_id', 'meta'])
            ->each(function (AdEntity $a) use (&$out) {
                $cid = (string) $a->parent_external_id;
                $meta = (array) ($a->meta ?? []);
                $existing = $out[$cid] ?? ['goal' => null, 'event' => null];
                // Ưu tiên adset có custom_event_type (chuyển đổi) để campaign hiện đúng sự kiện.
                $out[$cid] = [
                    'goal' => $existing['goal'] ?? ($meta['optimization_goal'] ?? null),
                    'event' => $existing['event'] ?? ($meta['custom_event_type'] ?? null),
                ];
            });

        return $out;
    }

    /**
     * external_id ⇒ {goal, event} cho 1 mức (adset).
     *
     * @param  list<string>  $extIds
     * @return array<string, array{goal:?string, event:?string}>
     */
    private function optimizationByExternalId(AdAccount $account, string $level, array $extIds): array
    {
        if ($extIds === []) {
            return [];
        }
        $out = [];
        AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', $level)
            ->whereIn('external_id', $extIds)
            ->get(['external_id', 'meta'])
            ->each(function (AdEntity $a) use (&$out) {
                $meta = (array) ($a->meta ?? []);
                $out[(string) $a->external_id] = ['goal' => $meta['optimization_goal'] ?? null, 'event' => $meta['custom_event_type'] ?? null];
            });

        return $out;
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

        // Cache key carries a per-account version so "Làm mới" can bust it (bumpCache).
        $ver = (int) Cache::get(self::versionKey((int) $account->getKey()), 0);
        $key = "ads.report.{$account->getKey()}.v{$ver}.{$level}.{$since}.{$until}";

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
            // action_type ⇒ value (đã index) — withResult() dùng để tính Kết quả theo sự kiện tối ưu,
            // rồi loại bỏ khỏi payload trả FE.
            'actions' => $r->actions,
        ];
    }
}
