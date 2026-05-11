<?php

namespace Tests\Unit;

use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use PHPUnit\Framework\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_owner_can_do_everything(): void
    {
        $this->assertTrue(Role::Owner->can('orders.update'));
        $this->assertTrue(Role::Owner->can('billing.manage'));
        $this->assertTrue(Role::Owner->can('anything.at.all'));
    }

    public function test_admin_is_full_business_access_but_cannot_delete_or_transfer_the_tenant(): void
    {
        $this->assertTrue(Role::Admin->can('orders.update'));
        $this->assertTrue(Role::Admin->can('inventory.adjust'));
        $this->assertFalse(Role::Admin->can('tenant.delete'));
        $this->assertFalse(Role::Admin->can('tenant.transfer'));
    }

    public function test_staff_order_can_work_orders_but_not_finance_or_inventory_writes(): void
    {
        $this->assertTrue(Role::StaffOrder->can('orders.status'));
        $this->assertTrue(Role::StaffOrder->can('fulfillment.print'));
        $this->assertFalse(Role::StaffOrder->can('finance.view'));
        $this->assertFalse(Role::StaffOrder->can('inventory.adjust'));
    }

    public function test_staff_warehouse_can_move_stock_but_not_change_order_status(): void
    {
        $this->assertTrue(Role::StaffWarehouse->can('inventory.transfer'));
        $this->assertTrue(Role::StaffWarehouse->can('fulfillment.scan'));
        $this->assertFalse(Role::StaffWarehouse->can('orders.status'));
        $this->assertFalse(Role::StaffWarehouse->can('finance.view'));
    }

    public function test_accountant_is_read_plus_finance(): void
    {
        $this->assertTrue(Role::Accountant->can('finance.reconcile'));
        $this->assertTrue(Role::Accountant->can('reports.export'));
        $this->assertTrue(Role::Accountant->can('orders.view'));
        $this->assertFalse(Role::Accountant->can('orders.update'));
    }

    public function test_viewer_is_read_only(): void
    {
        $this->assertTrue(Role::Viewer->can('orders.view'));
        $this->assertTrue(Role::Viewer->can('dashboard.view'));
        $this->assertFalse(Role::Viewer->can('orders.update'));
        $this->assertFalse(Role::Viewer->can('inventory.adjust'));
    }

    public function test_every_role_has_a_label(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertNotEmpty($role->label());
        }
    }
}
