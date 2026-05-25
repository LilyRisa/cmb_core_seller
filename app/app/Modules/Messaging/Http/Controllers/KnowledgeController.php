<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
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
                ->paginate(min(100, max(1, (int) $request->query('per_page', 30))))
                ->through(fn (AiKnowledgeDocument $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'source' => $d->source,
                    'status' => $d->status,
                    'chunk_count' => (int) $d->chunk_count,
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
            'created_by' => $request->user()->id,
        ]);

        IndexKnowledgeDoc::dispatch($doc->id);

        AuditLog::record('messaging.knowledge.upload', $doc, ['title' => $doc->title, 'source' => $doc->source]);

        return response()->json(['data' => ['id' => $doc->id, 'status' => $doc->status]], 201);
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
}
