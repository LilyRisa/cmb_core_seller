<?php

namespace CMBcoreSeller\Modules\Accounting\Database\Seeders;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Seed hệ thống tài khoản theo Thông tư 133/2016/TT-BTC (DN nhỏ & vừa). Phase 7.1 — SPEC 0019.
 *
 * KHÔNG kế thừa Illuminate\Database\Seeder vì cần `tenant_id` — gọi từ AccountingSetupService.
 *
 * Idempotent qua unique (tenant_id, code): chạy lại không nhân đôi, không ghi đè name nếu tenant
 * đã đổi (chỉ tạo TK còn thiếu).
 *
 * Bộ TK ~80 mục — đã rút gọn từ TT133 đầy đủ (lược TK ngoại tệ, TK nông-lâm-thuỷ-sản, TK xây lắp).
 * Tenant có thể tạo thêm TK con qua UI nếu cần.
 */
class ChartAccountsTT133Seeder
{
    public function run(int $tenantId): int
    {
        $created = 0;
        foreach (self::TEMPLATE as $row) {
            $exists = ChartAccount::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('code', $row['code'])->exists();
            if ($exists) {
                continue;
            }
            $parentId = null;
            if (! empty($row['parent_code'])) {
                $parentId = ChartAccount::query()
                    ->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)
                    ->where('code', $row['parent_code'])
                    ->value('id');
            }
            ChartAccount::query()->create([
                'tenant_id' => $tenantId,
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'parent_id' => $parentId,
                'normal_balance' => $row['nb'],
                'is_postable' => $row['postable'] ?? true,
                'is_active' => true,
                'vas_template' => 'tt133',
                'sort_order' => $row['sort'] ?? 0,
            ]);
            $created++;
        }

