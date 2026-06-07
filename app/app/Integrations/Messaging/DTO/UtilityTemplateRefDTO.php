<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Tham chiếu tới 1 utility template ĐÃ SUBMIT/duyệt phía provider — đủ để gửi tin
 * (`sendUtilityTemplate`) hay re-sync trạng thái. Module lưu các trường này trên
 * `utility_templates` (external_template_id, name, language, status).
 */
final readonly class UtilityTemplateRefDTO
{
    public function __construct(
        public string $externalTemplateId,
        public string $name,
        public string $language,
        /** PENDING|APPROVED|REJECTED — {@see UtilityTemplateStatusDTO}. */
        public string $status = UtilityTemplateStatusDTO::PENDING,
    ) {}
}
