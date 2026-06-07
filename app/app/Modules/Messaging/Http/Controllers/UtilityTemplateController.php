<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Http\Resources\UtilityTemplateResource;
use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use CMBcoreSeller\Modules\Messaging\Services\UtilityTemplateService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * CRUD + submit/sync mẫu tin nhắn tiện ích (`utility_templates`) — SPEC-0032.
 *
 * Permission: đọc cần `messaging.view`; mutate/submit/sync cần `messaging.template.manage`.
 * `BelongsToTenant` global scope đảm bảo chỉ thấy template của tenant hiện tại; mọi
 * thao tác connector cô lập trong {@see UtilityTemplateService} (luật vàng).
 */
class UtilityTemplateController extends Controller
{
    public function __construct(private readonly UtilityTemplateService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('messaging.view');

        $q = UtilityTemplate::query();

        if ($accountId = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $accountId);
        }
        if ($status = $request->query('status')) {
            $q->where('status', (string) $status);
        }

        $q->orderBy('name');

        return UtilityTemplateResource::collection(
            $q->paginate(min(100, max(1, (int) $request->query('per_page', 50))))
        );
    }

    public function show(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $template = UtilityTemplate::query()->findOrFail($id);

        return response()->json(['data' => (new UtilityTemplateResource($template))->toArray($request)]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $tenantId = app(CurrentTenant::class)->id();
        $data = $this->validatePayload($request, $tenantId);

        $this->assertOwnedAccount((int) $data['channel_account_id']);

        $template = UtilityTemplate::create([
            'channel_account_id' => (int) $data['channel_account_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'language' => $data['language'] ?? 'vi',
            'body' => $data['body'],
            'buttons' => $data['buttons'] ?? [],
            'variables' => $data['variables'] ?? [],
            'status' => UtilityTemplate::STATUS_DRAFT,
            'enabled' => $data['enabled'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('messaging.utility_template.create', $template, ['code' => $template->code]);

        return response()->json(['data' => (new UtilityTemplateResource($template))->toArray($request)], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $template = UtilityTemplate::query()->findOrFail($id);

        // Chỉ sửa khi còn draft/rejected — template đã pending/approved phía Meta thì khoá.
        if (! in_array($template->status, [UtilityTemplate::STATUS_DRAFT, UtilityTemplate::STATUS_REJECTED], true)) {
            return response()->json([
                'error' => ['code' => 'UTILITY_TEMPLATE_LOCKED', 'message' => 'Chỉ sửa được mẫu ở trạng thái nháp hoặc bị từ chối.'],
            ], 422);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'language' => ['sometimes', 'string', 'max:8'],
            'body' => ['sometimes', 'string', 'max:5000'],
            'buttons' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $template->fill($data)->save();

        AuditLog::record('messaging.utility_template.update', $template, ['fields' => array_keys($data)]);

        return response()->json(['data' => (new UtilityTemplateResource($template))->toArray($request)]);
    }

    public function submit(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $template = UtilityTemplate::query()->findOrFail($id);

        try {
            $this->service->submit($template);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => ['code' => 'UTILITY_TEMPLATE_SUBMIT_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }

        AuditLog::record('messaging.utility_template.submit', $template, ['status' => $template->status]);

        return response()->json(['data' => (new UtilityTemplateResource($template->refresh()))->toArray($request)]);
    }

    public function sync(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $template = UtilityTemplate::query()->findOrFail($id);

        try {
            $this->service->syncStatus($template);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => ['code' => 'UTILITY_TEMPLATE_SYNC_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }

        return response()->json(['data' => (new UtilityTemplateResource($template->refresh()))->toArray($request)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $template = UtilityTemplate::query()->findOrFail($id);
        $code = $template->code;
        $template->delete(); // soft delete

        AuditLog::record('messaging.utility_template.delete', $template, ['code' => $code]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** @return array<string, mixed> */
    private function validatePayload(Request $request, int $tenantId): array
    {
        return $request->validate([
            'channel_account_id' => ['required', 'integer'],
            'code' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('utility_templates', 'code')
                    ->where('tenant_id', $tenantId)
                    ->where('channel_account_id', (int) $request->input('channel_account_id'))
                    ->where('language', (string) $request->input('language', 'vi'))
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:160'],
            'language' => ['nullable', 'string', 'max:8'],
            'body' => ['required', 'string', 'max:5000'],
            'buttons' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'enabled' => ['nullable', 'boolean'],
        ]);
    }

    /** Page phải thuộc tenant hiện tại (global scope) — chặn tạo template chéo tenant. */
    private function assertOwnedAccount(int $channelAccountId): void
    {
        ChannelAccount::query()->findOrFail($channelAccountId);
    }
}
