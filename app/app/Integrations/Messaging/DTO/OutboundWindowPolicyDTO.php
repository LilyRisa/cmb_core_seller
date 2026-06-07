<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

use CMBcoreSeller\Integrations\Messaging\Contracts\UtilityTemplateConnector;

/**
 * Outbound window rule của 1 provider — `OutboundWindowGuard` check trước
 * khi cho phép gửi tin.
 *
 * - Facebook Page Messenger: 24h kể từ last inbound (text tự do `RESPONSE`).
 *   Sau khi Meta KHAI TỬ message tag (POST_PURCHASE_UPDATE/CONFIRMED_EVENT_UPDATE/
 *   ACCOUNT_UPDATE — error_subcode 1893061), tag DUY NHẤT còn sống là `HUMAN_AGENT`
 *   (tin nhân viên người thật, tới `humanAgentWindowHours=168` = 7 ngày). NGOÀI cửa
 *   sổ đó chỉ gửi được **utility template đã duyệt** (`templateOnlyOutsideWindow=true`,
 *   xem {@see UtilityTemplateConnector}).
 *   `freeWindowHours=24`, `requiresTag=true`, `allowedTags=['HUMAN_AGENT']`.
 * - Shopee/TikTok/Lazada: không có hard window 24h. `freeWindowHours=null`,
 *   `requiresTag=false`. (Vẫn có rate-limit per shop ở `MessageSendService`.)
 *
 * Tag (`HUMAN_AGENT`) là cờ Messaging core passthrough qua `opts.message_tag` khi
 * gọi `sendText/sendMedia` — connector tự đính kèm vào API call.
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
        /**
         * Số giờ cửa sổ mở rộng cho tin nhân viên người thật (Facebook Human Agent =
         * 168h/7 ngày). NULL = không có cửa sổ mở rộng. Chỉ áp khi tin gắn HUMAN_AGENT.
         */
        public ?int $humanAgentWindowHours = null,
        /**
         * True ⇒ ngoài tất cả cửa sổ trên, KHÔNG cho text tự do, chỉ utility template
         * đã duyệt (Facebook sau khai tử tag). Core/FE dùng để khóa ô soạn & ép template.
         */
        public bool $templateOnlyOutsideWindow = false,
    ) {}
}
