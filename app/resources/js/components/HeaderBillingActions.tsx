import { useState } from 'react';
import { Link } from 'react-router-dom';
import { App as AntApp, Button, Input, Modal, Space, Tag, Tooltip, Typography } from 'antd';
import { CrownOutlined, GiftOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useSubscription, useValidateVoucher } from '@/lib/billing';

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
    const validate = useValidateVoucher();
    const [code, setCode] = useState('');

    const check = () => {
        const c = code.trim().toUpperCase();
        if (!c) return;
        validate.mutate({ code: c }, {
            onSuccess: (v) => {
                if (v.valid) {
                    message.success(v.discount_amount
                        ? `Mã hợp lệ — giảm ${v.discount_amount.toLocaleString('vi-VN')}đ. Áp dụng khi thanh toán ở trang gói.`
                        : (v.message ?? 'Mã hợp lệ. Áp dụng khi thanh toán ở trang gói.'));
                } else {
                    message.warning(v.message ?? 'Mã không hợp lệ hoặc đã hết lượt.');
                }
            },
            onError: (e) => message.error(errorMessage(e, 'Không kiểm tra được mã.')),
        });
    };

    return (
        <Modal title="Nhập mã giảm giá" open={open} onCancel={onClose} okText="Kiểm tra"
            confirmLoading={validate.isPending} onOk={check}>
            <Text type="secondary">Nhập mã để kiểm tra ưu đãi. Mã sẽ được áp dụng khi thanh toán ở trang gói.</Text>
            <Input
                style={{ marginTop: 12, textTransform: 'uppercase' }}
                placeholder="VD: SALE50"
                value={code}
                onChange={(e) => setCode(e.target.value)}
                onPressEnter={check}
                allowClear
            />
        </Modal>
    );
}
