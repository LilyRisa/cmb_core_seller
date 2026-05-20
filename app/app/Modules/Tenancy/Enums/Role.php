<?php

namespace CMBcoreSeller\Modules\Tenancy\Enums;

/**
 * Built-in roles for a member within a tenant.
 * See docs/01-architecture/multi-tenancy-and-rbac.md §3.
 */
enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case StaffOrder = 'staff_order';
    case StaffWarehouse = 'staff_warehouse';
    case StaffCs = 'staff_cs';           // SPEC-0024 — NV chăm sóc khách hàng (chat)
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Chủ sở hữu',
            self::Admin => 'Quản trị',
            self::StaffOrder => 'NV xử lý đơn',
            self::StaffWarehouse => 'NV kho',
            self::StaffCs => 'NV chăm sóc khách hàng',
            self::Accountant => 'Kế toán',
            self::Viewer => 'Chỉ xem',
        };
    }

    /**
     * Permission strings granted to this role. '*' means everything.
     * Permissions are coarse for now; refine as modules land.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            // Owner: toàn quyền + quản lý billing (`billing.manage`). Phase 6.4 — SPEC 0018.
            self::Owner => ['*'],
            // Admin: toàn quyền nghiệp vụ nhưng KHÔNG `billing.manage` (chỉ owner đổi gói / thanh toán).
            self::Admin => ['*', '!tenant.delete', '!tenant.transfer', '!billing.manage'],
            self::StaffOrder => [
                'orders.view', 'orders.update', 'orders.create', 'orders.status',
                'fulfillment.view', 'fulfillment.print', 'fulfillment.ship',
                'products.view', 'inventory.view', 'inventory.map', 'channels.view', 'dashboard.view',
                'customers.view', 'customers.note', 'customers.view_phone',
                // SPEC-0024 — NV đơn thường cũng trả tin với khách trong context xử lý đơn.
                'messaging.view', 'messaging.reply',
            ],
            self::StaffWarehouse => [
                'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.stocktake', 'inventory.map',
                'products.view', 'products.manage',
                'fulfillment.view', 'fulfillment.scan', 'fulfillment.print',
                'orders.view', 'dashboard.view', 'customers.view',
                // Kho được xem NCC + nhận hàng theo PO (không sửa giá / không huỷ PO) — Phase 6.1.
                'procurement.view', 'procurement.receive',
            ],
            self::Accountant => [
                'finance.view', 'finance.reconcile', 'reports.view', 'reports.export',
                'orders.view', 'inventory.view', 'dashboard.view', 'customers.view',
                // Kế toán xem được NCC / PO + giá nhập để đối soát giá vốn — Phase 6.1.
                'procurement.view',
                // Kế toán xem được gói + hoá đơn (không thanh toán) — Phase 6.4.
                'billing.view',
                // Module Kế toán đầy đủ — Phase 7. KHÔNG có `accounting.config` (cấu hình CoA / mapping rule
                // thuộc Owner/Admin) — đề phòng sai dữ liệu hệ thống.
                'accounting.view', 'accounting.post', 'accounting.close_period', 'accounting.export',
            ],
            self::Viewer => ['orders.view', 'inventory.view', 'products.view', 'channels.view', 'dashboard.view', 'customers.view'],
            // SPEC-0024 — staff_cs (Customer Service): full messaging permission + chỉ-xem context đơn/khách.
            // KHÔNG có quyền sửa đơn / kho / billing. Mở rộng phase sau nếu cần.
            self::StaffCs => [
                'messaging.view', 'messaging.reply', 'messaging.template.manage',
                'orders.view', 'customers.view', 'customers.view_phone', 'customers.note',
                'channels.view', 'dashboard.view', 'products.view', 'inventory.view',
            ],
        };
    }

    public function can(string $permission): bool
    {
        $perms = $this->permissions();

        if (in_array('!'.$permission, $perms, true)) {
            return false;
        }

        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }
}
