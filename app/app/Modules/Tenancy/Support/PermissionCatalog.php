<?php

namespace CMBcoreSeller\Modules\Tenancy\Support;

/**
 * Single source of truth for every permission string a tenant role may grant.
 *
 * Keys MUST match the ability strings used in `Gate::authorize('...')` across the
 * app — do NOT add abilities to the Role enum anymore, add them here. Each entry is
 * tagged `view` (read-only) or `action` (mutating) so the UI can render a
 * feature × {Xem, Thao tác} matrix. A few abilities are OWNER-ONLY: they are never
 * assignable to a custom role (only the built-in owner role, which bypasses checks).
 */
final class PermissionCatalog
{
    /** Abilities only the built-in owner role has — never assignable to a custom role. */
    public const OWNER_ONLY = ['tenant.delete', 'tenant.transfer', 'billing.manage'];

    /**
     * Feature groups → permissions. Order is display order.
     *
     * @return array<int, array{key:string, label:string, permissions:list<array{key:string, label:string, type:string}>}>
     */
    public static function groups(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Tổng quan', 'permissions' => [
                ['key' => 'dashboard.view', 'label' => 'Xem tổng quan', 'type' => 'view'],
            ]],
            ['key' => 'orders', 'label' => 'Đơn hàng', 'permissions' => [
                ['key' => 'orders.view', 'label' => 'Xem đơn', 'type' => 'view'],
                ['key' => 'orders.create', 'label' => 'Tạo đơn', 'type' => 'action'],
                ['key' => 'orders.update', 'label' => 'Sửa đơn (tag/ghi chú)', 'type' => 'action'],
                ['key' => 'orders.status', 'label' => 'Đổi trạng thái đơn', 'type' => 'action'],
                ['key' => 'orders.delete', 'label' => 'Xoá đơn (đã huỷ)', 'type' => 'action'],
            ]],
            ['key' => 'products', 'label' => 'Sản phẩm', 'permissions' => [
                ['key' => 'products.view', 'label' => 'Xem sản phẩm', 'type' => 'view'],
                ['key' => 'products.manage', 'label' => 'Quản lý sản phẩm', 'type' => 'action'],
            ]],
            ['key' => 'inventory', 'label' => 'Kho', 'permissions' => [
                ['key' => 'inventory.view', 'label' => 'Xem tồn kho', 'type' => 'view'],
                ['key' => 'inventory.adjust', 'label' => 'Điều chỉnh tồn', 'type' => 'action'],
                ['key' => 'inventory.transfer', 'label' => 'Chuyển kho', 'type' => 'action'],
                ['key' => 'inventory.stocktake', 'label' => 'Kiểm kê', 'type' => 'action'],
                ['key' => 'inventory.map', 'label' => 'Map SKU', 'type' => 'action'],
            ]],
            ['key' => 'fulfillment', 'label' => 'Giao vận', 'permissions' => [
                ['key' => 'fulfillment.view', 'label' => 'Xem giao vận', 'type' => 'view'],
                ['key' => 'fulfillment.print', 'label' => 'In phiếu/vận đơn', 'type' => 'action'],
                ['key' => 'fulfillment.ship', 'label' => 'Tạo vận đơn / giao', 'type' => 'action'],
                ['key' => 'fulfillment.scan', 'label' => 'Quét đóng gói', 'type' => 'action'],
                ['key' => 'fulfillment.carriers', 'label' => 'Cấu hình đơn vị vận chuyển', 'type' => 'action'],
            ]],
            ['key' => 'channels', 'label' => 'Kênh bán', 'permissions' => [
                ['key' => 'channels.view', 'label' => 'Xem kênh', 'type' => 'view'],
                ['key' => 'channels.manage', 'label' => 'Kết nối / quản lý kênh', 'type' => 'action'],
            ]],
            ['key' => 'messaging', 'label' => 'Tin nhắn', 'permissions' => [
                ['key' => 'messaging.view', 'label' => 'Xem hội thoại', 'type' => 'view'],
                ['key' => 'messaging.reply', 'label' => 'Trả lời tin', 'type' => 'action'],
                ['key' => 'messaging.assign', 'label' => 'Phân công hội thoại', 'type' => 'action'],
                ['key' => 'messaging.template.manage', 'label' => 'Quản lý mẫu tin', 'type' => 'action'],
                ['key' => 'messaging.rule.manage', 'label' => 'Quản lý kịch bản', 'type' => 'action'],
                ['key' => 'messaging.connect', 'label' => 'Kết nối kênh chat', 'type' => 'action'],
                ['key' => 'messaging.ai.config', 'label' => 'Cấu hình AI chat', 'type' => 'action'],
                ['key' => 'messaging.ai.train', 'label' => 'Huấn luyện AI chat', 'type' => 'action'],
            ]],
            ['key' => 'customers', 'label' => 'Khách hàng', 'permissions' => [
                ['key' => 'customers.view', 'label' => 'Xem khách hàng', 'type' => 'view'],
                ['key' => 'customers.view_phone', 'label' => 'Xem số điện thoại', 'type' => 'view'],
                ['key' => 'customers.note', 'label' => 'Ghi chú khách', 'type' => 'action'],
                ['key' => 'customers.block', 'label' => 'Chặn khách', 'type' => 'action'],
                ['key' => 'customers.merge', 'label' => 'Gộp khách', 'type' => 'action'],
            ]],
            ['key' => 'marketing', 'label' => 'Quảng cáo', 'permissions' => [
                ['key' => 'marketing.view', 'label' => 'Xem quảng cáo', 'type' => 'view'],
                ['key' => 'marketing.connect', 'label' => 'Kết nối tài khoản QC', 'type' => 'action'],
                ['key' => 'marketing.ads.create', 'label' => 'Tạo quảng cáo', 'type' => 'action'],
            ]],
            ['key' => 'finance', 'label' => 'Tài chính', 'permissions' => [
                ['key' => 'finance.view', 'label' => 'Xem tài chính', 'type' => 'view'],
                ['key' => 'finance.reconcile', 'label' => 'Đối soát', 'type' => 'action'],
            ]],
            ['key' => 'accounting', 'label' => 'Kế toán', 'permissions' => [
                ['key' => 'accounting.view', 'label' => 'Xem kế toán', 'type' => 'view'],
                ['key' => 'accounting.post', 'label' => 'Hạch toán', 'type' => 'action'],
                ['key' => 'accounting.close_period', 'label' => 'Khoá kỳ', 'type' => 'action'],
                ['key' => 'accounting.export', 'label' => 'Xuất sổ', 'type' => 'action'],
            ]],
            ['key' => 'einvoice', 'label' => 'Hóa đơn điện tử', 'permissions' => [
                ['key' => 'einvoice.view', 'label' => 'Xem hóa đơn điện tử', 'type' => 'view'],
                ['key' => 'einvoice.config', 'label' => 'Cấu hình nhà cung cấp HĐĐT', 'type' => 'action'],
                ['key' => 'einvoice.issue', 'label' => 'Phát hành hóa đơn', 'type' => 'action'],
                ['key' => 'einvoice.manage', 'label' => 'Hủy/Điều chỉnh/Thay thế hóa đơn', 'type' => 'action'],
            ]],
            ['key' => 'procurement', 'label' => 'Mua hàng', 'permissions' => [
                ['key' => 'procurement.view', 'label' => 'Xem mua hàng', 'type' => 'view'],
                ['key' => 'procurement.receive', 'label' => 'Nhận hàng (PO)', 'type' => 'action'],
                ['key' => 'procurement.manage', 'label' => 'Quản lý mua hàng', 'type' => 'action'],
            ]],
            ['key' => 'reports', 'label' => 'Báo cáo', 'permissions' => [
                ['key' => 'reports.view', 'label' => 'Xem báo cáo', 'type' => 'view'],
                ['key' => 'reports.export', 'label' => 'Xuất báo cáo', 'type' => 'action'],
            ]],
            ['key' => 'billing', 'label' => 'Gói & thanh toán', 'permissions' => [
                ['key' => 'billing.view', 'label' => 'Xem gói / hoá đơn', 'type' => 'view'],
                ['key' => 'billing.manage', 'label' => 'Đổi gói / thanh toán (chỉ chủ shop)', 'type' => 'action'],
            ]],
            ['key' => 'team', 'label' => 'Thành viên & phân quyền', 'permissions' => [
                ['key' => 'team.manage', 'label' => 'Quản lý thành viên & vai trò', 'type' => 'action'],
            ]],
            ['key' => 'tenant', 'label' => 'Gian hàng', 'permissions' => [
                ['key' => 'tenant.settings', 'label' => 'Sửa thông tin gian hàng', 'type' => 'action'],
                ['key' => 'tenant.delete', 'label' => 'Xoá gian hàng (chỉ chủ shop)', 'type' => 'action'],
                ['key' => 'tenant.transfer', 'label' => 'Chuyển quyền sở hữu (chỉ chủ shop)', 'type' => 'action'],
            ]],
        ];
    }

    /**
     * Every known permission key.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $keys = [];
        foreach (self::groups() as $group) {
            foreach ($group['permissions'] as $perm) {
                $keys[] = $perm['key'];
            }
        }

        return $keys;
    }

    /**
     * Permissions a custom role may be granted (everything except owner-only).
     *
     * @return list<string>
     */
    public static function assignable(): array
    {
        return array_values(array_filter(self::all(), static fn (string $k): bool => ! in_array($k, self::OWNER_ONLY, true)));
    }

    public static function isValid(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }

    public static function isAssignable(string $permission): bool
    {
        return in_array($permission, self::assignable(), true);
    }
}
