<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Contracts\BadReportData;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerBadReportProvider;
use CMBcoreSeller\Modules\Customers\Models\CustomerBadReport;

/**
 * Cache "bom hàng" Pancake (SPEC 0038). v2: **gọi Pancake 1 lần** — chỉ khi chưa
 * có dòng cache cho số đó; đã có ⇒ dùng cache, không gọi lại (bỏ TTL refetch).
 * Lỗi tạm (provider trả null) ⇒ KHÔNG ghi cache, lần sau thử lại.
 *
 * Chạy được CẢ khi customer chưa tồn tại (cache tách khỏi `customers`).
 */
class CustomerBadReportService
{
    public function __construct(private readonly CustomerBadReportProvider $provider) {}

    /**
     * @param  string  $phoneHash  CustomerPhoneNormalizer::normalizeAndHash()
     * @param  string  $phone  số đã chuẩn hoá (truyền cho provider)
     */
    public function fetchOnce(string $phoneHash, string $phone): ?BadReportData
    {
        $row = CustomerBadReport::query()->where('phone_hash', $phoneHash)->first();
        if ($row !== null) {
            return $this->fromRow($row);
        }

        $fresh = $this->provider->lookup($phone);
        if ($fresh === null) {
            return null; // tắt/lỗi — không ghi, lần sau thử lại
        }

        $this->store($phoneHash, $fresh);

        return $fresh;
    }

    /** Baseline Pancake đã nạp (nếu có) — KHÔNG gọi API. Dùng để cộng dồn với nội bộ. */
    public function cached(string $phoneHash): ?BadReportData
    {
        $row = CustomerBadReport::query()->where('phone_hash', $phoneHash)->first();

        return $row !== null ? $this->fromRow($row) : null;
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

    private function fromRow(CustomerBadReport $row): BadReportData
    {
        return new BadReportData(
            orderFail: (int) $row->order_fail,
            orderSuccess: (int) $row->order_success,
            warningCount: (int) $row->warning_count,
            warnings: array_values((array) ($row->warnings ?? [])),
            matchedPhone: '',
            matched: true,
        );
    }
}
