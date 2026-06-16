<?php

namespace CMBcoreSeller\Integrations\Vector\Qdrant;

use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter HTTP tối giản cho Qdrant (không SDK). Tách RIÊNG khỏi
 * Support\QdrantClient (help-bot) để không đụng luồng tối ưu của Help Assistant
 * (ADR — chấp nhận trùng ~80 dòng đổi lấy tách biệt). Hỗ trợ nhiều collection +
 * filter equality (tenant isolation).
 */
class QdrantStore implements VectorStore
{
    private string $url;

    private string $apiKey;

    private int $timeout;

    /** @param  array{url?:string,api_key?:string,timeout?:int}|null  $config */
    public function __construct(?array $config = null)
    {
        $config ??= (array) config('integrations.vector.qdrant', []);
        $this->url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->apiKey = (string) ($config['api_key'] ?? '');
        $this->timeout = (int) ($config['timeout'] ?? 10);
    }

    public function enabled(): bool
    {
        return $this->url !== '';
    }

    public function ensureCollection(string $collection, int $dim, string $distance = 'Cosine'): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        try {
            if ($this->http()->get($this->ep("/collections/{$collection}"))->successful()) {
                return true;
            }
        } catch (\Throwable) {
            // fallthrough to create
        }

        return $this->create($collection, $dim, $distance);
    }

    public function recreateCollection(string $collection, int $dim, string $distance = 'Cosine'): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        try {
            $this->http()->delete($this->ep("/collections/{$collection}"));
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.delete_failed', ['error' => $e->getMessage()]);
        }

        return $this->create($collection, $dim, $distance);
    }

    public function upsert(string $collection, array $points): bool
    {
        if (! $this->enabled() || $points === []) {
            return false;
        }
        try {
            return $this->http()
                ->put($this->ep("/collections/{$collection}/points?wait=true"), ['points' => $points])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.upsert_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function search(string $collection, array $vector, int $topK, array $filter = []): array
    {
        if (! $this->enabled() || $vector === []) {
            return [];
        }
        $body = ['vector' => $vector, 'limit' => $topK, 'with_payload' => true];
        if ($filter !== []) {
            $body['filter'] = ['must' => array_map(
                fn ($k, $v) => ['key' => $k, 'match' => ['value' => $v]],
                array_keys($filter),
                array_values($filter),
            )];
        }
        try {
            $res = $this->http()->post($this->ep("/collections/{$collection}/points/search"), $body);
            if (! $res->successful()) {
                return [];
            }

            return collect((array) $res->json('result', []))
                ->map(fn ($r) => [
                    'id' => (string) ($r['id'] ?? ''),
                    'score' => (float) ($r['score'] ?? 0),
                    'payload' => (array) ($r['payload'] ?? []),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.search_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function deleteIds(string $collection, array $ids): bool
    {
        if (! $this->enabled() || $ids === []) {
            return false;
        }
        try {
            return $this->http()
                ->post($this->ep("/collections/{$collection}/points/delete?wait=true"), ['points' => $ids])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.delete_ids_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function create(string $collection, int $dim, string $distance): bool
    {
        try {
            return $this->http()
                ->put($this->ep("/collections/{$collection}"), ['vectors' => ['size' => $dim, 'distance' => $distance]])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('vector.qdrant.create_failed', ['error' => $e->getMessage()]);

            return false;
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

    private function ep(string $path): string
    {
        return $this->url.$path;
    }
}
