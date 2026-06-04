<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Publish one AdDraft to Facebook: create Campaign→AdSet→Ad (PAUSED), resume-first
 * (skip a level whose external id is already stored ⇒ idempotent on retry). On any
 * error the draft is marked `failed` with the message; partial ids are kept so a
 * re-publish resumes from the failed level. No auto-retry (`tries = 1`).
 */
class PublishAdDraft implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 600;

    public function __construct(public int $draftId)
    {
        $this->onQueue('marketing-publish');
    }

    public function uniqueId(): string
    {
        return "publish-draft:{$this->draftId}";
    }

    public function handle(AdsRegistry $registry, AdDraftSpecMapper $mapper): void
    {
        /** @var AdDraft|null $draft */
        $draft = AdDraft::withoutGlobalScope(TenantScope::class)->find($this->draftId);
        if (! $draft) {
            return;
        }
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($draft->ad_account_id);

        $connector = $account && $registry->has($account->provider) ? $registry->for($account->provider) : null;
        if (! $account || ! $connector instanceof AdsWriteConnector || ! $connector->supports('ads.create')) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => 'Tài khoản/quảng cáo không hỗ trợ tạo.'])->save();

            return;
        }

        $token = (string) $account->access_token;
        $acc = $account->external_account_id;
        $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHING, 'last_error' => null])->save();

        try {
            if (! $draft->campaign_external_id) {
                $draft->campaign_external_id = $connector->createCampaign($token, $acc, $mapper->campaign($draft));
                $draft->save();
            }
            if (! $draft->adset_external_id) {
                $draft->adset_external_id = $connector->createAdSet($token, $acc, $mapper->adSet($draft, (string) $account->currency));
                $draft->save();
            }
            if (! $draft->ad_external_id) {
                $draft->ad_external_id = $connector->createAd($token, $acc, $mapper->ad($draft));
                $draft->save();
            }
            $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHED])->save();
        } catch (\Throwable $e) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => $e->getMessage()])->save();
        }
    }
}
