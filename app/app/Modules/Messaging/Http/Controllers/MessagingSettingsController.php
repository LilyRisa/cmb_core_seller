<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
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
    public function __construct(private AiAssistantRegistry $registry) {}

    public function show(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $tenantId = app(CurrentTenant::class)->id();
        $setting = MessagingSetting::query()->find($tenantId);

        return response()->json([
            'data' => [
                'ai_provider_code' => $setting?->ai_provider_code,
                'ai_enabled' => (bool) ($setting?->ai_enabled ?? false),
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
            'away_hours' => ['nullable', 'array'],
            'fallback_template_id' => ['nullable', 'integer'],
        ]);

        $tenantId = app(CurrentTenant::class)->id();

        $setting = MessagingSetting::query()->firstOrNew(['tenant_id' => $tenantId]);
        $setting->fill($data);
        $setting->tenant_id = $tenantId;
        $setting->save();

        AuditLog::record('messaging.ai.config_change', null, ['fields' => array_keys($data)]);

        return $this->show($request);
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
