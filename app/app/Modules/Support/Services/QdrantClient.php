<?php

namespace CMBcoreSeller\Modules\Support\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter HTTP tối giản cho Qdrant (không SDK) — chỉ những gì help-bot cần:
 * tạo collection, upsert điểm, tìm theo vector.
 *
 * Triết lý: KHÔNG bao giờ ném ra ngoài service trợ lý. `enabled()` = false khi chưa
 * cấu hình QDRANT_URL ⇒ caller dùng fallback keyword. Lỗi mạng ⇒ log + trả false/[]
 * để trợ lý suy biến mượt thay vì 500.
 */
class QdrantClient
{
    private string $url;

    private string $apiKey;

    private string $collection;

    private int $timeout;

    public function __construct(?array $config = null)
    {
        $config ??= (array) config('support.qdrant', []);
        $this->url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->apiKey = (string) ($config['api_key'] ?? '');
        $this->collection = (string) ($config['collection'] ?? 'omnisell_help');
        $this->timeout = (int) ($config['timeout'] ?? 10);
    }

    /** Đã cấu hình URL ⇒ có thể dùng vector search. */
    public function enabled(): bool
    {
        return $this->url !== '';
    }

    public function collection(): string
    {
        return $this->collection;
    }

    /** Collection đã tồn tại chưa (GET trả 200). */
    public function collectionExists(): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        try {
            return $this->http()->get($this->endpoint("/collections/{$this->collection}"))->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Đảm bảo collection tồn tại (idempotent). Đã có ⇒ true (KHÔNG PUT lại — tránh
     * 4xx tuỳ phiên bản Qdrant). Chưa có ⇒ PUT tạo mới.
     */
    public function ensureCollection(int $dimension): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        if ($this->collectionExists()) {
            return true;
        }

        return $this->createCollection($dimension);
    }

    /**
     * Tạo lại từ đầu (cho `--fresh`): DROP collection cũ rồi tạo mới — đảm bảo không còn
     * point id mồ côi sau khi help_chunks bị xoá & cấp id mới.
     */
    public function recreateCollection(int $dimension): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        try {
            $this->http()->delete($this->endpoint("/collections/{$this->collection}"));
        } catch (\Throwable $e) {
            Log::warning('support.qdrant.delete_collection_failed', ['error' => $e->getMessage()]);
        }

        return $this->createCollection($dimension);
    }

    private function createCollection(int $dimension): bool
    {
        try {
            $res = $this->http()->put($this->endpoint("/collections/{$this->collection}"), [
                'vectors' => ['size' => $dimension, 'distance' => 'Cosine'],
            ]);

            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('support.qdrant.create_collection_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Upsert danh sách điểm. Mỗi điểm: ['id'=>int, 'vector'=>list<float>, 'payload'=>array].
     *
     * @param  list<array{id:int, vector:list<float>, payload:array<string,mixed>}>  $points
     */
    public function upsert(array $points): bool
    {
        if (! $this->enabled() || $points === []) {
            return false;
        }

        try {
            $res = $this->http()->put($this->endpoint("/collections/{$this->collection}/points?wait=true"), [
                'points' => $points,
            ]);

            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('support.qdrant.upsert_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Tìm top-K theo vector. Trả list ['id'=>int, 'score'=>float, 'payload'=>array].
     * Lỗi / chưa bật ⇒ [] (caller fallback keyword).
     *
     * @return list<array{id:int, score:float, payload:array<string,mixed>}>
     */
    public function search(array $vector, int $topK = 5): array
    {
        if (! $this->enabled() || $vector === []) {
            return [];
        }

        try {
            $res = $this->http()->post($this->endpoint("/collections/{$this->collection}/points/search"), [
                'vector' => $vector,
                'limit' => $topK,
                'with_payload' => true,
            ]);

            if (! $res->successful()) {
                return [];
            }

            return collect((array) $res->json('result', []))
                ->map(fn ($r) => [
                    'id' => (int) ($r['id'] ?? 0),
                    'score' => (float) ($r['score'] ?? 0),
                    'payload' => (array) ($r['payload'] ?? []),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('support.qdrant.search_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function http(): PendingRequest
    {
        $req = Http::timeout($this->timeout)->acceptJson();
        if ($this->apiKey !== '') {
            $req = $req->withHeaders(['api-key' => $this->apiKey]);
        }

        return $req;
    }

    private function endpoint(string $path): string
    {
        return $this->url.$path;
    }
}
