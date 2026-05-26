<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageTemplateResource;
use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use CMBcoreSeller\Modules\Messaging\Services\TemplateResolver;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * CRUD mẫu tin (`message_templates`) — SPEC-0024 S3.
 *
 * Permission: đọc cần `messaging.view`; mutate cần `messaging.template.manage`.
 * `vars` tự suy từ body qua {@see TemplateResolver::declaredVariables} nếu client
 * không gửi — giữ cột `vars` đồng bộ với `{{...}}` thực trong body.
 *
 * Audit: action `messaging.template.create|update|delete` (08-security §8.7).
 * `BelongsToTenant` global scope đảm bảo chỉ thấy template của tenant hiện tại.
 */
class TemplateController extends Controller
{
    public function __construct(private TemplateResolver $resolver) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('messaging.view');

        $q = MessageTemplate::query();

        if ($request->boolean('enabled_only')) {
            $q->where('enabled', true);
        }
        if ($provider = $request->query('provider')) {
            // Template scope rỗng = áp mọi provider; nếu có scope.providers thì lọc.
            $q->where(function ($qq) use ($provider) {
                $qq->whereNull('scope')
                    ->orWhereJsonLength('scope->providers', 0)
                    ->orWhereJsonContains('scope->providers', $provider);
            });
        }
        if ($threadType = $request->query('thread_type')) {
            // scope.thread_types rỗng/không khai báo = áp cả tin nhắn lẫn bình luận.
            $q->where(function ($qq) use ($threadType) {
                $qq->whereNull('scope')
                    ->orWhereJsonLength('scope->thread_types', 0)
                    ->orWhereJsonContains('scope->thread_types', $threadType);
            });
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $q->orderBy('name');

        return MessageTemplateResource::collection(
            $q->paginate(min(100, max(1, (int) $request->query('per_page', 50))))
        );
    }

    public function show(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $template = MessageTemplate::query()->findOrFail($id);

        return response()->json(['data' => (new MessageTemplateResource($template))->toArray($request)]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $tenantId = app(CurrentTenant::class)->id();

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('message_templates', 'code')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'vars' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'scope' => ['nullable', 'array'],
            'shortcut_key' => ['nullable', 'string', 'max:32'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $template = MessageTemplate::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'body' => $data['body'],
            'vars' => $data['vars'] ?? $this->resolver->declaredVariables($data['body']),
            'attachments' => $data['attachments'] ?? [],
            'scope' => $data['scope'] ?? [],
            'shortcut_key' => $data['shortcut_key'] ?? null,
            'enabled' => $data['enabled'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('messaging.template.create', $template, ['code' => $template->code]);

        return response()->json(['data' => (new MessageTemplateResource($template))->toArray($request)], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $template = MessageTemplate::query()->findOrFail($id);
        $tenantId = $template->tenant_id;

        $data = $request->validate([
            'code' => [
                'sometimes', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('message_templates', 'code')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($template->id),
            ],
            'name' => ['sometimes', 'string', 'max:160'],
            'body' => ['sometimes', 'string', 'max:5000'],
            'vars' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'scope' => ['nullable', 'array'],
            'shortcut_key' => ['nullable', 'string', 'max:32'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        // Nếu đổi body mà không gửi vars ⇒ tự suy lại vars từ body mới.
        if (array_key_exists('body', $data) && ! array_key_exists('vars', $data)) {
            $data['vars'] = $this->resolver->declaredVariables($data['body']);
        }

        $template->fill($data)->save();

        AuditLog::record('messaging.template.update', $template, ['fields' => array_keys($data)]);

        return response()->json(['data' => (new MessageTemplateResource($template))->toArray($request)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.template.manage');

        $template = MessageTemplate::query()->findOrFail($id);
        $code = $template->code;
        $template->delete(); // soft delete

        AuditLog::record('messaging.template.delete', $template, ['code' => $code]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
