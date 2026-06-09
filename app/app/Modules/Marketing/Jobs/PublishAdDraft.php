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
 * Publish one AdDraft to Facebook: create Campaign → N ad sets → N ads (ACTIVE),
 * resume-first (skip any node whose external id is already stored in payload ⇒
 * idempotent on retry). On any error the draft is marked `failed` with the message;
 * partial ids are kept so a re-publish resumes from the failed node. No auto-retry
 * (`tries = 1`).
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
            // Normalize to the tree shape so per-node external ids can persist (resume).
            $payload = (array) ($draft->payload ?? []);
            $payload['adsets'] = $mapper->adsetNodes($draft);
            $draft->payload = $payload;
            $draft->save();

            // Guard: không có nhóm/quảng cáo ⇒ fail RÕ trước khi tạo campaign (tránh campaign mồ côi).
            if ($payload['adsets'] === []) {
                $draft->forceFill([
                    'status' => AdDraft::STATUS_FAILED,
                    'last_error' => 'Bản nháp chưa có nhóm quảng cáo/quảng cáo — vui lòng thêm trước khi xuất bản.',
                ])->save();

                return;
            }

            // Resume-first; nếu campaign cũ bị ARCHIVED/DELETED (re-publish bản nháp đã từng
            // tạo campaign mồ côi rồi bị lưu trữ) ⇒ tạo lại campaign + reset id 1 lần rồi thử lại.
            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    $this->createTree($draft, $connector, $mapper, $token, $acc, (string) $account->currency);
                    $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHED])->save();

                    return;
                } catch (\Throwable $e) {
                    if ($attempt === 0 && $this->isStaleCampaignError($e)) {
                        $this->resetForRecreate($draft);

                        continue;
                    }
                    throw $e;
                }
            }
        } catch (\Throwable $e) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => $e->getMessage()])->save();
        }
    }

    /** Tạo campaign (nếu chưa có) → các ad set → các ad. Resume theo external_id đã lưu. */
    private function createTree(AdDraft $draft, AdsWriteConnector $connector, AdDraftSpecMapper $mapper, string $token, string $acc, string $currency): void
    {
        if (! $draft->campaign_external_id) {
            $draft->campaign_external_id = $connector->createCampaign($token, $acc, $mapper->campaign($draft, $currency));
            $draft->save();
        }
        $campaignId = (string) $draft->campaign_external_id;
        $payload = (array) $draft->payload;

        foreach ((array) ($payload['adsets'] ?? []) as $i => $adsetNode) {
            if (empty($payload['adsets'][$i]['external_id'])) {
                $payload['adsets'][$i]['external_id'] = $connector->createAdSet($token, $acc, $mapper->adSet($draft, (array) $adsetNode, $campaignId, $currency));
                $draft->payload = $payload;
                $draft->save();
            }
            $adsetId = (string) $payload['adsets'][$i]['external_id'];

            foreach ((array) ($adsetNode['ads'] ?? []) as $j => $adNode) {
                if (empty($payload['adsets'][$i]['ads'][$j]['external_id'])) {
                    $payload['adsets'][$i]['ads'][$j]['external_id'] = $connector->createAd($token, $acc, $mapper->ad($draft, (array) $adNode, $adsetId));
                    $draft->payload = $payload;
                    $draft->save();
                }
            }
        }
    }

    /** Lỗi FB cho biết campaign đã lưu trữ/xoá (không thể gắn ad set) ⇒ cần tạo campaign mới. */
    private function isStaleCampaignError(\Throwable $e): bool
    {
        $m = mb_strtolower($e->getMessage());

        return str_contains($m, '1487866')      // archived campaign
            || str_contains($m, 'lưu trữ')
            || str_contains($m, 'archived')
            || str_contains($m, 'deleted')
            || str_contains($m, 'does not exist')
            || str_contains($m, 'không tồn tại');
    }

    /** Xoá campaign + mọi external_id trong payload để publish lại tạo cây mới. */
    private function resetForRecreate(AdDraft $draft): void
    {
        $payload = (array) $draft->payload;
        foreach ((array) ($payload['adsets'] ?? []) as $i => $as) {
            $payload['adsets'][$i]['external_id'] = null;
            foreach ((array) ($as['ads'] ?? []) as $j => $ad) {
                $payload['adsets'][$i]['ads'][$j]['external_id'] = null;
            }
        }
        $draft->payload = $payload;
        $draft->campaign_external_id = null;
        $draft->save();
    }
}
