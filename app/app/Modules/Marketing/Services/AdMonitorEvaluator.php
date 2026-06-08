<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookMoney;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookResultMap;
use CMBcoreSeller\Modules\Marketing\Events\AdMonitorActionTaken;
use CMBcoreSeller\Modules\Marketing\Events\AdMonitorThresholdApproaching;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitor;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitorAction;
use CMBcoreSeller\Modules\Marketing\Notifications\AdMonitorActionNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Evaluates ad monitors: raise budget when cost-per-result is cheap, pause when
 * it's too expensive. Campaign monitors override their adsets'. Emails Owner/Admin
 * on any action.
 */
class AdMonitorEvaluator
{
    public function __construct(private AdsRegistry $registry) {}

    public function evaluateAll(): void
    {
        AdAccount::withoutGlobalScope(TenantScope::class)
            ->where('status', 'active')->orderBy('id')
            ->each(fn (AdAccount $a) => $this->evaluateAccount($a));
    }

    /**
     * @return list<array<string,mixed>> the actions taken
     */
    public function evaluateAccount(AdAccount $account): array
    {
        $monitors = AdMonitor::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())->where('enabled', true)->get();
        if ($monitors->isEmpty() || ! $this->registry->has($account->provider)) {
            return [];
        }
        // If this FB account is connected by several tenants, only the automation owner
        // runs monitors — otherwise both would compound budgets / fight over pause.
        if (! $account->isAutomationOwner()) {
            Log::info('marketing.monitor.skipped_not_owner', ['account' => $account->getKey(), 'external' => $account->external_account_id]);

            return [];
        }
        $connector = $this->registry->for($account->provider);
        if (! ($connector instanceof AdsWriteConnector)) {
            return [];
        }

