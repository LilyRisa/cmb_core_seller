<?php

namespace CMBcoreSeller\Modules\Customers\Contracts;

/**
 * Nguồn báo cáo "bom hàng" bên ngoài cho một số điện thoại (SPEC 0038).
 * Implement bởi tầng Integrations (Pancake). Customers module phụ thuộc vào
 * interface này, không vào connector cụ thể.
 */
interface CustomerBadReportProvider
{
    /**
     * Tra cứu báo cáo cho `$phone` (số đã chuẩn hoá, vd `0395151515`).
     *
     * @return BadReportData|null `null` khi tắt cấu hình / thiếu credential /
     *                            lỗi HTTP / timeout. Gọi thành công nhưng số
     *                            không có dữ liệu xấu ⇒ trả {@see BadReportData::clean()}
     *                            (matched=true), KHÔNG phải null.
     */
    public function lookup(string $phone): ?BadReportData;
}
