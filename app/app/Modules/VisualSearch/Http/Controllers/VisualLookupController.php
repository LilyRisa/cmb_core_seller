<?php

namespace CMBcoreSeller\Modules\VisualSearch\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Công cụ "Tìm bằng ảnh" cho seller: upload ảnh → item khớp (tri-state). */
class VisualLookupController extends Controller
{
    public function __construct(
        private VisualItemSearch $search,
        private AiAssistantRegistry $registry,
    ) {}

    public function lookup(Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $maxKb = (int) config('visual_search.image.max_size_kb', 8192);
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'max:'.$maxKb],
            'rerank' => ['nullable', 'boolean'],
            'channel_account_id' => ['nullable', 'integer'],
        ]);

        $tenantId = app(CurrentTenant::class)->id();
        $file = $request->file('image');
        $input = VisualImageInput::fromBinary((string) file_get_contents($file->getRealPath()), (string) $file->getMimeType());

        $rerank = (bool) ($data['rerank'] ?? false);
        [$providerCode, $ctx] = $rerank ? $this->resolveProvider($tenantId) : [null, null];

        $opts = new VisualLookupOptions(
            channelAccountId: isset($data['channel_account_id']) ? (int) $data['channel_account_id'] : null,
            rerank: $rerank && $providerCode !== null,
            providerCode: $providerCode,
            aiContext: $ctx,
        );

        $result = $this->search->lookup($tenantId, $input, $opts);

        return response()->json(['data' => $result->toArray()]);
    }

    /** @return array{0:?string,1:?AiContext} */
    private function resolveProvider(int $tenantId): array
    {
        $active = $this->registry->activeProviders();
        if ($active === []) {
            return [null, null];
        }
        $chosen = DB::table('messaging_settings')->where('tenant_id', $tenantId)->value('ai_provider_code');
        $code = ($chosen && in_array($chosen, $active, true)) ? (string) $chosen : $active[0];

        return [$code, new AiContext(tenantId: $tenantId, providerCode: $code, meta: ['mode' => 'visual_rerank'])];
    }
}
