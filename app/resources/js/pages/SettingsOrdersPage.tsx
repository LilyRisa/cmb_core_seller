import { useEffect, useState } from 'react';
import { Alert, App as AntApp, Button, Card, InputNumber, Space, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { CHANNEL_META } from '@/lib/format';
import { ChannelLogo } from '@/components/ChannelLogo';
import { useCan, useTenant, useUpdateTenant } from '@/lib/tenant';

/**
 * /settings/orders — "Cài đặt đơn hàng": phí sàn (%) theo từng nền tảng, dùng để ước tính lợi nhuận sau
 * phí sàn của đơn (SPEC 0012). Lưu vào tenant.settings.platform_fee_pct. owner/admin sửa được.
 * Giá vốn (bình quân | lô gần nhất) đặt theo từng SKU ở màn hình SKU — không cấu hình ở đây.
 */
const PLATFORMS = ['tiktok', 'shopee', 'lazada', 'manual'] as const;

export function SettingsOrdersPage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const update = useUpdateTenant();
    const canManage = useCan('tenant.settings');
    const [fees, setFees] = useState<Record<string, number | null>>({});

    useEffect(() => {
        if (!tenant) return;
        const cur = (tenant.settings?.platform_fee_pct ?? {}) as Record<string, unknown>;
        setFees(Object.fromEntries(PLATFORMS.map((p) => [p, cur[p] == null ? null : Number(cur[p])])));
    }, [tenant]);

    const save = () => {
        const platform_fee_pct: Record<string, number> = {};
        for (const p of PLATFORMS) {
            const v = fees[p];
            if (v != null && v > 0) platform_fee_pct[p] = v;
        }
        update.mutate({ settings: { platform_fee_pct } }, {
            onSuccess: () => message.success('Đã lưu cài đặt phí sàn'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    if (!tenant) return <Card loading title="Cài đặt đơn hàng" />;

    return (
        <Card title="Phí sàn — ước tính lợi nhuận đơn hàng">
            <Typography.Paragraph type="secondary" style={{ marginBottom: 16 }}>
                Đặt phần trăm phí sàn (hoa hồng + phí dịch vụ) cho từng nền tảng. Hệ thống dùng nó để tính
                <b> lợi nhuận ước tính sau phí sàn</b> của mỗi đơn: <i>tổng tiền − phí sàn − phí vận chuyển − giá vốn hàng</i>.
                Giá vốn lấy theo cài đặt của từng SKU (giá vốn bình quân hoặc giá vốn lô nhập kho gần nhất).
            </Typography.Paragraph>
            <Space direction="vertical" size="middle" style={{ width: '100%', maxWidth: 460 }}>
                {PLATFORMS.map((p) => {
                    const meta = CHANNEL_META[p] ?? { name: p, color: '#8c8c8c' };
                    return (
                        <div key={p} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
                                <ChannelLogo provider={p} size={20} />
                                {meta.name}
                            </span>
                            <InputNumber
                                disabled={!canManage}
                                min={0}
                                max={100}
                                step={0.5}
                                value={fees[p] ?? undefined}
                                onChange={(v) => setFees((f) => ({ ...f, [p]: v ?? null }))}
                                addonAfter="%"
                                placeholder="0"
                                style={{ width: 140 }}
                            />
                        </div>
                    );
                })}
                {canManage
                    ? <Button type="primary" loading={update.isPending} onClick={save}>Lưu</Button>
                    : <Alert type="info" showIcon message="Chỉ Chủ sở hữu / Quản trị mới sửa được cài đặt này." />}
            </Space>
        </Card>
    );
}
