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
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Chủ sở hữu',
            self::Admin => 'Quản trị',
            self::StaffOrder => 'NV xử lý đơn',
            self::StaffWarehouse => 'NV kho',
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
            self::Owner => ['*'],
            self::Admin => ['*', '!tenant.delete', '!tenant.transfer'],
            self::StaffOrder => [
                'orders.view', 'orders.update', 'orders.create', 'orders.status',
                'fulfillment.view', 'fulfillment.print', 'fulfillment.ship',
                'products.view', 'inventory.view', 'inventory.map', 'channels.view', 'dashboard.view',
                'customers.view', 'customers.note', 'customers.view_phone',
            ],
            self::StaffWarehouse => [
                'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.stocktake', 'inventory.map',
                'products.view', 'products.manage',
                'fulfillment.view', 'fulfillment.scan', 'fulfillment.print',
                'orders.view', 'dashboard.view', 'customers.view',
            ],
            self::Accountant => [
                'finance.view', 'finance.reconcile', 'reports.view', 'reports.export',
                'orders.view', 'inventory.view', 'dashboard.view', 'customers.view',
            ],
            self::Viewer => ['orders.view', 'inventory.view', 'products.view', 'channels.view', 'dashboard.view', 'customers.view'],
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
