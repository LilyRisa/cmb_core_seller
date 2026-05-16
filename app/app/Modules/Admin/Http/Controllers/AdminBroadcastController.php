<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Admin\Models\Broadcast;
use CMBcoreSeller\Modules\Admin\Services\BroadcastService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * /api/v1/admin/broadcasts — gửi email thông báo cho user của tenant. SPEC 0023 §3.9.
 */
class AdminBroadcastController extends Controller
{
    public function __construct(protected BroadcastService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));
        $page = Broadcast::query()->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (Broadcast $b) => $this->resource($b))->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $b = Broadcast::query()->findOrFail($id);

        return response()->json(['data' => $this->resource($b)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_markdown' => ['required', 'string', 'max:50000'],
            'audience.kind' => ['required', Rule::in([
                Broadcast::AUDIENCE_ALL_OWNERS,
                Broadcast::AUDIENCE_ALL_ADMINS_AND_OWNERS,
                Broadcast::AUDIENCE_TENANT_IDS,
            ])],
            'audience.tenant_ids' => ['nullable', 'array'],
            'audience.tenant_ids.*' => ['integer'],
        ]);

        $broadcast = $this->service->send(
            $data['audience'],
            $data['subject'],
            $data['body_markdown'],
            (int) $request->user()->getKey(),
        );

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => (int) $request->user()->getKey(),
            'action' => 'admin.broadcast.send',
            'auditable_type' => $broadcast->getMorphClass(),
            'auditable_id' => $broadcast->getKey(),
            'changes' => [
                'subject' => $broadcast->subject,
                'audience' => $broadcast->audience,
                'recipient_count' => $broadcast->recipient_count,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json(['data' => $this->resource($broadcast)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Broadcast $b): array
    {
        return [
            'id' => $b->id,
            'subject' => $b->subject,
            'body_markdown' => $b->body_markdown,
            'audience' => $b->audience,
            'recipient_count' => $b->recipient_count,
            'sent_count' => $b->sent_count,
            'skipped_count' => $b->skipped_count,
            'sent_at' => $b->sent_at?->toIso8601String(),
            'created_by_user_id' => $b->created_by_user_id,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }
}
