<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * CRUD quy tắc auto-reply (SPEC-0024 S5). Đọc cần `messaging.view`; mutate cần
 * `messaging.rule.manage`. Engine (`AutoReplyEngine`) đọc bảng này runtime.
 */
class AutoReplyRuleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('messaging.view');

        return JsonResource::collection(
            AutoReplyRule::query()->orderBy('priority')->orderBy('id')
                ->paginate(min(100, max(1, (int) $request->query('per_page', 50))))
                ->through(fn (AutoReplyRule $r) => $this->present($r))
        );
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $data = $this->validatePayload($request, creating: true);

        $rule = AutoReplyRule::create($data + ['created_by' => $request->user()->id]);

        AuditLog::record('messaging.rule.create', $rule, ['trigger' => $rule->trigger]);

        return response()->json(['data' => $this->present($rule)], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $rule = AutoReplyRule::query()->findOrFail($id);
        $rule->fill($this->validatePayload($request, creating: false))->save();

        AuditLog::record('messaging.rule.update', $rule, ['trigger' => $rule->trigger]);

        return response()->json(['data' => $this->present($rule)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.rule.manage');

        $rule = AutoReplyRule::query()->findOrFail($id);
        $rule->delete(); // soft

        AuditLog::record('messaging.rule.delete', $rule, ['trigger' => $rule->trigger]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** @return array<string,mixed> */
    private function validatePayload(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'name' => [$req, 'string', 'max:160'],
            'trigger' => [$req, 'in:schedule,order_status,away_no_response,first_message,keyword'],
            'trigger_config' => ['nullable', 'array'],
            'filter' => ['nullable', 'array'],
            'action' => [$req, 'array'],
            'action.kind' => [$req, 'in:template,raw,ai_reply'],
            'action.template_id' => ['nullable', 'integer'],
            'action.raw_text' => ['nullable', 'string', 'max:5000'],
            'cooldown_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'enabled' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }

    private function present(AutoReplyRule $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'trigger' => $r->trigger,
            'trigger_config' => $r->trigger_config ?? [],
            'filter' => $r->filter ?? [],
            'action' => $r->action ?? [],
            'cooldown_seconds' => (int) $r->cooldown_seconds,
            'enabled' => (bool) $r->enabled,
            'priority' => (int) $r->priority,
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }
}
