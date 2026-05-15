import { useMemo, useState } from 'react';
import { Alert, Empty, Modal, Radio, Space, Spin, Tag, Typography } from 'antd';
import { CarOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import { CARRIER_META } from '@/components/CarrierBadge';
import { useCarrierAccounts, type CarrierAccount } from '@/lib/fulfillment';

/**
 * SPEC 0021 — popup chọn ĐVVC khi "Chuẩn bị hàng" cho đơn manual.
 *
 * Theo yêu cầu: chọn đơn thủ công + ấn "Chuẩn bị hàng" ⇒ modal liệt kê ĐVVC; chọn + xác nhận mới đẩy
 * sang ĐVVC (GHN createOrder hoặc manual no-op). Đơn sàn KHÔNG dùng modal này — flow `prepareChannelOrder`
 * tự đẩy lên sàn lấy tem/AWB.
 *
 * Default account đứng đầu danh sách (is_default=true). Chỉ liệt kê ĐVVC active.
 */
export function CarrierAccountPicker({ open, count, onConfirm, onCancel, loading }: {
    open: boolean;
    /** Số đơn manual sắp chuẩn bị (badge tiêu đề). */
    count: number;
    onConfirm: (carrierAccountId: number | null) => void;
    onCancel: () => void;
    loading?: boolean;
}) {
    const { data: accounts = [], isFetching } = useCarrierAccounts();
    const active = useMemo(() => accounts.filter((a) => a.is_active), [accounts]);
    const sorted = useMemo(() => [...active].sort((a, b) => Number(b.is_default) - Number(a.is_default) || a.id - b.id), [active]);
    const defaultId = sorted.find((a) => a.is_default)?.id ?? sorted[0]?.id ?? null;
    const [selected, setSelected] = useState<number | null>(defaultId);
    // re-sync default khi accounts vừa load xong
    if (selected == null && defaultId != null) setSelected(defaultId);

    const chosen = sorted.find((a) => a.id === selected) ?? null;

    return (
        <Modal
            open={open} onCancel={onCancel} width={560} maskClosable={!loading}
            title={`Chọn đơn vị vận chuyển cho ${count} đơn thủ công`}
            okText="Xác nhận & chuẩn bị hàng" cancelText="Huỷ"
            okButtonProps={{ disabled: !chosen, loading }}
            onOk={() => onConfirm(chosen?.id ?? null)}
        >
            {isFetching && sorted.length === 0 ? (
                <div style={{ padding: 32, textAlign: 'center' }}><Spin /></div>
            ) : sorted.length === 0 ? (
                <Empty description={(
                    <Space direction="vertical">
                        <span>Chưa có đơn vị vận chuyển nào được bật.</span>
                        <Link to="/settings/carriers">Mở Cài đặt → Đơn vị vận chuyển</Link>
                    </Space>
                )} />
            ) : (
                <>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 0 }}>
                        Đơn thủ công cần chọn ĐVVC trước khi chuẩn bị hàng. Hệ thống sẽ đẩy đơn lên ĐVVC (vd GHN
                        `createOrder` để lấy mã vận đơn), in phiếu giao hàng, rồi sang trạng thái "Đang xử lý".
                    </Typography.Paragraph>
                    <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)} style={{ width: '100%' }}>
                        <div style={{ maxHeight: 360, overflowY: 'auto' }}>
                            {sorted.map((a) => <CarrierRow key={a.id} account={a} />)}
                        </div>
                    </Radio.Group>
                    {chosen && chosen.carrier === 'ghn' && !((chosen.meta as { from_address?: Record<string, unknown> })?.from_address?.district_id) && (
                        <Alert style={{ marginTop: 12 }} type="warning" showIcon
                            message="GHN cần địa chỉ kho hàng (Tỉnh / Quận / Phường + mã district_id, ward_code)."
                            description={<Link to="/settings/carriers">Mở Cài đặt → Đơn vị vận chuyển để bổ sung địa chỉ kho hàng.</Link>}
                        />
                    )}
                </>
            )}
        </Modal>
    );
}

function CarrierRow({ account }: { account: CarrierAccount }) {
    const meta = CARRIER_META[account.carrier.toLowerCase()] ?? { name: account.carrier, color: 'default' };
    return (
        <Radio value={account.id} style={{ display: 'flex', padding: '10px 12px', borderBottom: '1px solid #fafafa', alignItems: 'flex-start' }}>
            <Space direction="vertical" size={2} style={{ marginInlineStart: 4 }}>
                <Space size={6} wrap>
                    <Tag color={meta.color} icon={<CarOutlined />} style={{ marginInlineEnd: 0 }}>{meta.name}</Tag>
                    <Typography.Text strong>{account.name}</Typography.Text>
                    {account.is_default && <Tag color="blue" style={{ marginInlineEnd: 0 }}>Mặc định</Tag>}
                </Space>
                {account.default_service && <Typography.Text type="secondary" style={{ fontSize: 12 }}>Dịch vụ mặc định: {account.default_service}</Typography.Text>}
            </Space>
        </Radio>
    );
}
