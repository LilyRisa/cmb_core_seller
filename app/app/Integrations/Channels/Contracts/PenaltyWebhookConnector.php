<?php

namespace CMBcoreSeller\Integrations\Channels\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\PenaltyEventDTO;

/**
 * Năng lực bóc sự kiện điểm phạt/vi phạm từ webhook sàn (segregated capability —
 * giống {@see ShopReportConnector}). Core kiểm `instanceof` TRƯỚC khi gọi; sàn không
 * gửi webhook điểm phạt thì không implement.
 */
interface PenaltyWebhookConnector
{
    /**
     * Bóc 1 push thô (đã verify) thành các sự kiện điểm phạt chuẩn hoá. Push không phải
     * loại điểm phạt → trả [].
     *
     * @param  array<string,mixed>  $rawPush  toàn bộ payload push (WebhookEvent::payload)
     * @return list<PenaltyEventDTO>
     */
    public function parsePenaltyWebhook(array $rawPush): array;
}
