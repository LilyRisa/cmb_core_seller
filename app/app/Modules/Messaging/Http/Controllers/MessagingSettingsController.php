<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\AiFlowExclusionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Cấu hình Messaging cấp tenant — chọn AI provider (trong list super-admin đã
 * active), giờ vắng mặt, fallback template. SPEC-0024 §6.1 `/tenant/settings/messaging`.
 *
 * Permission `messaging.ai.config`. Tenant KHÔNG thấy api_key (chỉ code + tên).
 */
class MessagingSettingsController extends Controller
{
    public function __construct(
        private AiAssistantRegistry $registry,
        private AiFlowExclusionService $exclusion,
    ) {}

    public function show(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $tenantId = app(CurrentTenant::class)->id();
        $setting = MessagingSetting::query()->find($tenantId);

        return response()->json([
            'data' => [
                'ai_provider_code' => $setting?->ai_provider_code,
                'ai_enabled' => (bool) $setting?->ai_enabled,
                'auto_mode_marketplace' => (bool) $setting?->auto_mode_marketplace,
                'auto_mode_facebook' => (bool) $setting?->auto_mode_facebook,
                'away_hours' => $setting?->away_hours,
                'fallback_template_id' => $setting?->fallback_template_id,
                'available_providers' => $this->availableProviders(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $activeCodes = $this->registry->activeProviders();

        $data = $request->validate([
            'ai_provider_code' => ['nullable', 'string', Rule::in($activeCodes)],
            'ai_enabled' => ['nullable', 'boolean'],
            'auto_mode_marketplace' => ['nullable', 'boolean'],
            'auto_mode_facebook' => ['nullable', 'boolean'],
            'away_hours' => ['nullable', 'array'],
            'fallback_template_id' => ['nullable', 'integer'],
        ]);

        $tenantId = app(CurrentTenant::class)->id();

        $setting = MessagingSetting::query()->firstOrNew(['tenant_id' => $tenantId]);
        $facebookWasOn = (bool) $setting->auto_mode_facebook;

        $setting->fill($data);
        $setting->tenant_id = $tenantId;
        // `auto_mode` cũ (deprecated) = OR 2 nhóm — giữ ý nghĩa cho consumer còn sót.
        $setting->auto_mode = (bool) $setting->auto_mode_marketplace || (bool) $setting->auto_mode_facebook;
        $setting->save();

        // Loại trừ Tầng 2 (ADR-0022 §4): bật FB AI auto ⇒ pause flow `inbox_any` FB.
        $pausedFlows = 0;
        if (! $facebookWasOn && (bool) $setting->auto_mode_facebook) {
            $pausedFlows = $this->exclusion->pauseFacebookCatchAllFlows($tenantId);
        }

        AuditLog::record('messaging.ai.config_change', null, [
            'fields' => array_keys($data),
            'paused_catch_all_flows' => $pausedFlows,
        ]);

        $response = $this->show($request);
        $payload = $response->getData(true);
        $payload['meta'] = ['paused_catch_all_flows' => $pausedFlows];

        return response()->json($payload);
    }

    /** @return list<array{code:string, name:string}> */
    private function availableProviders(): array
    {
        $overrides = AiProvider::query()->where('is_active', true)->pluck('display_name', 'code');

        $out = [];
        foreach ($this->registry->activeProviders() as $code) {
            $name = $overrides[$code] ?? null;
            if (! $name) {
                try {
                    $name = $this->registry->for($code)->displayName();
                } catch (\Throwable) {
                    $name = $code;
                }
            }
            $out[] = ['code' => $code, 'name' => $name];
        }

        return $out;
    }
}
