<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeDoc;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * CRUD tài liệu AI training (RAG). SPEC-0024 §6.1.
 *
 * Đọc cần `messaging.view`; upload/xoá cần `messaging.ai.train`. Sau khi tạo →
 * dispatch `IndexKnowledgeDoc` (chunk + index nền). Xoá → cascade chunks.
 */
class KnowledgeController extends Controller
{
    public function __construct(private MediaStorage $storage) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('messaging.view');

        return JsonResource::collection(
            AiKnowledgeDocument::query()
                ->orderByDesc('created_at')
                // Lọc theo nền tảng (?provider=zalo_oa) — mỗi nền tảng có kho tài liệu riêng.
                ->when($request->query('provider'), fn ($q, $p) => $q->where('provider', (string) $p))
                ->paginate(min(100, max(1, (int) $request->query('per_page', 30))))
                ->through(fn (AiKnowledgeDocument $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'source' => $d->source,
                    'status' => $d->status,
                    'chunk_count' => (int) $d->chunk_count,
                    'provider' => $d->provider,
                    'applies_all_pages' => (bool) $d->applies_all_pages,
                    'channel_account_ids' => $d->pages()->pluck('channel_accounts.id')->all(),
                    'indexed_at' => $d->indexed_at?->toIso8601String(),
                    'error' => $d->error,
                    'created_at' => $d->created_at?->toIso8601String(),
                ])
        );
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'source' => ['required', 'in:inline,url,upload'],
            'inline_text' => ['required_if:source,inline', 'nullable', 'string', 'max:100000'],
            'url' => ['required_if:source,url', 'nullable', 'url', 'max:1024'],
            // 25MB; định dạng trích được text (xem DocumentTextExtractor).
            'file' => ['required_if:source,upload', 'nullable', 'file', 'max:25600',
                'extensions:txt,md,csv,tsv,docx,xlsx,pdf'],
            // Nền tảng tài liệu (mặc định facebook_page) — tách kho theo Facebook/Zalo OA.
            'provider' => ['nullable', 'string', 'max:32'],
            // SPEC 0035 — phạm vi page: áp mọi trang HOẶC gán danh sách page (nhiều page).
            'applies_all_pages' => ['nullable', 'boolean'],
            'channel_account_ids' => ['nullable', 'array'],
            'channel_account_ids.*' => ['integer'],
        ]);

        $tenantId = app(CurrentTenant::class)->id();

        $storagePath = null;
        if ($data['source'] === AiKnowledgeDocument::SOURCE_UPLOAD && $request->hasFile('file')) {
            $file = $request->file('file');
            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $storagePath = "tenants/{$tenantId}/messaging/knowledge/".Str::uuid().'.'.$ext;
            $stream = fopen($file->getRealPath(), 'rb');
            $this->storage->disk()->writeStream($storagePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $doc = AiKnowledgeDocument::create([
            'tenant_id' => $tenantId,
            'title' => $data['title'],
            'source' => $data['source'],
            'inline_text' => $data['inline_text'] ?? null,
            'url' => $data['url'] ?? null,
            'storage_path' => $storagePath,
            'chunk_count' => 0,
            'status' => AiKnowledgeDocument::STATUS_PENDING,
            'applies_all_pages' => (bool) ($data['applies_all_pages'] ?? false),
            'provider' => $data['provider'] ?? 'facebook_page',
            'created_by' => $request->user()->id,
        ]);
        $this->syncPages($doc, $data['channel_account_ids'] ?? []);

        IndexKnowledgeDoc::dispatch($doc->id);

        AuditLog::record('messaging.knowledge.upload', $doc, ['title' => $doc->title, 'source' => $doc->source]);

        return response()->json(['data' => ['id' => $doc->id, 'status' => $doc->status]], 201);
    }

    /**
     * Đồng bộ pivot document↔page (lọc page thuộc tenant). `applies_all_pages=true` ⇒ xoá pivot.
     *
     * @param  list<int>  $pageIds
     */
    private function syncPages(AiKnowledgeDocument $doc, array $pageIds): void
    {
        if ($doc->applies_all_pages) {
            $doc->pages()->sync([]);

            return;
        }

        $ownIds = ChannelAccount::query()
            ->where('tenant_id', $doc->tenant_id)
            ->whereIn('id', array_map('intval', $pageIds))
            ->pluck('id');

        $doc->pages()->sync(
            $ownIds->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $doc->tenant_id]])->all()
        );
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $doc = AiKnowledgeDocument::query()->findOrFail($id);
        AiKnowledgeChunk::query()->where('document_id', $doc->id)->delete();
        $doc->delete(); // soft delete document

        AuditLog::record('messaging.knowledge.delete', $doc, ['title' => $doc->title]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /**
     * Tải lại (re-index) tài liệu — fetch lại nguồn url/Google Sheet/file và chunk
     * lại. Inline cũng index lại từ text hiện tại. Dùng khi nguồn có dữ liệu mới.
     */
    public function reindex(int $id): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $doc = AiKnowledgeDocument::query()->findOrFail($id);
        $doc->update(['status' => AiKnowledgeDocument::STATUS_PENDING, 'error' => null]);

        IndexKnowledgeDoc::dispatch($doc->id);

        AuditLog::record('messaging.knowledge.reindex', $doc, ['title' => $doc->title, 'source' => $doc->source]);

        return response()->json(['data' => ['id' => $doc->id, 'status' => $doc->status]]);
    }

    /**
     * Xem nội dung ĐÃ TRÍCH (chunk) của tài liệu — để người dùng kiểm tra dữ liệu
     * AI thực sự lấy được từ file/URL/Sheet. Trả meta + danh sách chunk theo thứ tự.
     */
    public function chunks(int $id): JsonResponse
    {
        Gate::authorize('messaging.view');

        $doc = AiKnowledgeDocument::query()->findOrFail($id);
        $chunks = AiKnowledgeChunk::query()
            ->where('document_id', $doc->id)
            ->orderBy('chunk_index')
            ->get(['chunk_index', 'chunk_text', 'token_count']);

        return response()->json(['data' => [
            'id' => $doc->id,
            'title' => $doc->title,
            'source' => $doc->source,
            'url' => $doc->url,
            'status' => $doc->status,
            'error' => $doc->error,
            'chunk_count' => (int) $doc->chunk_count,
            'indexed_at' => $doc->indexed_at?->toIso8601String(),
            'chunks' => $chunks->map(fn (AiKnowledgeChunk $c) => [
                'index' => (int) $c->chunk_index,
                'text' => (string) $c->chunk_text,
                'token_count' => (int) $c->token_count,
            ])->all(),
        ]]);
    }
}
