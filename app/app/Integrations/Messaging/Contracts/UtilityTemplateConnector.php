<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateStatusDTO;

/**
 * Năng lực RIÊNG: gửi "tin nhắn tiện ích" (Utility Messages) qua TEMPLATE được
 * provider duyệt trước — cách hợp lệ DUY NHẤT để gửi tin giao dịch tự động ra
 * NGOÀI cửa sổ 24h sau khi Meta khai tử message tag (POST_PURCHASE_UPDATE…).
 *
 * Tách KHỎI {@see MessagingConnector} có chủ đích (Interface Segregation): chỉ
 * Facebook Page hỗ trợ; các sàn (TikTok/Shopee/Lazada) KHÔNG có khái niệm này và
 * KHÔNG bị buộc implement.
 *
 * GOLDEN RULE (extensibility-rules.md): core KHÔNG `instanceof FacebookPageConnector`
 * — chỉ kiểm `instanceof UtilityTemplateConnector` + `supports('outbound.utility_template')`.
 *
 * Quy trình: {@see createUtilityTemplate} (submit) → {@see syncUtilityTemplateStatus}
 * (chờ duyệt) → {@see sendUtilityTemplate} (gửi khi APPROVED).
 */
interface UtilityTemplateConnector
{
    /**
     * Tạo + submit 1 utility template lên provider để duyệt. Trả ref (external id +
     * trạng thái khởi tạo, thường PENDING). Lỗi quyền/định dạng ⇒ ném RuntimeException.
     */
    public function createUtilityTemplate(MessagingAuthContext $auth, UtilityTemplateDTO $template): UtilityTemplateRefDTO;

    /** Poll trạng thái duyệt của 1 template đã submit (theo external id). */
    public function syncUtilityTemplateStatus(MessagingAuthContext $auth, string $externalTemplateId): UtilityTemplateStatusDTO;

    /**
     * Gửi 1 utility template ĐÃ DUYỆT tới hội thoại (PSID). `$vars` = giá trị thay
     * `{{1}},{{2}}…` đúng thứ tự. Gửi được NGOÀI cửa sổ 24h.
     *
     * @param  list<string>  $vars
     * @param  array<string, mixed>  $opts
     */
    public function sendUtilityTemplate(MessagingAuthContext $auth, string $externalConversationId, UtilityTemplateRefDTO $template, array $vars = [], array $opts = []): SendResultDTO;
}
