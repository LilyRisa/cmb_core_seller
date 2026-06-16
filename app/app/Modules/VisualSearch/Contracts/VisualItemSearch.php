<?php

namespace CMBcoreSeller\Modules\VisualSearch\Contracts;

use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;

/**
 * Cổng tiêu thụ visual search cho module khác (Messaging) + API seller.
 * Triết lý: KHÔNG ném — tắt/lỗi/không khớp ⇒ VisualMatchResult::notFound().
 */
interface VisualItemSearch
{
    public function lookup(int $tenantId, VisualImageInput $image, VisualLookupOptions $opts): VisualMatchResult;
}
