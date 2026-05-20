<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Outbound window rule của 1 provider — `OutboundWindowGuard` check trước
 * khi cho phép gửi tin.
 *
 * - Facebook Page Messenger: 24h kể từ last inbound; quá window chỉ gửi
 *   được tin có `message_tag` (CONFIRMED_EVENT_UPDATE / POST_PURCHASE_UPDATE /
 *   ACCOUNT_UPDATE). `freeWindowHours=24`, `requiresTag=true`, `allowedTags=[...]`.
 * - Shopee/TikTok/Lazada: không có hard window 24h. `freeWindowHours=null`,
 *   `requiresTag=false`. (Vẫn có rate-limit per shop ở `MessageSendService`.)
 *
 * Tag (vd `CONFIRMED_EVENT_UPDATE`) là cờ Messaging core passthrough qua `opts`
 * khi gọi `sendText/sendMedia/sendTemplate` — connector tự đính kèm vào API call.
 */
final readonly class OutboundWindowPolicyDTO
{
    public function __construct(
        /** Số giờ window tự do tính từ last inbound. NULL = không có window cứng. */
        public ?int $freeWindowHours = null,
        /** Hết window ⇒ bắt buộc gửi với 1 trong $allowedTags. */
        public bool $requiresTag = false,
        /** @var list<string> */
        public array $allowedTags = [],
    ) {}
}
