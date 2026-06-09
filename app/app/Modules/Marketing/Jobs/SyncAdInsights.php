<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Near-real-time insights poll for one ad account (account + campaign/adset/ad).
 * Upserts the latest snapshot per (entity, window). Adaptive pacing: when the
 * `x-fb-ads-insights-throttle` header is hot, flag + release to back off (BUC).
 */
class SyncAdInsights implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 900;

    /** @var array<int,int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $adAccountId, public string $window = 'today')
    {
        $this->onQueue('marketing-sync');
    }

    public function uniqueId(): string
    {
        return "ads-insights:{$this->adAccountId}";
    }

    public function handle(AdsRegistry $registry): void
    {
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($this->adAccountId);
        if (! $account || $account->status !== AdAccount::STATUS_ACTIVE || ! $registry->has($account->provider)) {
            return;
        }

        $connector = $registry->for($account->provider);
        $token = (string) $account->access_token;
        $query = ['date_preset' => $this->window];
        $throttle = null;

        // Refresh account health (disabled / payment / policy) — cheap single-node read.
        // Use a query-builder update (not the model) so a failure here (e.g. the
        // health migration not yet run, or the node read failing) never dirties the
        // model and so can't poison the insights save below.
        try {
            $health = $connector->fetchAccountStatus($token, $account->external_account_id);
            AdAccount::withoutGlobalScope(TenantScope::class)->whereKey($account->getKey())->update([
                'fb_account_status' => $health['account_status'],
                'disable_reason' => $health['disable_reason'],
                'health_checked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Non-fatal — a disabled account may still report insights/no-op.
        }

        // Account-level first, then the entity tree.
        if ($this->ingest($account, null, $account->external_account_id, 'account', $connector->fetchInsights($token, $account->external_account_id, 'account', $query, $throttle))
            && $this->paceIfHot($account, $throttle)) {
            return;
        }

        // Provider có báo cáo advertiser-scoped (vd TikTok /report/integrated/get/) ⇒ gộp
        // 1 call/level cho cả account (3 call) thay vì N+1 per-entity. FB không khai
        // capability này nên rớt xuống nhánh per-entity cũ (hành vi không đổi).
        if ($connector->supports('insights.account_report')) {
            $this->syncAccountScoped($account, $connector, $token, $query);
        } else {
            foreach (AdEntity::withoutGlobalScope(TenantScope::class)->where('ad_account_id', $account->getKey())->get() as $entity) {
                $rows = $connector->fetchInsights($token, $entity->external_id, $entity->level, $query, $throttle);
                $this->ingest($account, (int) $entity->id, $entity->external_id, $entity->level, $rows);
                if ($this->paceIfHot($account, $throttle)) {
                    return;
                }
            }
        }

        $meta = (array) ($account->meta ?? []);
        $meta['insights_throttled'] = false;
        $account->forceFill(['meta' => $meta, 'insights_synced_at' => now()])->save();
    }

    /**
     * Advertiser-scoped sync: 1 report call per level for the whole account, then
     * map each returned row to its AdEntity (by external_id) and ingest. Used by
     * providers exposing `insights.account_report` (vd TikTok).
     *
     * @param  array<string,mixed>  $query
     */
    private function syncAccountScoped(AdAccount $account, AdsConnector $connector, string $token, array $query): void
    {
        // external_id => entity id, keyed by level.
        $entities = AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())->get(['id', 'level', 'external_id']);
        /** @var array<string,array<string,int>> $byLevel */
        $byLevel = [];
        foreach ($entities as $e) {
            $byLevel[(string) $e->level][(string) $e->external_id] = (int) $e->id;
        }

        foreach (['campaign', 'adset', 'ad'] as $level) {
            if (empty($byLevel[$level])) {
                continue;
            }
            $rows = $connector->fetchInsights($token, $account->external_account_id, $level, $query);
            foreach ($rows as $r) {
                $entityId = $byLevel[$level][$r->externalId] ?? null;
                if ($entityId === null) {
                    continue; // entity chưa đồng bộ — bỏ qua, lần sync entity sau sẽ có
                }
                $this->ingest($account, $entityId, $r->externalId, $level, [$r]);
            }
        }
    }

    /**
     * Upsert one entity's insight rows. Returns true (always) so it composes in the
     * short-circuit above.
     *
     * @param  list<AdInsightDTO>  $rows
     */
    private function ingest(AdAccount $account, ?int $entityId, string $externalId, string $level, array $rows): bool
    {
        $cutoff = CarbonImmutable::now()->subDays(28)->startOfDay();
        foreach ($rows as $r) {
            $dateStop = $r->dateStop !== '' ? CarbonImmutable::parse($r->dateStop) : CarbonImmutable::now();
            AdInsightSnapshot::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                [
                    'ad_account_id' => (int) $account->getKey(),
                    'level' => $level,
                    'external_id' => $externalId,
                    'window' => $this->window,
                    'date_start' => $r->dateStart !== '' ? $r->dateStart : now()->toDateString(),
                    'date_stop' => $r->dateStop !== '' ? $r->dateStop : now()->toDateString(),
                ],
                [
                    'tenant_id' => (int) $account->tenant_id,
                    'ad_entity_id' => $entityId,
                    'is_finalizing' => $dateStop->gte($cutoff),
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
                    'metrics' => $r->raw,
                    'fetched_at' => now(),
                ],
            );
        }

        return true;
    }

    /** When throttle is hot, flag the account + release to back off. Returns true if paced. */
    private function paceIfHot(AdAccount $account, ?AdInsightThrottleDTO $throttle): bool
    {
        if (! $throttle instanceof AdInsightThrottleDTO || ! $throttle->isHot()) {
            return false;
        }

        $meta = (array) ($account->meta ?? []);
        $meta['insights_throttled'] = true;
        $account->forceFill(['meta' => $meta])->save();
        $this->release(120);

        return true;
    }
}