        return $created;
    }

    /** @return array<int, array{code:string,name:string,type:string,nb:string,parent_code?:string,postable?:bool,sort?:int}> */
    private const TEMPLATE = [
        // === LOẠI 1 — TÀI SẢN NGẮN HẠN ===
        ['code' => '111', 'name' => 'Tiền mặt', 'type' => 'asset', 'nb' => 'debit', 'postable' => false, 'sort' => 1110],
        ['code' => '1111', 'name' => 'Tiền Việt Nam', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '111', 'sort' => 1111],
        ['code' => '1112', 'name' => 'Ngoại tệ', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '111', 'sort' => 1112],
        ['code' => '112', 'name' => 'Tiền gửi ngân hàng', 'type' => 'asset', 'nb' => 'debit', 'postable' => false, 'sort' => 1120],
        ['code' => '1121', 'name' => 'Tiền gửi NH (VND)', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '112', 'sort' => 1121],
        ['code' => '1122', 'name' => 'Tiền gửi NH (Ngoại tệ)', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '112', 'sort' => 1122],
        ['code' => '113', 'name' => 'Tiền đang chuyển', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1130],

        ['code' => '121', 'name' => 'Chứng khoán kinh doanh', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1210],
        ['code' => '128', 'name' => 'Đầu tư nắm giữ đến ngày đáo hạn', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1280],

        ['code' => '131', 'name' => 'Phải thu của khách hàng', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1310],
        ['code' => '133', 'name' => 'Thuế GTGT được khấu trừ', 'type' => 'asset', 'nb' => 'debit', 'postable' => false, 'sort' => 1330],
        ['code' => '1331', 'name' => 'Thuế GTGT đầu vào của HHDV', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '133', 'sort' => 1331],
        ['code' => '1332', 'name' => 'Thuế GTGT đầu vào của TSCĐ', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '133', 'sort' => 1332],
        ['code' => '136', 'name' => 'Phải thu nội bộ', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1360],
        ['code' => '138', 'name' => 'Phải thu khác', 'type' => 'asset', 'nb' => 'debit', 'postable' => false, 'sort' => 1380],
        ['code' => '1381', 'name' => 'Tài sản thiếu chờ xử lý', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '138', 'sort' => 1381],
        ['code' => '1388', 'name' => 'Phải thu khác', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '138', 'sort' => 1388],

        ['code' => '141', 'name' => 'Tạm ứng', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1410],

        ['code' => '151', 'name' => 'Hàng mua đang đi đường', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1510],
        ['code' => '152', 'name' => 'Nguyên liệu, vật liệu', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1520],
        ['code' => '153', 'name' => 'Công cụ, dụng cụ', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1530],
        ['code' => '154', 'name' => 'Chi phí sản xuất, kinh doanh dở dang', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1540],
        ['code' => '155', 'name' => 'Thành phẩm', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1550],
        ['code' => '156', 'name' => 'Hàng hoá', 'type' => 'asset', 'nb' => 'debit', 'postable' => false, 'sort' => 1560],
        ['code' => '1561', 'name' => 'Giá mua hàng hoá', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '156', 'sort' => 1561],
        ['code' => '1562', 'name' => 'Chi phí mua hàng hoá', 'type' => 'asset', 'nb' => 'debit', 'parent_code' => '156', 'sort' => 1562],
        ['code' => '157', 'name' => 'Hàng gửi đi bán', 'type' => 'asset', 'nb' => 'debit', 'sort' => 1570],

        // === LOẠI 2 — TÀI SẢN DÀI HẠN ===
        ['code' => '211', 'name' => 'Tài sản cố định', 'type' => 'asset', 'nb' => 'debit', 'sort' => 2110],
        ['code' => '213', 'name' => 'TSCĐ vô hình', 'type' => 'asset', 'nb' => 'debit', 'sort' => 2130],
        ['code' => '214', 'name' => 'Hao mòn TSCĐ', 'type' => 'contra_asset', 'nb' => 'credit', 'sort' => 2140],
        ['code' => '217', 'name' => 'Bất động sản đầu tư', 'type' => 'asset', 'nb' => 'debit', 'sort' => 2170],
        ['code' => '228', 'name' => 'Đầu tư góp vốn vào đơn vị khác', 'type' => 'asset', 'nb' => 'debit', 'sort' => 2280],
        ['code' => '229', 'name' => 'Dự phòng tổn thất tài sản', 'type' => 'contra_asset', 'nb' => 'credit', 'sort' => 2290],
        ['code' => '241', 'name' => 'XDCB dở dang', 'type' => 'asset', 'nb' => 'debit', 'sort' => 2410],
        ['code' => '242', 'name' => 'Chi phí trả trước', 'type' => 'asset', 'nb' => 'debit', 'sort' => 2420],

        // === LOẠI 3 — NỢ PHẢI TRẢ ===
        ['code' => '331', 'name' => 'Phải trả cho người bán', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3310],
        ['code' => '333', 'name' => 'Thuế và các khoản phải nộp Nhà nước', 'type' => 'liability', 'nb' => 'credit', 'postable' => false, 'sort' => 3330],
        ['code' => '3331', 'name' => 'Thuế GTGT phải nộp', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'postable' => false, 'sort' => 3331],
        ['code' => '33311', 'name' => 'Thuế GTGT đầu ra', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '3331', 'sort' => 33311],
        ['code' => '33312', 'name' => 'Thuế GTGT hàng nhập khẩu', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '3331', 'sort' => 33312],
        ['code' => '3332', 'name' => 'Thuế tiêu thụ đặc biệt', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'sort' => 3332],
        ['code' => '3333', 'name' => 'Thuế xuất, nhập khẩu', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'sort' => 3333],
        ['code' => '3334', 'name' => 'Thuế TNDN', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'sort' => 3334],
        ['code' => '3335', 'name' => 'Thuế TNCN', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'sort' => 3335],
        ['code' => '3338', 'name' => 'Các loại thuế khác', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'sort' => 3338],
        ['code' => '3339', 'name' => 'Phí, lệ phí và các khoản phải nộp khác', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '333', 'sort' => 3339],
        ['code' => '334', 'name' => 'Phải trả người lao động', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3340],
        ['code' => '335', 'name' => 'Chi phí phải trả', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3350],
        ['code' => '336', 'name' => 'Phải trả nội bộ', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3360],
        ['code' => '338', 'name' => 'Phải trả, phải nộp khác', 'type' => 'liability', 'nb' => 'credit', 'postable' => false, 'sort' => 3380],
        ['code' => '3381', 'name' => 'Tài sản thừa chờ giải quyết', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '338', 'sort' => 3381],
        ['code' => '3382', 'name' => 'Kinh phí công đoàn', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '338', 'sort' => 3382],
        ['code' => '3383', 'name' => 'BHXH', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '338', 'sort' => 3383],
        ['code' => '3384', 'name' => 'BHYT', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '338', 'sort' => 3384],
        ['code' => '3385', 'name' => 'BHTN', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '338', 'sort' => 3385],
        ['code' => '3388', 'name' => 'Phải trả khác', 'type' => 'liability', 'nb' => 'credit', 'parent_code' => '338', 'sort' => 3388],
        ['code' => '341', 'name' => 'Vay và nợ thuê tài chính', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3410],
        ['code' => '352', 'name' => 'Dự phòng phải trả', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3520],
        ['code' => '353', 'name' => 'Quỹ khen thưởng, phúc lợi', 'type' => 'liability', 'nb' => 'credit', 'sort' => 3530],

        // === LOẠI 4 — VỐN CHỦ SỞ HỮU ===
        ['code' => '411', 'name' => 'Vốn đầu tư của chủ sở hữu', 'type' => 'equity', 'nb' => 'credit', 'postable' => false, 'sort' => 4110],
        ['code' => '4111', 'name' => 'Vốn góp của chủ sở hữu', 'type' => 'equity', 'nb' => 'credit', 'parent_code' => '411', 'sort' => 4111],
        ['code' => '4112', 'name' => 'Thặng dư vốn cổ phần', 'type' => 'equity', 'nb' => 'credit', 'parent_code' => '411', 'sort' => 4112],
        ['code' => '418', 'name' => 'Các quỹ thuộc vốn CSH', 'type' => 'equity', 'nb' => 'credit', 'sort' => 4180],
        ['code' => '419', 'name' => 'Cổ phiếu quỹ', 'type' => 'contra_asset', 'nb' => 'debit', 'sort' => 4190],
        ['code' => '421', 'name' => 'Lợi nhuận sau thuế chưa phân phối', 'type' => 'equity', 'nb' => 'credit', 'postable' => false, 'sort' => 4210],
        ['code' => '4211', 'name' => 'LNST chưa phân phối năm trước', 'type' => 'equity', 'nb' => 'credit', 'parent_code' => '421', 'sort' => 4211],
        ['code' => '4212', 'name' => 'LNST chưa phân phối năm nay', 'type' => 'equity', 'nb' => 'credit', 'parent_code' => '421', 'sort' => 4212],

        // === LOẠI 5 — DOANH THU ===
        ['code' => '511', 'name' => 'Doanh thu bán hàng & cung cấp dịch vụ', 'type' => 'revenue', 'nb' => 'credit', 'postable' => false, 'sort' => 5110],
        ['code' => '5111', 'name' => 'Doanh thu bán hàng hoá', 'type' => 'revenue', 'nb' => 'credit', 'parent_code' => '511', 'sort' => 5111],
        ['code' => '5113', 'name' => 'Doanh thu cung cấp dịch vụ', 'type' => 'revenue', 'nb' => 'credit', 'parent_code' => '511', 'sort' => 5113],
        ['code' => '515', 'name' => 'Doanh thu hoạt động tài chính', 'type' => 'revenue', 'nb' => 'credit', 'sort' => 5150],
        ['code' => '521', 'name' => 'Các khoản giảm trừ doanh thu', 'type' => 'contra_revenue', 'nb' => 'debit', 'sort' => 5210],

        // === LOẠI 6 — CHI PHÍ ===
        ['code' => '611', 'name' => 'Mua hàng', 'type' => 'expense', 'nb' => 'debit', 'sort' => 6110],
        ['code' => '631', 'name' => 'Giá thành sản xuất', 'type' => 'expense', 'nb' => 'debit', 'sort' => 6310],
        ['code' => '632', 'name' => 'Giá vốn hàng bán', 'type' => 'cogs', 'nb' => 'debit', 'sort' => 6320],
        ['code' => '635', 'name' => 'Chi phí tài chính', 'type' => 'expense', 'nb' => 'debit', 'sort' => 6350],
        ['code' => '642', 'name' => 'Chi phí quản lý kinh doanh', 'type' => 'expense', 'nb' => 'debit', 'postable' => false, 'sort' => 6420],
        ['code' => '6421', 'name' => 'Chi phí bán hàng', 'type' => 'expense', 'nb' => 'debit', 'parent_code' => '642', 'sort' => 6421],
        ['code' => '6422', 'name' => 'Chi phí quản lý doanh nghiệp', 'type' => 'expense', 'nb' => 'debit', 'parent_code' => '642', 'sort' => 6422],

        // === LOẠI 7-8 — KHÁC ===
        ['code' => '711', 'name' => 'Thu nhập khác', 'type' => 'revenue', 'nb' => 'credit', 'sort' => 7110],
        ['code' => '811', 'name' => 'Chi phí khác', 'type' => 'expense', 'nb' => 'debit', 'sort' => 8110],
        ['code' => '821', 'name' => 'Chi phí thuế TNDN', 'type' => 'expense', 'nb' => 'debit', 'sort' => 8210],

        // === LOẠI 9 — XÁC ĐỊNH KẾT QUẢ KD ===
        ['code' => '911', 'name' => 'Xác định kết quả kinh doanh', 'type' => 'clearing', 'nb' => 'debit', 'sort' => 9110],
    ];
}
