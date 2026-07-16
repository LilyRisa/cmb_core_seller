import { useState } from 'react';
import { Link } from 'react-router-dom';
import { App as AntApp, Button, Input, Modal, Space, Tag, Tooltip, Typography } from 'antd';
import { CrownOutlined, GiftOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { useRedeemVoucher, useSubscription } from '@/lib/billing';

const { Text } = Typography;

/** Header: bộ đếm lượt AI + nút mua/gia hạn gói + nút nhập voucher (SPEC 0032). */
export function HeaderBillingActions() {
    const { data } = useSubscription();
    const ai = data?.usage?.ai_credits;
    const [voucherOpen, setVoucherOpen] = useState(false);

    return (
        <Space size={8}>
            {ai?.enabled && (() => {
                const total = ai.monthly_allowance + ai.purchased_balance;
                const used = Math.max(0, total - (ai.available ?? total));
                return (
                    <Tooltip
                        title={ai.unlimited
                            ? 'Lượt gọi AI không giới hạn'
                            : `Đã gọi ${used}/${total} lượt AI kỳ này · tặng ${ai.monthly_allowance} + đã mua ${ai.purchased_balance} · còn ${ai.available ?? 0}`}
                    >
                        <Tag icon={<ThunderboltOutlined />} color="purple" style={{ margin: 0 }}>
                            {ai.unlimited ? 'Lượt AI ∞' : `Lượt AI ${used}/${total}`}
                        </Tag>
                    </Tooltip>
                );
            })()}

            <Link to="/plans">
                <Button size="small" type="primary" ghost icon={<CrownOutlined />}>Mua / Gia hạn gói</Button>
            </Link>

            <Tooltip title="Nhập mã giảm giá">
                <Button type="text" icon={<GiftOutlined />} onClick={() => setVoucherOpen(true)} />
            </Tooltip>

            <VoucherModal open={voucherOpen} onClose={() => setVoucherOpen(false)} />
        </Space>
    );
}

function VoucherModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const redeem = useRedeemVoucher();
    const [code, setCode] = useState('');

    const submit = () => {
        const c = code.trim().toUpperCase();
        if (!c) return;
        redeem.mutate(c, {
            onSuccess: (r) => {
                const msg = r.kind === 'ai_credits'
                    ? `Đã nhận ${r.granted} lượt AI! (ví: ${r.balance} lượt đã mua/tặng)`
                    : r.kind === 'free_days'
                        ? `Đã gia hạn thêm ${r.days} ngày! Hết hạn mới: ${formatDate(r.new_period_end)}`
                        : `Đã nâng lên gói ${r.plan_name} — dùng thử ${r.days} ngày!`;
                message.success(msg);
                setCode('');
                onClose();
            },
            onError: (e) => message.error(errorMessage(e, 'Mã không hợp lệ. Mã GIẢM GIÁ hãy áp ở trang gói khi thanh toán.')),
        });
    };

    return (
        <Modal title="Nhập mã ưu đãi" open={open} onCancel={onClose} okText="Áp dụng"
            confirmLoading={redeem.isPending} onOk={submit}>
            <Text type="secondary">Nhập mã <b>tặng lượt AI</b>, <b>tặng ngày</b> hoặc <b>tặng gói</b> để áp dụng ngay. Mã giảm giá gói hãy áp ở trang gói khi thanh toán.</Text>
            <Input
                style={{ marginTop: 12, textTransform: 'uppercase' }}
                placeholder="VD: AI500"
                value={code}
                onChange={(e) => setCode(e.target.value)}
                onPressEnter={submit}
                allowClear
            />
        </Modal>
    );
}
