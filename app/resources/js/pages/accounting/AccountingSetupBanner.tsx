import { Alert, App, Button, Space } from 'antd';
import { ThunderboltOutlined } from '@ant-design/icons';
import { useAccountingSetup, useAccountingSetupStatus } from '@/lib/accounting';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';

/**
 * Banner xuất hiện ở mọi trang /accounting/* khi tenant chưa onboard module Accounting.
 * Owner/Admin bấm "Khởi tạo" → POST /accounting/setup (idempotent).
 */
export function AccountingSetupBanner({ onInitialized }: { onInitialized?: () => void }) {
    const { data: status, isLoading } = useAccountingSetupStatus();
    const setup = useAccountingSetup();
    const canConfig = useCan('accounting.config');
    const { message } = App.useApp();

    if (isLoading) return null;
    if (status?.initialized) return null;

    const run = async () => {
        try {
            const r = await setup.mutateAsync({ year: new Date().getFullYear() });
            message.success(`Đã khởi tạo: ${r.accounts_created} TK, ${r.periods_created} kỳ kế toán, ${r.rules_created} quy tắc hạch toán.`);
            onInitialized?.();
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    return (
        <Alert
            type="info"
            showIcon
            style={{ marginBottom: 16 }}
            message="Module Kế toán chưa được khởi tạo cho gian hàng này"
            description={
                <Space direction="vertical" size={2}>
                    <span>Lần đầu sử dụng — cần khởi tạo hệ thống tài khoản theo Thông tư 133/2016 (DN nhỏ & vừa), tạo kỳ kế toán năm hiện tại và mặc định quy tắc hạch toán tự động.</span>
                    <span style={{ color: 'rgba(0,0,0,0.45)', fontSize: 12 }}>An toàn để chạy lại — hệ thống bỏ qua mục đã tồn tại.</span>
                </Space>
            }
            action={
                canConfig ? (
                    <Button type="primary" icon={<ThunderboltOutlined />} onClick={run} loading={setup.isPending}>
                        Khởi tạo TT133
                    </Button>
                ) : (
                    <span style={{ color: 'rgba(0,0,0,0.45)', fontSize: 12 }}>Liên hệ chủ gian hàng để khởi tạo.</span>
                )
            }
        />
    );
}
