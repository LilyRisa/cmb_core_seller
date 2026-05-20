<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super-admin CRUD AI provider — `/api/v1/admin/ai-providers/*`
 * (guard `admin_web`, KHÔNG tenant). SPEC-0024 §6.1 Admin SPA.
 *
 * `code` phải là 1 connector đã register ở `AiAssistantRegistry` (claude/openai/
 * gemini/local_llm/manual) — super-admin chỉ NHẬP credentials cho code có sẵn,
 * không tạo code tuỳ ý. `api_key` encrypted; response luôn MASK (không reveal).
 *
 * `capabilities` đọc từ connector class (không cho admin tự claim).
 */
class AdminAiProviderController extends Controller
{
    public function __construct(private AiAssistantRegistry $registry) {}

    public function index(): JsonResponse
    {
        $rows = AiProvider::query()->orderBy('code')->get();

        return response()->json([
            'data' => $rows->map(fn (AiProvider $p) => $this->present($p))->all(),
            'registered_codes' => $this->registry->providers(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', Rule::in($this->registry->providers()), 'unique:ai_providers,code'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'default_model' => ['nullable', 'string', 'max:64'],
            'pricing' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $provider = AiProvider::create(array_merge($data, [
            'created_by_admin_id' => Auth::guard('admin_web')->id(),
        ]));

        AuditLog::record('messaging.ai.provider_create', null, ['code' => $provider->code]);

        return response()->json(['data' => $this->present($provider)], 201);
    }

    public function update(string $code, Request $request): JsonResponse
    {
        $provider = AiProvider::query()->findOrFail($code);

        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],   // gửi rỗng = giữ nguyên (không xoá)
            'base_url' => ['nullable', 'string', 'max:255'],
            'default_model' => ['nullable', 'string', 'max:64'],
            'pricing' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Không gửi api_key (hoặc rỗng) ⇒ không ghi đè key cũ.
        if (! array_key_exists('api_key', $data) || $data['api_key'] === null || $data['api_key'] === '') {
            unset($data['api_key']);
        }

        $provider->fill($data)->save();

        AuditLog::record('messaging.ai.provider_update', null, ['code' => $code, 'fields' => array_keys($data)]);

        return response()->json(['data' => $this->present($provider)]);
    }

    public function destroy(string $code): JsonResponse
    {
        $provider = AiProvider::query()->findOrFail($code);

        // Soft disable (giữ record + credentials). Tenant đang dùng ⇒ AiSuggestionService
        // resolveProviderCode tự fallback (provider không còn trong activeProviders()).
        $provider->update(['is_active' => false]);

        AuditLog::record('messaging.ai.provider_disable', null, ['code' => $code]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /**
     * Test connection: sinh 1 reply "hello". Connector chưa wire (stub) ⇒
     * `UnsupportedOperation` ⇒ trả 200 `{ok:false, reason:'connector_not_implemented'}`
     * (KHÔNG 500 — super-admin click thử provider mới không được crash).
     */
    public function test(string $code): JsonResponse
    {
        if (! $this->registry->has($code)) {
            return response()->json(['error' => ['code' => 'UNKNOWN_AI_PROVIDER', 'message' => "Provider [{$code}] chưa được đăng ký."]], 404);
        }

        $connector = $this->registry->make($code);

        try {
            $reply = $connector->generateReply(
                new AiContext(tenantId: 0, providerCode: $code),
                new ConversationSnapshot(
                    conversationId: 0,
                    provider: 'admin_test',
                    buyerName: 'Test',
                    recentMessages: [['direction' => 'inbound', 'kind' => 'text', 'body' => 'hello', 'sent_at' => null]],
                ),
                null,
            );

            return response()->json(['data' => ['ok' => true, 'sample' => Str::limit($reply->body, 120)]]);
        } catch (UnsupportedOperation $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'connector_not_implemented', 'message' => $e->getMessage()]]);
        } catch (\CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'not_configured', 'message' => $e->getMessage()]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)]]);
        }
    }

    /** Response shape — KHÔNG bao giờ lộ api_key (chỉ cờ đã set). */
    private function present(AiProvider $p): array
    {
        $capabilities = [];
        try {
            $capabilities = $this->registry->make($p->code)->capabilities();
        } catch (\Throwable) {
            // code không còn register (đã gỡ connector) — bỏ qua.
        }

        return [
            'code' => $p->code,
            'display_name' => $p->display_name,
            'has_api_key' => filled($p->getRawOriginal('api_key')),
            'base_url' => $p->base_url,
            'default_model' => $p->default_model,
            'pricing' => $p->pricing ?? [],
            'is_active' => (bool) $p->is_active,
            'capabilities' => $capabilities,
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }
}
