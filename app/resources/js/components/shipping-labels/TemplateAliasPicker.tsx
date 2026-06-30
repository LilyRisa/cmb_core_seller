import { useMemo, useState } from 'react';
import { Divider, Modal, Radio, Select, Space, Tag, Typography } from 'antd';
import { useShippingLabelTemplates, usePreviewShippingLabelTemplate } from '@/lib/shippingLabels';
import { useTenant } from '@/lib/tenant';

const LS_KEY = (tenantId: number | string) => `lastShippingLabelTemplateId:${tenantId}`;
const LS_SENDER = (tenantId: number | string) => `lastDeliverySenderId:${tenantId}`;

interface SenderProfile { id: string; name: string; phone?: string; address?: string; is_default?: boolean }

export function TemplateAliasPicker({ open, onCancel, onConfirm }: {
    open: boolean;
    onCancel: () => void;
    onConfirm: (templateId: number | null, senderId: string | null) => void;
}) {
    const { data: tenant } = useTenant();
    const { data: items = [], isLoading } = useShippingLabelTemplates();
    const preview = usePreviewShippingLabelTemplate();

    const senders = useMemo<SenderProfile[]>(
        () => (Array.isArray((tenant?.settings as { print?: { senders?: unknown } })?.print?.senders)
            ? ((tenant!.settings as { print: { senders: SenderProfile[] } }).print.senders)
            : []),
        [tenant],
    );

    const defaultId = useMemo(() => {
        if (!tenant) return null;
        const lastUsed = Number(localStorage.getItem(LS_KEY(tenant.id)) || 0);
        if (items.find((t) => t.id === lastUsed)) return lastUsed;
        return items.find((t) => t.is_default)?.id ?? null;
    }, [items, tenant]);

    const defaultSenderId = useMemo(() => {
        if (!tenant || senders.length === 0) return null;
        const last = localStorage.getItem(LS_SENDER(tenant.id));
        if (last && senders.find((s) => s.id === last)) return last;
        return senders.find((s) => s.is_default)?.id ?? senders[0]?.id ?? null;
    }, [senders, tenant]);

    const [selected, setSelected] = useState<number | null>(defaultId);
    const [senderId, setSenderId] = useState<string | null>(defaultSenderId);

    const handleConfirm = () => {
        if (tenant && selected != null) localStorage.setItem(LS_KEY(tenant.id), String(selected));
        if (tenant && senderId) localStorage.setItem(LS_SENDER(tenant.id), senderId);
        onConfirm(selected, senderId);
    };

    return (
        <Modal open={open} onCancel={onCancel} onOk={handleConfirm} okText="In" cancelText="Huỷ" title="In phiếu giao hàng">
            <Typography.Text strong style={{ fontSize: 12, color: '#888' }}>MẪU PHIẾU</Typography.Text>
            <div style={{ marginTop: 6, marginBottom: 8 }}>
                {items.length === 0 ? (
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
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
            </div>

            {senders.length > 0 && (
                <>
                    <Divider style={{ margin: '8px 0' }} />
                    <Typography.Text strong style={{ fontSize: 12, color: '#888' }}>ĐỊA CHỈ LẤY HÀNG / NGƯỜI GỬI</Typography.Text>
                    <div style={{ marginTop: 6 }}>
                        <Select
                            style={{ width: '100%' }}
                            value={senderId ?? undefined}
                            onChange={(v) => setSenderId((v as string) ?? null)}
                            optionLabelProp="label"
                            options={senders.map((s) => ({
                                value: s.id,
                                label: `${s.name}${s.phone ? ' · ' + s.phone : ''}`,
                                sender: s,
                            }))}
                            optionRender={(opt) => {
                                const s = (opt.data as { sender: SenderProfile }).sender;
                                return (
                                    <div>
                                        <div><b>{s.name}</b>{s.phone ? <span style={{ color: '#888' }}> · {s.phone}</span> : null}{s.is_default ? <Tag color="gold" style={{ marginLeft: 6 }}>Mặc định</Tag> : null}</div>
                                        {s.address ? <div style={{ fontSize: 12, color: '#888' }}>{s.address}</div> : null}
                                    </div>
                                );
                            }}
                        />
                        <Typography.Text type="secondary" style={{ fontSize: 11 }}>
                            Quản lý danh sách người gửi tại <b>Cài đặt → Mẫu in</b>.
                        </Typography.Text>
                    </div>
                </>
            )}
        </Modal>
    );
}
