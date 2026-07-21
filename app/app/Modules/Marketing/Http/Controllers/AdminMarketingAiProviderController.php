<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\CredentialProbe;
use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;
use CMBcoreSeller\Modules\Marketing\Rules\SafeProviderUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super-admin CRUD for the DEDICATED marketing AI provider (forecast/strategy).
 * Separate from messaging `ai_providers`. Guard: admin_web. api_key returned plaintext
 * (super-admin-only surface — see safe()).
 */
class AdminMarketingAiProviderController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => MarketingAiProvider::query()->orderBy('code')->get()->map(fn (MarketingAiProvider $p) => $this->safe($p))->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateProvider($request, true);
        $this->persist($data['code'], $data);

        return response()->json(['data' => $this->safe(MarketingAiProvider::findOrFail($data['code']))]);
    }

    public function update(string $code, Request $request): JsonResponse
    {
        $provider = MarketingAiProvider::findOrFail($code);
        $data = $this->validateProvider($request, false);
        // Empty api_key on update ⇒ keep existing.
        if (($data['api_key'] ?? null) === null || $data['api_key'] === '') {
            unset($data['api_key']);
        }
        $this->persist($code, $data);

        return response()->json(['data' => $this->safe($provider->fresh())]);
    }

    public function destroy(string $code): JsonResponse
    {
        MarketingAiProvider::findOrFail($code)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function testDraft(Request $request, CredentialProbe $probe): JsonResponse
    {
        $data = $request->validate([
            'adapter' => ['required', 'in:anthropic,openai_compatible'],
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl],
            'api_key' => ['nullable', 'string'],
            'default_model' => ['nullable', 'string', 'max:64'],
        ]);

        return response()->json(['data' => $probe->probeChat(
            $data['adapter'],
            $data['base_url'] ?? null,
            $data['api_key'] ?? null,
            $data['default_model'] ?? null,
        )]);
    }

    /** @return array<string,mixed> */
    private function validateProvider(Request $request, bool $creating): array
    {
        return $request->validate([
            'code' => [$creating ? 'required' : 'sometimes', 'string', 'max:32', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'adapter' => [$creating ? 'required' : 'sometimes', 'in:anthropic,openai_compatible,manual'],
            'api_key' => ['nullable', 'string'],
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl],
            'default_model' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @param array<string,mixed> $data */
    private function persist(string $code, array $data): void
    {
        // Single active provider: activating one deactivates the rest.
        if (! empty($data['is_active'])) {
            MarketingAiProvider::query()->where('code', '!=', $code)->update(['is_active' => false]);
        }
        MarketingAiProvider::query()->updateOrCreate(['code' => $code], $data);
    }

    /** @return array<string,mixed> */
    private function safe(MarketingAiProvider $p): array
    {
        return [
            'code' => $p->code,
            'display_name' => $p->display_name,
            'adapter' => $p->adapter,
            'base_url' => $p->base_url,
            'default_model' => $p->default_model,
            'is_active' => (bool) $p->is_active,
            'has_key' => $p->getRawOriginal('api_key') !== null,
            // [TIKTOK-REVIEW-TEMP] Cùng quy ước với AdminAiProviderController (Messaging,
            // present()): trang chỉ super-admin (guard admin_web) truy cập ⇒ hiển thị thẳng
            // key để dùng chung SecretInput (spec 2026-07-21 §5.3). Trước đây field này
            // không tồn tại trong response ⇒ FE phải dùng input "để trống = giữ nguyên".
            'api_key' => $p->api_key,
        ];
    }
}
