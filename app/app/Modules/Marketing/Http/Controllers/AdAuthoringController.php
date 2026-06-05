<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Wizard authoring reads: Pages, Page posts (engagement + media), targeting search,
 * audience estimate, ad previews. Tenant-scoped; provider resolved via the registry.
 * Read permission marketing.view (Marketing is Owner/Admin-only). Tokens never leave.
 */
class AdAuthoringController extends Controller
{
    /** GET ad-accounts/{id}/pages */
    public function pages(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $pages = array_map(fn (PageRefDTO $p) => ['id' => $p->id, 'name' => $p->name],
            $connector->listPages((string) $account->access_token));

        return response()->json(['data' => $pages]);
    }

    /** GET ad-accounts/{id}/pages/{pageId}/posts */
    public function pagePosts(int $id, string $pageId, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $page = collect($connector->listPages((string) $account->access_token))->firstWhere('id', $pageId);
        abort_unless($page instanceof PageRefDTO, 404, 'Trang không tồn tại hoặc chưa được cấp quyền.');

        $limit = max(1, min(50, (int) $request->integer('limit', 25)));
        $posts = array_map(fn (PagePostDTO $p) => [
            'id' => $p->id,
            'message' => $p->message,
            'created_time' => $p->createdTime,
            'media_type' => $p->mediaType,
            'image_url' => $p->imageUrl,
            'likes' => $p->likes,
            'comments' => $p->comments,
            'shares' => $p->shares,
            'link_url' => $p->linkUrl,
            'cta_type' => $p->ctaType,
        ], $connector->listPagePosts($page->accessToken, $pageId, $limit));

        return response()->json(['data' => $posts]);
    }

    /** GET ad-accounts/{id}/targeting-search?q=&type= */
    public function targetingSearch(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $type = (string) $request->input('type', 'adinterest');
        $options = array_map(fn (TargetingOptionDTO $o) => [
            'id' => $o->id, 'name' => $o->name, 'type' => $o->type, 'audience_size' => $o->audienceSize,
        ], $connector->searchTargeting((string) $account->access_token, (string) $request->input('q', ''), $type));

        return response()->json(['data' => $options]);
    }

    /** POST ad-accounts/{id}/audience-estimate { targeting, optimization_goal } */
    public function audienceEstimate(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $size = $connector->estimateAudience(
            (string) $account->access_token,
            $account->external_account_id,
            (array) $request->input('targeting', []),
            (string) $request->input('optimization_goal', 'REACH'),
        );

        return response()->json(['data' => ['lower_bound' => $size->lowerBound, 'upper_bound' => $size->upperBound]]);
    }

    /** POST ad-accounts/{id}/ad-previews { creative, formats[] } */
    public function previews(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        /** @var list<string> $formats */
        $formats = array_values(array_filter((array) $request->input('formats', []), 'is_string'));
        if ($formats === []) {
            $formats = ['DESKTOP_FEED_STANDARD', 'MOBILE_FEED_STANDARD'];
        }

        $previews = array_map(fn (AdPreviewDTO $p) => ['format' => $p->format, 'body' => $p->body],
            $connector->generatePreviews((string) $account->access_token, $account->external_account_id, (array) $request->input('creative', []), $formats));

        return response()->json(['data' => $previews]);
    }

    /**
     * Resolve tenant-scoped account + its write-capable connector (or 422).
     *
     * @return array{0: AdAccount, 1: AdsWriteConnector}
     */
    private function resolve(int $id): array
    {
        /** @var AdAccount $account */
        $account = AdAccount::query()->findOrFail($id);
        $registry = app(AdsRegistry::class);
        $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
        abort_unless($connector instanceof AdsWriteConnector, 422, 'Tính năng tạo quảng cáo chưa được bật.');

        return [$account, $connector];
    }
}
