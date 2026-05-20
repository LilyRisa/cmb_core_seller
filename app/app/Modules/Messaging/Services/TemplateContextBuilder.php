<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;

/**
 * Dựng map context phẳng (dotted-key) cho {@see TemplateResolver} từ 1 conversation.
 *
 * Nguồn dữ liệu — chỉ qua seam liên-module hợp lệ (modules.md §3):
 *   - `customer.*` ← {@see CustomerProfileContract} (đọc registry khách, KHÔNG
 *                    import Customer model). Chỉ phone đã mask — không lộ full.
 *   - `buyer.*`    ← chính `conversations` (tên buyer trên sàn).
 *   - `shop.*`     ← `channel_accounts` (Messaging đã có quan hệ này).
 *   - `order.*`    ← do caller truyền vào `$overrides` (FE gửi kèm `vars`), hoặc
 *                    từ `conversation.meta['order']` nếu được denormalize sẵn.
 *                    (Chưa có Orders read-contract — enrich đầy đủ là việc sau.)
 *
 * `$overrides` (vars do người dùng nhập) luôn ĐÈ LÊN context tự dựng — cho phép
 * NV chỉnh tay giá trị trước khi gửi.
 */
class TemplateContextBuilder
{
    public function __construct(
        private CustomerProfileContract $customers,
    ) {}

    /**
     * @param  array<string,scalar|null>  $overrides  vars do người dùng nhập (dotted-key hoặc phẳng)
     * @return array<string,scalar|null>
     */
    public function forConversation(Conversation $conv, array $overrides = []): array
    {
        $ctx = [
            'buyer.name' => $conv->buyer_name,
            'shop.name' => $conv->channelAccount?->effectiveName(),
        ];

        if ($conv->customer_id) {
            $profile = $this->customers->findById((int) $conv->tenant_id, (int) $conv->customer_id);
            if ($profile) {
                $ctx['customer.name'] = $profile->name;
                $ctx['customer.phone'] = $profile->phoneMasked;
                $ctx['customer.reputation'] = $profile->reputationLabel;
            }
        }

        // order.* denormalized trong meta (best-effort cho tới khi có Orders read seam)
        $metaOrder = (array) ($conv->meta['order'] ?? []);
        foreach ($metaOrder as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $ctx['order.'.$k] = $v;
            }
        }

        // overrides đè cuối cùng
        foreach ($overrides as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $ctx[$k] = $v;
            }
        }

        return $ctx;
    }
}
