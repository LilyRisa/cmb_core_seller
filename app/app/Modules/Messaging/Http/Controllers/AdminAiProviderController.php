<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Rules\SafeProviderUrl;
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
 * `code` = slug instance tự do (duy nhất); `adapter` chọn connector class
 * (anthropic|openai_compatible|manual). Nhiều instance có thể dùng cùng adapter
 * (deepseek/qwen/openrouter đều openai_compatible). `api_key` encrypted; response
 * luôn MASK (không reveal).
 *
 * `capabilities` đọc từ connector class (không cho admin tự claim).
 */
class AdminAiProviderController extends Controller
{
    public function __construct(private AiAssistantRegistry $registry) {}

    /** Preset gợi ý cho FE auto-điền base_url/model (không bắt buộc dùng). */
    private const PRESETS = [
        'openai_compatible' => [
            ['name' => 'OpenAI', 'base_url' => 'https://api.openai.com', 'default_model' => 'gpt-4o-mini'],
            ['name' => 'DeepSeek', 'base_url' => 'https://api.deepseek.com', 'default_model' => 'deepseek-chat'],
            ['name' => 'Qwen', 'base_url' => 'https://dashscope-intl.aliyuncs.com/compatible-mode', 'default_model' => 'qwen-plus'],
            ['name' => 'OpenRouter', 'base_url' => 'https://openrouter.ai/api', 'default_model' => 'openai/gpt-4o-mini'],
            ['name' => 'Gemini', 'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'default_model' => 'gemini-2.0-flash'],
        ],
        'anthropic' => [
            ['name' => 'Anthropic Claude', 'base_url' => 'https://api.anthropic.com', 'default_model' => 'claude-opus-4-7'],
        ],
        'custom_http' => [
            ['name' => 'Mẫu HTTP tùy chỉnh', 'base_url' => 'https://your-llm.example.com/v1/chat', 'default_model' => 'your-model'],
        ],
        'manual' => [
            ['name' => 'Manual (test/dev)', 'base_url' => null, 'default_model' => null],
        ],
    ];

    public function index(): JsonResponse
    {
        $rows = AiProvider::query()->orderBy('sort_order')->orderBy('code')->get();

        $adapters = array_map(fn (string $a) => [
            'adapter' => $a,
            'presets' => self::PRESETS[$a] ?? [],
        ], $this->registry->adapters());

        return response()->json([
            'data' => $rows->map(fn (AiProvider $p) => $this->present($p))->all(),
            'adapters' => $adapters,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9][a-z0-9_-]{1,31}$/', 'unique:ai_providers,code'],
            'adapter' => ['required', 'string', Rule::in($this->registry->adapters())],
            'display_name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],
            // base_url: endpoint cho openai_compatible (host) và custom_http (URL đầy đủ).
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl, 'required_if:adapter,openai_compatible,custom_http'],
            'default_model' => ['nullable', 'string', 'max:64', 'required_if:adapter,openai_compatible'],
            'pricing' => ['nullable', 'array'],
            ...$this->adapterConfigRules(true),
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $provider = AiProvider::create(array_merge($data, [
            'created_by_admin_id' => Auth::guard('admin_web')->id(),
        ]));

        AuditLog::record('messaging.ai.provider_create', null, ['code' => $provider->code, 'adapter' => $provider->adapter]);

        return response()->json(['data' => $this->present($provider)], 201);
    }

    public function update(string $code, Request $request): JsonResponse
    {
        $provider = AiProvider::query()->findOrFail($code);

        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],   // gửi rỗng = giữ nguyên (không xoá)
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl],
            'default_model' => ['nullable', 'string', 'max:64'],
            'pricing' => ['nullable', 'array'],
            ...$this->adapterConfigRules(false),
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:255'],
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
     * Test connection — kiểm TẤT CẢ năng lực provider KHAI BÁO (không chỉ chat):
     *   - `reply.suggest` ⇒ thử sinh 1 reply "hello".
     *   - `embedding`     ⇒ thử embed 1 đoạn text (QUAN TRỌNG cho trợ lý Support RAG —
     *     provider chat OK nhưng embedding sai vẫn làm hỏng RAG; trước đây test bỏ sót).
     *
     * Trả `{ ok, results: { chat?: {...}, embedding?: {...} }, sample?, reason?, message? }`.
     * `ok` = mọi năng lực đã thử đều pass. KHÔNG bao giờ 500 (mọi lỗi → ok:false có lý do).
     */
    public function test(string $code): JsonResponse
    {
        $row = AiProvider::query()->find($code);
        if (! $row) {
            return response()->json(['error' => ['code' => 'UNKNOWN_AI_PROVIDER', 'message' => "Provider [{$code}] không tồn tại."]], 404);
        }

        try {
            $connector = $this->registry->make($code); // resolve theo adapter, inject code
        } catch (\Throwable $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'not_configured', 'message' => Str::limit($e->getMessage(), 200), 'results' => []]]);
        }

        $results = [];

        if ($connector->supports('reply.suggest')) {
            $results['chat'] = $this->probe(fn () => [
                'ok' => true,
                'sample' => Str::limit($connector->generateReply(
                    new AiContext(tenantId: 0, providerCode: $code),
                    new ConversationSnapshot(conversationId: 0, provider: 'admin_test', buyerName: 'Test',
                        recentMessages: [['direction' => 'inbound', 'kind' => 'text', 'body' => 'hello', 'sent_at' => null]]),
                    null,
                )->body, 120),
            ]);
        }

        if ($connector->supports('embedding')) {
            $embModel = (string) system_setting('help_assistant.embedding_model', config('support.assistant.embedding.model', 'text-embedding-3-small'));
            $results['embedding'] = $this->probe(function () use ($connector, $code, $embModel) {
                $dto = $connector->embed(new AiContext(tenantId: 0, providerCode: $code, meta: ['embedding_model' => $embModel]), 'hello');

                return $dto->dimension > 0
                    ? ['ok' => true, 'dimension' => $dto->dimension, 'model' => $dto->model]
                    : ['ok' => false, 'reason' => 'empty_vector', 'message' => 'Provider trả vector rỗng.'];
            });
        }

        // ok = có ít nhất 1 năng lực thử được & không năng lực nào thất bại.
        $ok = $results !== [] && ! collect($results)->contains(fn ($r) => ($r['ok'] ?? false) === false);
        // Field phẳng cũ (giữ tương thích FE): ưu tiên chat, fallback embedding.
        $primary = $results['chat'] ?? $results['embedding'] ?? ['reason' => 'no_capability', 'message' => 'Provider không có năng lực test được.'];

        return response()->json(['data' => [
            'ok' => $ok,
            'sample' => $primary['sample'] ?? null,
            'reason' => $primary['reason'] ?? null,
            'message' => $primary['message'] ?? null,
            'results' => $results,
        ]]);
    }

    /**
     * Chạy 1 phép thử năng lực, bắt mọi lỗi → mảng kết quả (không ném).
     *
     * @param  callable():array<string,mixed>  $fn
     * @return array<string,mixed>
     */
    private function probe(callable $fn): array
    {
        try {
            return $fn();
        } catch (UnsupportedOperation $e) {
            return ['ok' => false, 'reason' => 'connector_not_implemented', 'message' => $e->getMessage()];
        } catch (ProviderNotConfigured $e) {
            return ['ok' => false, 'reason' => 'not_configured', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)];
        }
    }

    /**
     * Validation rules cho `adapter_config` (riêng adapter `custom_http`). `$onCreate`
     * ép required_if khi tạo; khi update adapter immutable nên chỉ optional (gửi gì lưu nấy,
     * không gửi = giữ nguyên vì validate() chỉ trả key có trong request).
     *
     * @return array<string, array<int, mixed>>
     */
    private function adapterConfigRules(bool $onCreate): array
    {
        $req = $onCreate ? ['required_if:adapter,custom_http'] : [];

        return [
            'adapter_config' => ['nullable', 'array', ...$req],
            'adapter_config.method' => ['nullable', 'string', Rule::in(['POST', 'PUT', 'GET'])],
            'adapter_config.headers' => ['nullable', 'array'],
            'adapter_config.headers.*' => ['nullable', 'string', 'max:1000'],
            'adapter_config.request_template' => ['nullable', 'string', 'max:8000', ...$req],
            'adapter_config.response_path' => ['nullable', 'string', 'max:255', ...$req],
            'adapter_config.usage' => ['nullable', 'array'],
            'adapter_config.usage.prompt_path' => ['nullable', 'string', 'max:255'],
            'adapter_config.usage.completion_path' => ['nullable', 'string', 'max:255'],
        ];
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
            'adapter' => $p->adapter,
            'display_name' => $p->display_name,
            'has_api_key' => filled($p->getRawOriginal('api_key')),
            'base_url' => $p->base_url,
            'default_model' => $p->default_model,
            'pricing' => $p->pricing ?? [],
            'adapter_config' => $p->adapter_config,
            'is_active' => (bool) $p->is_active,
            'sort_order' => $p->sort_order,
            'notes' => $p->notes,
            'capabilities' => $capabilities,
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }
}
