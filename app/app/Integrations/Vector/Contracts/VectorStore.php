<?php

namespace CMBcoreSeller\Integrations\Vector\Contracts;

/**
 * Kho vector vendor-agnostic (Qdrant hôm nay; có thể thêm Milvus/PGVector sau =
 * 1 connector mới). Triết lý: KHÔNG ném — lỗi/chưa cấu hình ⇒ log + trả false/[]
 * để caller (VisualSearch) suy biến (not_found) thay vì 500.
 */
interface VectorStore
{
    public function enabled(): bool;

    public function ensureCollection(string $collection, int $dim, string $distance = 'Cosine'): bool;

    public function recreateCollection(string $collection, int $dim, string $distance = 'Cosine'): bool;

    /**
     * Point id: Qdrant chỉ chấp nhận unsigned integer HOẶC UUID (chuỗi). KHÔNG chấp nhận chuỗi số bất kỳ.
     *
     * @param  list<array{id:int|string, vector:list<float>, payload:array<string,mixed>}>  $points
     */
    public function upsert(string $collection, array $points): bool;

    /**
     * @param  list<float>  $vector
     * @param  array<string,mixed>  $filter  Map khoá=giá trị (equality). VD ['tenant_id'=>5].
     * @return list<array{id:string, score:float, payload:array<string,mixed>}>
     */
    public function search(string $collection, array $vector, int $topK, array $filter = []): array;

    /** @param  list<int|string>  $ids */
    public function deleteIds(string $collection, array $ids): bool;
}
