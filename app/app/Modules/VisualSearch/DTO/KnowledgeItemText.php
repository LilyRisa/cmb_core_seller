<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/** Text nguồn của 1 mục tri thức để Messaging index (cross-module DTO). */
final class KnowledgeItemText
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $tenantId,
        public readonly string $text,
    ) {}
}
