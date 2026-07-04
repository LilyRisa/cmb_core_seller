<?php

namespace CMBcoreSeller\Modules\VisualSearch\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Support\VisionModelGate;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Super-admin: chọn provider AI RIÊNG cho bước vision re-rank (chấm ảnh top-5),
 * tách khỏi provider chat. `/api/v1/admin/ai-visual-rerank/*` (guard admin_web,
 * KHÔNG tenant). Lưu tại system_setting('visual_search.rerank.provider_code').
 * Rỗng ⇒ VisionReRanker fallback provider chat. SPEC 2026-07-05.
 */
class AdminVisualRerankController extends Controller
{
    private const SETTING_KEY = 'visual_search.rerank.provider_code';

    public function __construct(
        private AiAssistantRegistry $registry,
        private SystemSettingService $settings,
    ) {}

    public function index(): JsonResponse
    {
        $providers = AiProvider::query()->orderBy('sort_order')->orderBy('code')->get()
            ->map(fn (AiProvider $p) => [
                'code' => $p->code,
                'display_name' => $p->display_name,
                'default_model' => $p->default_model,
                'is_active' => (bool) $p->is_active,
                'vision' => $p->default_model ? VisionModelGate::enabledFor($p->default_model) : false,
            ])->values()->all();

        return response()->json(['data' => [
            'selected_provider_code' => (string) system_setting(self::SETTING_KEY, '') ?: null,
            'providers' => $providers,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));

        if ($code !== '' && ! in_array($code, $this->registry->activeProviders(), true)) {
            return response()->json(['error' => [
                'code' => 'PROVIDER_NOT_ACTIVE',
                'message' => 'Provider không tồn tại hoặc chưa bật.',
            ]], 422);
        }

        $this->settings->set(self::SETTING_KEY, $code, Auth::guard('admin_web')->id());
        AuditLog::record('visual_search.rerank.provider_set', null, ['provider_code' => $code]);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function test(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));
        if ($code === '') {
            return response()->json(['data' => ['ok' => false, 'reason' => 'no_provider', 'message' => 'Chưa chọn provider.']]);
        }

        try {
            $connector = $this->registry->for($code);
            // Ảnh mẫu 1x1 PNG (base64) — chỉ để kiểm provider có nhận input ảnh.
            $sample = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
            $out = $connector->analyzeImages(
                new AiContext(tenantId: 0, providerCode: $code, meta: ['mode' => 'visual_rerank_test']),
                [$sample],
                'Đây là ảnh thử. Trả về DUY NHẤT JSON {"match": 0}.',
            );

            return response()->json(['data' => ['ok' => true, 'sample' => Str::limit($out, 120)]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)]]);
        }
    }
}
