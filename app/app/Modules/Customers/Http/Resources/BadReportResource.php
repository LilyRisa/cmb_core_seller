<?php

namespace CMBcoreSeller\Modules\Customers\Http\Resources;

use CMBcoreSeller\Modules\Customers\Contracts\BadReportData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Báo cáo "bom hàng" Pancake cho FE cảnh báo khi tạo đơn thủ công (SPEC 0038).
 * Chỉ phơi số liệu + lý do/ngày báo cáo; KHÔNG có người báo cáo / page / id.
 *
 * @property-read BadReportData $resource
 */
class BadReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var BadReportData $d */
        $d = $this->resource;

        return [
            'order_fail' => $d->orderFail,
            'order_success' => $d->orderSuccess,
            'warning_count' => $d->warningCount,
            'warnings' => array_map(fn (array $w) => [
                'reason' => $w['reason'],
                'reported_at' => $w['reported_at'],
            ], $d->warnings),
            'has_data' => $d->hasData(),
        ];
    }
}
