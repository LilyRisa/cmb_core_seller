<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;

/**
 * Năng lực RIÊNG: gửi tin tương tác (nút bấm / carousel). Tách KHỎI
 * {@see MessagingConnector} có chủ đích — v1 chỉ Facebook Page hỗ trợ; các sàn
 * (TikTok/Shopee/Lazada) KHÔNG cần biết tới năng lực này và KHÔNG bị buộc
 * implement (Interface Segregation).
 *
 * GOLDEN RULE (extensibility-rules.md): core KHÔNG bao giờ `instanceof
 * FacebookPageConnector` — chỉ kiểm `instanceof InteractiveMessagingConnector`
 * (tên NĂNG LỰC, không phải tên sàn) + `supports('outbound.interactive')`. Sàn nào
 * có nút bấm sau này chỉ cần implement interface này + bật capability — không sửa core.
 */
interface InteractiveMessagingConnector
{
    /**
     * Gửi tin tương tác. `$structure` chuẩn hoá, connector tự map sang wire format
     * của sàn (vd Facebook button/generic template):
     *   `{ text, buttons:[ { type:'postback'|'url', title, payload?, url? } ] }`.
     *
     * @param  array{text?:string, buttons?:list<array<string,mixed>>}  $structure
     * @param  array<string, mixed>  $opts
     */
    public function sendInteractive(MessagingAuthContext $auth, string $externalConversationId, array $structure, array $opts = []): SendResultDTO;
}
