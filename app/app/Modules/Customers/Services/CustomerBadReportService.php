<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Contracts\BadReportData;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerBadReportProvider;
use CMBcoreSeller\Modules\Customers\Models\CustomerBadReport;

/**
 * Lấy báo cáo "bom hàng" cho một số điện thoại lúc tạo đơn thủ công (SPEC 0038):
 * đọc/ghi cache `customer_bad_reports` (theo phone_hash), gọi
 * {@see CustomerBadReportProvider} (Pancake) khi cache thiếu/cũ.
 *
 * Chạy được CẢ khi customer chưa tồn tại (đơn thủ công khách mới) vì cache tách
 * khỏi bảng `customers`. Provider lỗi (null) ⇒ giữ bản cache cũ, không ghi rỗng.
 */
class CustomerBadReportService
{
    public function __construct(private readonly CustomerBadReportProvider $provider) {}

    /**
     * @param  string  $phoneHash  CustomerPhoneNormalizer::normalizeAndHash()
     * @param  string  $phone  số đã chuẩn hoá (truyền cho provider)
     * @return BadReportData|null null nếu không có gì đáng hiển thị
     */
    public function fetch(string $phoneHash, string $phone): ?BadReportData
    {
        $ttl = (int) config('integrations.pancake.cache_ttl_minutes', 1440);

        $row = CustomerBadReport::query()->where('phone_hash', $phoneHash)->first();
        if ($row !== null && $row->isFresh($ttl)) {
            return $this->fromRow($row);
        }

        $fresh = $this->provider->lookup($phone);
        if ($fresh === null) {
            // Lỗi tạm / tắt cấu hình — giữ bản cũ nếu có (dù đã quá hạn), không ghi đè bằng rỗng.
            return $row !== null ? $this->fromRow($row) : null;
        }

        $this->store($phoneHash, $fresh);

        return $fresh->hasData() ? $fresh : null;
    }

    private function store(string $phoneHash, BadReportData $data): void
    {
        CustomerBadReport::query()->updateOrCreate(
            ['phone_hash' => $phoneHash],
            [
                'order_fail' => $data->orderFail,
                'order_success' => $data->orderSuccess,
                'warning_count' => $data->warningCount,
                'warnings' => $data->warnings,
                'has_data' => $data->hasData(),
                'synced_at' => now(),
            ],
        );
    }

    private function fromRow(CustomerBadReport $row): ?BadReportData
    {
        $data = new BadReportData(
            orderFail: (int) $row->order_fail,
            orderSuccess: (int) $row->order_success,
            warningCount: (int) $row->warning_count,
            warnings: array_values((array) ($row->warnings ?? [])),
            matchedPhone: '',
            matched: true,
        );

        return $data->hasData() ? $data : null;
    }
}
