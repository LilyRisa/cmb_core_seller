<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowGraphValidator;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * CRUD + publish/validate kịch bản tự động (Flow Builder S3). Đọc cần
 * `messaging.view`; mutate cần `messaging.rule.manage` (dùng lại RBAC rule).
 *
 * `graph` jsonb do canvas (FE reactflow) sinh — KHÔNG nhập JSON tay. Engine
 * (`FlowEngine`/`FlowMatcher`) đọc bảng này runtime; chỉ flow `status=active`
 * mới khớp trigger. Xuất bản = validate đồ thị (spec §5.4) rồi set active.
 */
class AutomationFlowController extends Controller
{
    public function __construct(private FlowGraphValidator $validator) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('messaging.view');

        return JsonResource::collection(
            AutomationFlow::query()->latest('id')
                ->paginate(min(100, max(1, (int) $request->query('per_page', 50))))
                ->through(fn (AutomationFlow $f) => $this->present($f))
        );
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize('messaging.view');

        return response()->json(['data' => $this->present(AutomationFlow::query()->findOrFail($id))]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $data = $this->validatePayload($request, creating: true);
        // Tạo luôn ở trạng thái nháp — xuất bản qua endpoint /publish sau khi validate.
        $flow = AutomationFlow::create($data + [
            'status' => AutomationFlow::STATUS_DRAFT,
            'provider' => $data['provider'] ?? 'facebook_page',
            'version' => 1,
            'created_by' => $request->user()?->id,
        ]);

        AuditLog::record('messaging.flow.create', $flow, ['trigger_type' => $flow->trigger_type]);

        return response()->json(['data' => $this->present($flow)], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $flow = AutomationFlow::query()->findOrFail($id);
        $data = $this->validatePayload($request, creating: false);
        // Status KHÔNG sửa trực tiếp ở đây (qua publish/pause). Sửa flow đã active ⇒
        // bump version để phân biệt phiên bản (run mới đọc graph hiện tại).
        unset($data['status']);
        if (array_key_exists('graph', $data)) {
            $data['version'] = (int) $flow->version + 1;
        }
        $flow->fill($data)->save();

        AuditLog::record('messaging.flow.update', $flow, ['trigger_type' => $flow->trigger_type]);

        return response()->json(['data' => $this->present($flow)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $flow = AutomationFlow::query()->findOrFail($id);
        $flow->delete(); // soft

        AuditLog::record('messaging.flow.delete', $flow, ['trigger_type' => $flow->trigger_type]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** Dry-run validate đồ thị — trả danh sách lỗi để FE highlight (không đổi status). */
    public function validateGraph(int $id): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $flow = AutomationFlow::query()->findOrFail($id);
        $errors = $this->validator->validate($flow);

        return response()->json(['data' => ['valid' => $errors === [], 'errors' => $errors]]);
    }

    public function publish(int $id): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $flow = AutomationFlow::query()->findOrFail($id);
        $errors = $this->validator->validate($flow);
        if ($errors !== []) {
            return response()->json([
                'error' => [
                    'code' => 'flow_invalid',
                    'message' => 'Kịch bản chưa hợp lệ — sửa các bước được đánh dấu rồi xuất bản lại.',
                    'details' => ['errors' => $errors],
                ],
            ], 422);
        }

        $flow->update(['status' => AutomationFlow::STATUS_ACTIVE]);
        AuditLog::record('messaging.flow.publish', $flow, ['status' => $flow->status]);

        return response()->json(['data' => $this->present($flow)]);
    }

    public function pause(int $id): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $flow = AutomationFlow::query()->findOrFail($id);
        $flow->update(['status' => AutomationFlow::STATUS_PAUSED]);
        AuditLog::record('messaging.flow.pause', $flow, ['status' => $flow->status]);

        return response()->json(['data' => $this->present($flow)]);
    }

    public function duplicate(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $src = AutomationFlow::query()->findOrFail($id);
        $copy = AutomationFlow::create([
            'name' => trim($src->name).' (bản sao)',
            'provider' => $src->provider,
            'status' => AutomationFlow::STATUS_DRAFT,
            'trigger_type' => $src->trigger_type,
            'trigger_config' => $src->trigger_config,
            'graph' => $src->graph,
            'version' => 1,
            'enabled' => true,
            'created_by' => $request->user()?->id,
        ]);

        AuditLog::record('messaging.flow.duplicate', $copy, ['source_id' => $src->id]);

        return response()->json(['data' => $this->present($copy)], 201);
    }

    /** @return array<string,mixed> */
    private function validatePayload(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'name' => [$req, 'string', 'max:160'],
            'provider' => ['sometimes', 'string', 'max:32'],
            'trigger_type' => [$req, 'in:comment_on_post,comment_any,inbox_first_message,inbox_keyword,inbox_any'],
            'trigger_config' => ['nullable', 'array'],
            'trigger_config.post_ids' => ['nullable', 'array'],
            'trigger_config.keywords' => ['nullable', 'array'],
            'trigger_config.match' => ['nullable', 'in:any,all'],
            'graph' => ['nullable', 'array'],
            'graph.nodes' => ['nullable', 'array'],
            'graph.edges' => ['nullable', 'array'],
            'enabled' => ['nullable', 'boolean'],
        ]);
    }

    /** @return array<string,mixed> */
    private function present(AutomationFlow $f): array
    {
        return [
            'id' => $f->id,
            'name' => $f->name,
            'provider' => $f->provider,
            'status' => $f->status,
            'trigger_type' => $f->trigger_type,
            'trigger_config' => $f->trigger_config ?? [],
            'graph' => $f->graph ?? ['nodes' => [], 'edges' => []],
            'version' => (int) $f->version,
            'enabled' => (bool) $f->enabled,
            'created_at' => $f->created_at?->toIso8601String(),
            'updated_at' => $f->updated_at?->toIso8601String(),
        ];
    }
}