        $token = (string) $account->access_token;
        $currency = (string) ($account->currency ?? 'VND');
        $entities = AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())->get()->keyBy('external_id');

        $monitoredCampaigns = array_flip(
            $monitors->where('target_level', AdMonitor::LEVEL_CAMPAIGN)->pluck('target_external_id')->all()
        );

        $actions = [];
        try {
            $campInsights = $this->insightsBy($connector, $token, $account->external_account_id, 'campaign');
            $adsetInsights = $this->insightsBy($connector, $token, $account->external_account_id, 'adset');
        } catch (\Throwable $e) {
            Log::warning('marketing.monitor.fetch_failed', ['account' => $account->getKey(), 'error' => $e->getMessage()]);

            return [];
        }

        foreach ($monitors as $m) {
            /** @var AdEntity|null $entity */
            $entity = $entities->get($m->target_external_id);
            $m->last_evaluated_at = now();
            if ($entity === null) {
                $m->save();

                continue;
            }
            // Campaign monitor overrides its adsets.
            if ($m->target_level === AdMonitor::LEVEL_ADSET
                && $entity->parent_external_id !== null
                && isset($monitoredCampaigns[$entity->parent_external_id])) {
                $m->save();

                continue;
            }

            $dto = $m->target_level === AdMonitor::LEVEL_CAMPAIGN
                ? ($campInsights[$m->target_external_id] ?? null)
                : ($adsetInsights[$m->target_external_id] ?? null);
            if ($dto === null) {
                $m->save();

                continue;
            }

            $results = $this->resultValue($entity, $entities, $dto);
            $action = $this->applyRules($connector, $token, $currency, $m, $entity, $dto->spend, $results);
            if ($action !== null) {
                $m->last_action = (string) $action['type'];
                $m->last_action_at = now();
                $actions[] = $action + ['name' => $entity->name, 'level' => $m->target_level];
                $actionRecord = AdMonitorAction::withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => (int) $account->tenant_id,
                    'ad_account_id' => (int) $account->getKey(),
                    'ad_monitor_id' => (int) $m->id,
                    'target_level' => $m->target_level,
                    'target_external_id' => $m->target_external_id,
                    'target_name' => $entity->name,
                    'type' => (string) $action['type'],
                    'cpr' => $action['cpr'] ?? null,
                    'spend' => $action['spend'] ?? $dto->spend,
                    'results' => $action['results'] ?? $results,
                    'from_budget' => $action['from'] ?? null,
                    'to_budget' => $action['to'] ?? null,
                ]);
                // SPEC 0036 — thông báo in-app cho mọi thành viên (Notifications nghe event này).
                AdMonitorActionTaken::dispatch((int) $account->tenant_id, (int) $actionRecord->getKey(), (string) $entity->name, (string) $action['type']);
            }
            $m->save();
        }

        if ($actions !== []) {
            $this->notify($account, $actions);
        }

        return $actions;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function applyRules(AdsWriteConnector $connector, string $token, string $currency, AdMonitor $m, AdEntity $entity, int $spend, int $results): ?array
    {
        // Pause (precedence): too expensive per result, OR money already burned with
        // ZERO results — with 0 results the effective cost/result is already ≥ the
        // threshold once spend reaches it. The min_results guard does NOT gate pausing
        // a money-burning campaign.
        if ($m->pause_enabled && $m->pause_above !== null && $spend > 0) {
            $exceeds = $results > 0
                ? ((int) round($spend / $results)) > $m->pause_above
                : $spend >= $m->pause_above;
            if ($exceeds) {
                $connector->updateEntity($token, $m->target_level, $m->target_external_id, ['status' => 'PAUSED'], $currency);
                $entity->status = 'PAUSED';
                $entity->effective_status = 'PAUSED';
                $entity->save();

                return ['type' => 'pause', 'cpr' => $results > 0 ? (int) round($spend / $results) : null, 'spend' => $spend, 'results' => $results];
            }

            // SPEC 0036 — chưa vượt nhưng đã TIẾN GẦN ngưỡng tắt ⇒ cảnh báo in-app (Notifications nghe event).
            $metric = $results > 0 ? (int) round($spend / $results) : $spend;
            if ($metric >= (int) ceil($m->pause_above * AdMonitor::APPROACHING_RATIO)) {
                AdMonitorThresholdApproaching::dispatch(
                    (int) $m->tenant_id,
                    (int) $m->getKey(),
                    (string) $entity->name,
                    (string) $m->target_level,
                    $results > 0 ? (int) round($spend / $results) : null,
                    (int) $m->pause_above,
                );
            }
        }

        // Increase: only on real, cheap results (respect min_results — don't scale on noise).
        if ($m->increase_enabled && $m->increase_below !== null
            && $results > 0 && $results >= max(1, $m->min_results)
            && ((int) round($spend / $results)) < $m->increase_below) {
            $cpr = (int) round($spend / $results);
            $currentMinor = (int) ($entity->daily_budget ?? 0);
            if ($currentMinor <= 0) {
                return null; // no own budget (e.g. campaign on adset-budget) ⇒ nothing to raise
            }
            $currentMajor = FacebookMoney::toMajorUnits($currentMinor, $currency);
            $newMajor = (int) round($currentMajor * (1 + $m->increase_step_pct / 100));
            if ($m->max_daily_budget !== null) {
                $newMajor = min($newMajor, $m->max_daily_budget);
            }
            if ($newMajor <= $currentMajor) {
                return null; // already at cap
            }
            $connector->updateEntity($token, $m->target_level, $m->target_external_id, ['daily_budget_major' => $newMajor], $currency);
            $entity->daily_budget = (int) FacebookMoney::toMinorUnits($newMajor, $currency);
            $entity->save();

            return ['type' => 'increase', 'from' => $currentMajor, 'to' => $newMajor, 'cpr' => $cpr];
        }

        return null;
    }

    /**
     * "Kết quả" theo đúng sự kiện tối ưu — dùng chung FacebookResultMap với cột báo cáo.
     * adset: optimization_goal + custom_event_type ở meta của nó; campaign: suy từ adset con.
     *
     * @param  Collection<string,AdEntity>  $entities
     */
    private function resultValue(AdEntity $entity, $entities, AdInsightDTO $dto): int
    {
        $objective = $entity->level === AdEntity::LEVEL_CAMPAIGN
            ? $entity->objective
            : $entities->get((string) $entity->parent_external_id)?->objective;

        if ($entity->level === AdEntity::LEVEL_ADSET) {
            $meta = (array) ($entity->meta ?? []);
        } else {
            // campaign: lấy meta đại diện từ adset con (ưu tiên adset có custom_event_type).
            $adsets = $entities->filter(
                fn (AdEntity $e) => $e->level === AdEntity::LEVEL_ADSET
                    && (string) $e->parent_external_id === (string) $entity->external_id
            );
            $repr = $adsets->first(fn (AdEntity $e) => ! empty(((array) ($e->meta ?? []))['custom_event_type'])) ?? $adsets->first();
            $meta = (array) ($repr->meta ?? []);
        }

        $goal = $meta['optimization_goal'] ?? null;
        $event = $meta['custom_event_type'] ?? null;

        return FacebookResultMap::count($dto->actions, FacebookResultMap::resolveCode($objective, $goal, $event));
    }

    /**
     * @return array<string, AdInsightDTO> keyed by entity external id
     */
    private function insightsBy(AdsConnector $connector, string $token, string $externalAccountId, string $level): array
    {
        $idField = ['campaign' => 'campaign_id', 'adset' => 'adset_id'][$level];
        $out = [];
        foreach ($connector->fetchInsights($token, $externalAccountId, $level, ['date_preset' => 'today']) as $r) {
            $out[(string) ($r->raw[$idField] ?? $r->externalId)] = $r;
        }

        return $out;
    }

    /** @param list<array<string,mixed>> $actions */
    private function notify(AdAccount $account, array $actions): void
    {
        $tenant = Tenant::find($account->tenant_id);
        if (! $tenant) {
            return;
        }
        $recipients = $tenant->users()->wherePivotIn('role', [Role::Owner->value, Role::Admin->value])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AdMonitorActionNotification($account, $actions));
        }
    }
}
