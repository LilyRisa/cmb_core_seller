import {
    ShoppingOutlined, MessageOutlined, ShopOutlined, InboxOutlined,
    FacebookFilled, TikTokOutlined, PieChartOutlined, CalculatorOutlined, SettingOutlined,
} from '@ant-design/icons';
import type { ReactNode } from 'react';

export interface AppMenuItem { key: string; label: string; children?: { key: string; label: string }[] }
export interface AppDef {
    key: string;
    label: string;
    icon: ReactNode;
    /** ability string (useCan); bỏ trống = mọi vai trò thấy. */
    permission?: string;
    defaultPath: string;
    /** path-prefix thuộc app này (để khớp tab/sub-menu); chọn prefix dài nhất. */
    prefixes: string[];
    menu: AppMenuItem[];
}

export const APP_CATALOG: AppDef[] = [
    {
        key: 'sales', label: 'Bán hàng', icon: <ShoppingOutlined />, permission: 'orders.view',
        defaultPath: '/orders', prefixes: ['/orders', '/returns', '/customers'],
        menu: [
            { key: '/orders', label: 'Đơn hàng' },
            { key: '/returns', label: 'Hoàn & Hủy' },
            { key: '/customers', label: 'Khách hàng' },
        ],
    },
    {
        key: 'messaging', label: 'Tin nhắn', icon: <MessageOutlined />, permission: 'messaging.view',
        defaultPath: '/messaging', prefixes: ['/messaging'],
        menu: [
            { key: '/messaging', label: 'Hộp thư' },
            { key: '/messaging/channels', label: 'Kết nối kênh' },
            { key: '/messaging/templates', label: 'Mẫu tin' },
            { key: '/messaging/utility-templates', label: 'Tin tiện ích' },
            { key: '/messaging/auto-rules', label: 'Tự động trả lời' },
            { key: '/messaging/flows', label: 'Kịch bản tự động' },
            { key: '/messaging/knowledge', label: 'AI training' },
        ],
    },
    {
        key: 'listing', label: 'Đăng bán sàn', icon: <ShopOutlined />, permission: 'products.view',
        defaultPath: '/marketplace/products', prefixes: ['/marketplace', '/channels', '/listings'],
        menu: [
            { key: '/marketplace/products', label: 'Sao chép sản phẩm' },
            { key: '/marketplace/to-push', label: 'Chờ đẩy lên sàn' },
            { key: '/marketplace/on-channel', label: 'Đã có trên sàn' },
            { key: '/marketplace/promotions', label: 'Chiến dịch giảm giá' },
            { key: '/channels', label: 'Gian hàng' },
        ],
    },
    {
        key: 'warehouse', label: 'Kho', icon: <InboxOutlined />, permission: 'inventory.view',
        defaultPath: '/inventory', prefixes: ['/inventory', '/procurement', '/products'],
        menu: [
            { key: '/inventory', label: 'Tồn kho' },
            { key: '/products', label: 'Sản phẩm & SKU' },
            { key: '/procurement/demand-planning', label: 'Đề xuất nhập hàng' },
            { key: '/procurement/suppliers', label: 'Nhà cung cấp' },
            { key: '/procurement/purchase-orders', label: 'Đơn mua hàng' },
        ],
    },
    {
        key: 'ads_facebook', label: 'Quảng cáo Facebook', icon: <FacebookFilled />, permission: 'marketing.view',
        defaultPath: '/marketing', prefixes: ['/marketing/ads', '/marketing'],
        menu: [
            { key: '/marketing', label: 'Tổng quan' },
            { key: '/marketing/ads/new', label: 'Tạo quảng cáo' },
            { key: '/marketing/ads/ai', label: 'Quảng cáo bằng AI' },
        ],
    },
    {
        key: 'ads_tiktok', label: 'Quảng cáo TikTok', icon: <TikTokOutlined />, permission: 'marketing.view',
        defaultPath: '/marketing/tiktok', prefixes: ['/marketing/tiktok'],
        menu: [{ key: '/marketing/tiktok', label: 'Tổng quan' }],
    },
    {
        key: 'reports', label: 'Báo cáo', icon: <PieChartOutlined />, permission: 'reports.view',
        defaultPath: '/reports/overview', prefixes: ['/reports', '/shop-report', '/finance'],
        menu: [
            { key: '/reports/overview', label: 'Báo cáo tổng thể' },
            { key: '/reports', label: 'Báo cáo bán hàng' },
            { key: '/shop-report', label: 'Báo cáo sàn' },
            { key: '/finance/settlements', label: 'Đối soát sàn' },
        ],
    },
    {
        key: 'accounting', label: 'Kế toán', icon: <CalculatorOutlined />, permission: 'accounting.view',
        defaultPath: '/accounting/dashboard', prefixes: ['/accounting'],
        menu: [
            { key: '/accounting/dashboard', label: 'Tổng quan kế toán' },
            { key: 'acc-books', label: 'Sổ sách', children: [
                { key: '/accounting/journals', label: 'Sổ nhật ký chung' },
                { key: '/accounting/chart-of-accounts', label: 'Hệ thống tài khoản' },
                { key: '/accounting/balances', label: 'Cân đối phát sinh' },
                { key: '/accounting/periods', label: 'Kỳ kế toán' },
            ] },
            { key: 'acc-money', label: 'Công nợ & Tiền', children: [
                { key: '/accounting/ar', label: 'Công nợ phải thu' },
                { key: '/accounting/ap', label: 'Công nợ phải trả' },
                { key: '/accounting/cash', label: 'Quỹ & Ngân hàng' },
            ] },
            { key: '/accounting/reports', label: 'Báo cáo tài chính & Thuế' },
        ],
    },
    {
        key: 'settings', label: 'Cài đặt hệ thống', icon: <SettingOutlined />,
        defaultPath: '/settings/profile', prefixes: ['/settings', '/sync-logs', '/support'],
        menu: [
            { key: '/settings/profile', label: 'Cài đặt' },
            { key: '/sync-logs', label: 'Nhật ký đồng bộ' },
            { key: '/support', label: 'Trung tâm trợ giúp' },
        ],
    },
];

import { useCan } from '@/lib/tenant';

/** Lọc app theo quyền — gọi useCan cho mọi app (số lượng cố định, đúng rule hooks). */
export function usePermittedApps(): AppDef[] {
    // Hooks gọi cố định theo thứ tự khai báo (APP_CATALOG bất biến) — an toàn.
    return APP_CATALOG.filter((app) => {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        return !app.permission || useCan(app.permission);
    });
}

/** Khớp app theo prefix dài nhất; '/' → undefined (thuộc Desktop home). */
export function appForPath(pathname: string): AppDef | undefined {
    let best: AppDef | undefined;
    let bestLen = -1;
    for (const app of APP_CATALOG) {
        for (const p of app.prefixes) {
            if ((pathname === p || pathname.startsWith(p + '/') || pathname.startsWith(p + '?')) && p.length > bestLen) {
                best = app; bestLen = p.length;
            }
        }
    }
    return best;
}
