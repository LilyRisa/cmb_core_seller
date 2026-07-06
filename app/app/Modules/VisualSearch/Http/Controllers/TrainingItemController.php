<?php

namespace CMBcoreSeller\Modules\VisualSearch\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemDeleted;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use CMBcoreSeller\Modules\VisualSearch\Services\TrainingImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * CRUD item "Sản phẩm AI training" (nhập tay). Đọc `messaging.view`;
 * mutate `messaging.ai.train`. Scope per-page giống AI knowledge (SPEC 0035).
 */
class TrainingItemController extends Controller
{
    public function __construct(private TrainingImageService $images) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $items = VisualTrainingItem::query()
            ->withCount('images')
            ->orderByDesc('created_at')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 30))));

        return response()->json([
            'data' => array_map(fn (VisualTrainingItem $i) => $this->row($i), $items->items()),
            'meta' => ['total' => $items->total(), 'per_page' => $items->perPage(), 'current_page' => $items->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $data = $this->validatePayload($request, true);
        $tenantId = app(CurrentTenant::class)->id();

        $item = VisualTrainingItem::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'attributes' => $data['attributes'] ?? null,
            'ref_code' => $data['ref_code'] ?? null,
            'status' => VisualTrainingItem::STATUS_ACTIVE,
            'applies_all_pages' => (bool) ($data['applies_all_pages'] ?? true),
            'created_by' => $request->user()->id,
        ]);
        $this->syncPages($item, $data['channel_account_ids'] ?? []);

        $item->forceFill(['kb_status' => VisualTrainingItem::KB_PENDING])->save();
        event(new KnowledgeItemSaved($item->id));

        AuditLog::record('visual_search.item.create', $item, ['name' => $item->name]);

        return response()->json(['data' => $this->row($item->refresh())], 201);
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize('messaging.view');

        $item = VisualTrainingItem::query()->findOrFail($id);

        return response()->json(['data' => $this->row($item, withImages: true)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $item = VisualTrainingItem::query()->findOrFail($id);
        $data = $this->validatePayload($request, false);

        $item->fill(array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'ref_code' => $data['ref_code'] ?? null,
        ], fn ($v) => $v !== null));
        if (array_key_exists('attributes', $data)) {
            $item->attributes = $data['attributes'];
        }
        if (array_key_exists('applies_all_pages', $data)) {
            $item->applies_all_pages = (bool) $data['applies_all_pages'];
        }
        $item->save();

        if (array_key_exists('channel_account_ids', $data)) {
            $this->syncPages($item, $data['channel_account_ids'] ?? []);
        }

        $item->forceFill(['kb_status' => VisualTrainingItem::KB_PENDING])->save();
        event(new KnowledgeItemSaved($item->id));

        AuditLog::record('visual_search.item.update', $item, ['name' => $item->name]);

        return response()->json(['data' => $this->row($item->refresh(), withImages: true)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $item = VisualTrainingItem::query()->findOrFail($id);
        foreach (VisualTrainingImage::query()->where('item_id', $item->id)->get() as $image) {
            $this->images->deleteImage($image);
        }
        $item->pages()->sync([]);
        event(new KnowledgeItemDeleted($item->id));
        $item->delete();

        AuditLog::record('visual_search.item.delete', $item, ['name' => $item->name]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** @return array<string,mixed> */
    private function validatePayload(Request $request, bool $creating): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'attributes' => ['nullable', 'array'],
            'ref_code' => ['nullable', 'string', 'max:64'],
            'applies_all_pages' => ['nullable', 'boolean'],
            'channel_account_ids' => ['nullable', 'array'],
            'channel_account_ids.*' => ['integer'],
        ]);
    }

    /** @param  list<int>  $pageIds */
    private function syncPages(VisualTrainingItem $item, array $pageIds): void
    {
        if ($item->applies_all_pages) {
            $item->pages()->sync([]);

            return;
        }
        $ownIds = ChannelAccount::query()
            ->where('tenant_id', $item->tenant_id)
            ->whereIn('id', array_map('intval', $pageIds))
            ->pluck('id');

        $item->pages()->sync(
            $ownIds->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $item->tenant_id]])->all()
        );
    }

    /** @return array<string,mixed> */
    private function row(VisualTrainingItem $item, bool $withImages = false): array
    {
        $data = [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'attributes' => (array) $item->attributes,
            'ref_code' => $item->ref_code,
            'status' => $item->status,
            'applies_all_pages' => (bool) $item->applies_all_pages,
            'primary_image_id' => $item->primary_image_id,
            'image_count' => (int) ($item->images_count ?? $item->images()->count()),
            'created_at' => $item->created_at?->toIso8601String(),
        ];
        if ($withImages) {
            $data['channel_account_ids'] = $item->pages()->pluck('channel_accounts.id')->all();
            $data['images'] = VisualTrainingImage::query()
                ->where('item_id', $item->id)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (VisualTrainingImage $im) => [
                    'id' => $im->id,
                    'width' => $im->width,
                    'height' => $im->height,
                    'mime_type' => $im->mime_type,
                    'is_primary' => (int) $item->primary_image_id === (int) $im->id,
                ])->all();
        }

        return $data;
    }
}
