import { useEffect, useState } from 'react';
import { Alert, App as AntApp, Button, Card, Radio, Space, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan, useTenant, useUpdateTenant } from '@/lib/tenant';

/**
 * /settings/print — "Mẫu in": khổ phiếu in (mỗi đơn 1 trang, render đúng kích thước này). Lưu vào
 * tenant.settings.print.label_size. Áp cho phiếu giao hàng tự tạo / packing slip / hoá đơn. owner/admin
 * sửa được. (Tem/AWB thật của sàn/ĐVVC giữ nguyên khổ gốc của họ.) SPEC 0013.
 */
const SIZES: Array<{ value: string; label: string; hint: string }> = [
    { value: 'A6', label: 'A6 (105×148mm)', hint: 'Tem nhiệt phổ biến — 1 phiếu/trang' },
    { value: '100x150mm', label: '100×150mm', hint: 'Tem nhiệt 10×15cm' },
    { value: '80mm', label: '80mm (cuộn nhiệt)', hint: 'Máy in bill nhiệt khổ 80mm, dài tự động' },
    { value: 'A5', label: 'A5 (148×210mm)', hint: '1 phiếu/trang' },
    { value: 'A4', label: 'A4 (210×297mm)', hint: 'Máy in văn phòng — 1 phiếu/trang' },
];
const DEFAULT_SIZE = 'A6';

export function SettingsPrintPage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const update = useUpdateTenant();
    const canManage = useCan('tenant.settings');
    const [size, setSize] = useState<string>(DEFAULT_SIZE);

    useEffect(() => {
        if (!tenant) return;
        const cur = (tenant.settings?.print ?? {}) as Record<string, unknown>;
        setSize(typeof cur.label_size === 'string' && cur.label_size ? cur.label_size : DEFAULT_SIZE);
    }, [tenant]);

    const save = () => update.mutate({ settings: { print: { label_size: size } } }, {
        onSuccess: () => message.success('Đã lưu khổ phiếu in'),
        onError: (e) => message.error(errorMessage(e)),
    });

    if (!tenant) return <Card loading title="Mẫu in" />;

    return (
        <Card title="Khổ phiếu in">
            <Typography.Paragraph type="secondary" style={{ marginBottom: 16 }}>
                Khi in <b>phiếu giao hàng / packing slip / hoá đơn</b>, hệ thống render PDF theo khổ này, <b>mỗi đơn 1 trang</b>.
                Tem / vận đơn <b>thật của sàn hoặc ĐVVC</b> luôn giữ nguyên khổ gốc của họ (không tự vẽ lại).
            </Typography.Paragraph>
            <Radio.Group value={size} onChange={(e) => setSize(e.target.value)} disabled={!canManage}>
                <Space direction="vertical">
                    {SIZES.map((s) => (
                        <Radio key={s.value} value={s.value}>
                            {s.label} <Typography.Text type="secondary" style={{ fontSize: 12 }}>— {s.hint}</Typography.Text>
                        </Radio>
                    ))}
                </Space>
            </Radio.Group>
            <div style={{ marginTop: 16 }}>
                {canManage
                    ? <Button type="primary" loading={update.isPending} onClick={save}>Lưu</Button>
                    : <Alert type="info" showIcon message="Chỉ Chủ sở hữu / Quản trị mới sửa được cài đặt này." />}
            </div>
        </Card>
    );
}
