<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\AiCampaignGenerateRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AdDraftResource;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Services\AiCampaignGenerator;
use CMBcoreSeller\Modules\Marketing\Services\AiCampaignRequest;
use CMBcoreSeller\Modules\Marketing\Services\LandingPageReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Tạo bản nháp chiến dịch Facebook bằng AI từ một bài viết page. Trả AdDraft (draft) +
 * recommendations của AI. Người dùng xem/chỉnh trong wizard rồi xuất bản qua luồng có sẵn.
 * Plan-gate `marketing_facebook` ở route; tính 1 lượt AI qua MarketingAnalysisClient.
 */
class AiCampaignController extends Controller
{
    public function generate(
        int $id,
        AiCampaignGenerateRequest $request,
        AiCampaignGenerator $generator,
        LandingPageReader $reader,
    ): JsonResponse {
        Gate::authorize('marketing.ads.create');
        $account = AdAccount::query()->findOrFail($id);
        $v = $request->validated();

        // Đọc landing page (CTA bài viết hoặc nhập tay) làm ngữ cảnh AI — best-effort.
        $landingUrl = $v['landing_url'] ?? $v['link_url'] ?? null;
        $landingText = $landingUrl !== null ? $reader->read((string) $landingUrl) : null;

        $timezone = (string) (((array) ($account->meta ?? []))['timezone'] ?? 'Asia/Ho_Chi_Minh');

        $req = new AiCampaignRequest(
            adAccountId: (int) $account->id,
            tenantId: (int) $account->tenant_id,
            userId: $request->user()?->id,
            objective: (string) $v['objective'],
            mode: (string) $v['mode'],
            placementMode: (string) $v['placement_mode'],
            pageId: (string) $v['page_id'],
            pagePostId: (string) $v['page_post_id'],
            caption: $v['caption'] ?? null,
            likes: (int) ($v['likes'] ?? 0),
            comments: (int) ($v['comments'] ?? 0),
            shares: (int) ($v['shares'] ?? 0),
            linkUrl: $v['link_url'] ?? null,
            ctaType: $v['cta_type'] ?? null,
            landingText: $landingText,
            pixelId: $v['pixel_id'] ?? null,
            conversionEvent: $v['conversion_event'] ?? null,
            startTime: $v['start_time'] ?? null,
            currency: (string) ($account->currency ?? 'VND'),
            timezone: $timezone,
            prompt: (string) ($v['prompt'] ?? ''),
        );

        try {
            $result = $generator->generate($req);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => ['code' => 'AI_CAMPAIGN_INVALID', 'message' => $e->getMessage()],
            ], 422);
        }

        return (new AdDraftResource($result['draft']))
            ->additional(['meta' => ['recommendations' => $result['recommendations']]])
            ->response()
            ->setStatusCode(201);
    }
}
