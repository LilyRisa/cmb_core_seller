<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Knowledge base chunks đã retrieve (top-K) cho RAG. `KnowledgeIndexer` ở
 * Messaging core handle embedding + retrieval; connector chỉ nhận chunks
 * dạng text và stitch vào prompt.
 */
final readonly class KnowledgeBase
{
    public function __construct(
        /** @var list<array{document_id:int, title:string, chunk_text:string, score?:float}> */
        public array $chunks = [],
    ) {}
}
