import { useMemo, useState } from 'react';
import { Modal, Radio, Select, Space, Tag, Typography } from 'antd';
import { useShippingLabelTemplates, usePreviewShippingLabelTemplate } from '@/lib/shippingLabels';
import { useTenant } from '@/lib/tenant';

const LS_KEY = (tenantId: number | string) => `lastShippingLabelTemplateId:${tenantId}`;

export function TemplateAliasPicker({ open, onCancel, onConfirm }: {
    open: boolean;
    onCancel: () => void;
    onConfirm: (templateId: number | null) => void;
}) {
    const { data: tenant } = useTenant();
    const { data: items = [], isLoading } = useShippingLabelTemplates();
    const preview = usePreviewShippingLabelTemplate();

    const defaultId = useMemo(() => {
        if (!tenant) return null;
        const lastUsed = Number(localStorage.getItem(LS_KEY(tenant.id)) || 0);
        if (items.find((t) => t.id === lastUsed)) return lastUsed;
        return items.find((t) => t.is_default)?.id ?? null;
    }, [items, tenant]);

    const [selected, setSelected] = useState<number | null>(defaultId);

    const handleConfirm = () => {
        if (tenant && selected != null) localStorage.setItem(LS_KEY(tenant.id), String(selected));
        onConfirm(selected);
    };

    return (
        <Modal open={open} onCancel={onCancel} onOk={handleConfirm} okText="In" cancelText="Huỷ" title="Chọn mẫu phiếu giao hàng">
            {items.length === 0 ? (
                <Typography.Paragraph type="secondary">
                    Bạn chưa có template nào — sẽ dùng mẫu mặc định của hệ thống. Có thể tạo template tại <b>Cài đặt → Mẫu phiếu giao hàng</b>.
                </Typography.Paragraph>
            ) : items.length <= 5 ? (
                <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)} style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {items.map((t) => (
                        <Radio key={t.id} value={t.id}>
                            <Space>
                                <span>{t.name}</span>
                                <Tag>{t.paper}</Tag>
                                {t.is_default && <Tag color="gold">Mặc định</Tag>}
                                <a onClick={(e) => { e.preventDefault(); preview.mutate({ id: t.id, sample_profile: 'three_items_long_address' }, { onSuccess: (r) => window.open(r.url, '_blank') }); }}>Xem trước</a>
                            </Space>
                        </Radio>
                    ))}
                    <Radio value={null as unknown as number}>Mặc định hệ thống (không dùng template)</Radio>
                </Radio.Group>
            ) : (
                <Select style={{ width: '100%' }} value={selected} onChange={(v) => setSelected(v as number | null)} showSearch loading={isLoading}
                    options={[
                        ...items.map((t) => ({ value: t.id, label: `${t.name} (${t.paper})${t.is_default ? ' · Mặc định' : ''}` })),
                        { value: null, label: 'Mặc định hệ thống' },
                    ]} />
            )}
        </Modal>
    );
}
