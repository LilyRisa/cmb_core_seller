<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Định nghĩa 1 mẫu tin tiện ích (UTILITY) để SUBMIT lên provider duyệt. Chuẩn hoá
 * khỏi model `utility_templates` của module — connector tự map sang wire format
 * của sàn (vd Facebook Messenger `POST /{page_id}/message_templates`).
 *
 * Biến nội dung dùng cú pháp `{{1}}`, `{{2}}`… theo thứ tự; `examples` cung cấp
 * giá trị mẫu cho từng placeholder (Meta yêu cầu để duyệt).
 *
 * GOLDEN RULE: chỉ Messaging core dựng DTO này; connector KHÔNG biết model nào.
 */
final readonly class UtilityTemplateDTO
{
    /**
     * @param  list<array<string, mixed>>  $buttons  mỗi nút: {type, title, url?, payload?}
     * @param  list<string>  $examples  giá trị mẫu cho {{1}},{{2}}… (đúng thứ tự)
     */
    public function __construct(
        public string $name,
        public string $language,
        public string $body,
        public array $buttons = [],
        public array $examples = [],
    ) {}
}
