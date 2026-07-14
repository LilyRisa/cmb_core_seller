<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;

/**
 * Năng lực RIÊNG: báo cáo sự kiện chuyển đổi (Purchase) về Meta qua Conversions API for
 * Business Messaging — chỉ Facebook Page (Messenger) hỗ trợ hiện tại; các sàn khác
 * (Zalo OA/Lazada IM) KHÔNG có khái niệm này và KHÔNG bị buộc implement.
 *
 * GOLDEN RULE (extensibility-rules.md): core KHÔNG `instanceof FacebookPageConnector` —
 * chỉ kiểm `instanceof ConversionReportingConnector` + `supports('conversion.report')`.
 */
interface ConversionReportingConnector
{
    /**
     * Tạo (nếu chưa có) dataset gắn theo Page/tài khoản, trả dataset_id. Lỗi thiếu quyền
     * `page_events` ⇒ ném {@see \CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException}.
     */
    public function ensureDataset(MessagingAuthContext $auth): string;

    /**
     * Gửi 1 sự kiện Purchase. `$eventId` dùng để dedupe phía log/Meta (vd "order-{id}").
     * Lỗi thiếu quyền ⇒ ném MissingScopeException; lỗi khác ⇒ RuntimeException.
     */
    public function reportPurchase(
        MessagingAuthContext $auth,
        string $datasetId,
        string $psid,
        int $valueVnd,
        \DateTimeInterface $eventTime,
        string $eventId,
    ): void;
}
