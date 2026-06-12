<?php

namespace CMBcoreSeller\Modules\Channels\Events;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sàn báo có thay đổi sản phẩm (`product_update` webhook) — vd kết thúc xét duyệt
 * (QC) hoặc bị cấm bán. Module Products lắng nghe để re-check trạng thái QC của các
 * bản nháp đang chờ duyệt trên shop này (webhook chỉ là tín hiệu — poll lại là nguồn
 * sự thật, giống after-sales).
 */
class MarketplaceProductUpdated
{
    use Dispatchable;

    /**
     * @param  string[]  $externalIds  id sản phẩm sàn (nếu webhook có) — gợi ý, không bắt buộc.
     */
    public function __construct(
        public readonly ChannelAccount $channelAccount,
        public readonly array $externalIds = [],
    ) {}
}
