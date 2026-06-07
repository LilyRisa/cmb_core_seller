<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Exceptions\AttachmentInvalid;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Services\AiFlowExclusionService;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowGraphValidator;
use CMBcoreSeller\Modules\Messaging\Services\MediaRelayService;
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
    public function __construct(
        private FlowGraphValidator $validator,
        private MediaRelayService $media,
        private AiFlowExclusionService $exclusion,
    ) {}

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
        $pageIds = $data['channel_account_ids'] ?? [];
        unset($data['channel_account_ids']);
        // Tạo luôn ở trạng thái nháp — xuất bản qua endpoint /publish sau khi validate.
        $flow = AutomationFlow::create($data + [
            'status' => AutomationFlow::STATUS_DRAFT,
            'provider' => $data['provider'] ?? 'facebook_page',
            'version' => 1,
            'created_by' => $request->user()?->id,
        ]);
        $this->syncPages($flow, $pageIds);

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
        $hasPages = array_key_exists('channel_account_ids', $data);
        $pageIds = $data['channel_account_ids'] ?? [];
        unset($data['channel_account_ids']);
        if (array_key_exists('graph', $data)) {
            $data['version'] = (int) $flow->version + 1;
        }
        $flow->fill($data)->save();
        if ($hasPages || array_key_exists('applies_all_pages', $data)) {
            $this->syncPages($flow, $pageIds);
        }

        AuditLog::record('messaging.flow.update', $flow, ['trigger_type' => $flow->trigger_type]);

        // Active sẵn rồi đổi trigger sang `inbox_any` (FB) ⇒ vẫn loại trừ AI (ADR-0022 §4).
        $disabledAi = false;
        if ($flow->status === AutomationFlow::STATUS_ACTIVE && $this->exclusion->isFacebookCatchAll($flow)) {
            $disabledAi = $this->exclusion->disableFacebookAiAuto((int) $flow->tenant_id);
        }

        return response()->json([
            'data' => $this->present($flow),
            'meta' => ['disabled_facebook_ai' => $disabledAi],
        ]);
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

        // Loại trừ Tầng 2 (ADR-0022 §4): kích hoạt flow `inbox_any` FB ⇒ tắt FB AI auto.
        $disabledAi = false;
        if ($this->exclusion->isFacebookCatchAll($flow)) {
            $disabledAi = $this->exclusion->disableFacebookAiAuto((int) $flow->tenant_id);
        }

        return response()->json([
            'data' => $this->present($flow),
            'meta' => ['disabled_facebook_ai' => $disabledAi],
        ]);
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
            'applies_all_pages' => $src->applies_all_pages,
            'created_by' => $request->user()?->id,
        ]);
        // Sao chép phạm vi page (pivot) từ flow gốc.
        $copy->pages()->sync(
            $src->pages()->pluck('channel_accounts.id')
                ->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $copy->tenant_id]])->all()
        );

        AuditLog::record('messaging.flow.duplicate', $copy, ['source_id' => $src->id]);

        return response()->json(['data' => $this->present($copy)], 201);
    }

    /**
     * Upload media (ảnh/video/âm thanh/file) cho node "Gửi tin" lúc dựng kịch bản.
     * Lưu vào object storage; trả descriptor để canvas nhúng vào node.data.attachments.
     * Runtime (SendMessageNodeExecutor) tạo Message + MessageAttachment từ storage_path.
     */
    public function media(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $flow = AutomationFlow::query()->findOrFail($id);
        $data = $request->validate([
            'kind' => ['required', 'in:image,video,audio,file'],
            'file' => ['required', 'file'],
        ]);

        try {
            $stored = $this->media->storeUpload((int) $flow->tenant_id, (int) $flow->id, $request->file('file'), $data['kind']);
        } catch (AttachmentInvalid $e) {
            return response()->json(['error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()]], 422);
        }

        return response()->json(['data' => [
            'kind' => $data['kind'],
            'storage_path' => $stored['storage_path'],
            'mime' => $stored['mime'],
            'size_bytes' => $stored['size_bytes'],
            'filename' => $stored['filename'],
        ]], 201);
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
            // SPEC 0035 — phạm vi page.
            'applies_all_pages' => ['nullable', 'boolean'],
            'channel_account_ids' => ['nullable', 'array'],
            'channel_account_ids.*' => ['integer'],
        ]);
    }

    /**
     * Đồng bộ pivot flow↔page (lọc page thuộc tenant, chống cross-tenant).
     * `applies_all_pages=true` ⇒ xoá pivot (áp mọi trang).
     *
     * @param  list<int>  $pageIds
     */
    private function syncPages(AutomationFlow $flow, array $pageIds): void
    {
        if ($flow->applies_all_pages) {
            $flow->pages()->sync([]);

            return;
        }

        $ownIds = ChannelAccount::query()
            ->where('tenant_id', $flow->tenant_id)
            ->whereIn('id', array_map('intval', $pageIds))
            ->pluck('id');

        $flow->pages()->sync(
            $ownIds->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $flow->tenant_id]])->all()
        );
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
            'applies_all_pages' => (bool) $f->applies_all_pages,
            'channel_account_ids' => $f->pages()->pluck('channel_accounts.id')->all(),
            'created_at' => $f->created_at?->toIso8601String(),
            'updated_at' => $f->updated_at?->toIso8601String(),
        ];
    }
}
