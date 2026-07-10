import { useEffect } from 'react';
import { Alert, Descriptions, Modal, Result, Space, Spin, Typography } from 'antd';
import { CheckCircleOutlined } from '@ant-design/icons';
import { CheckoutSession, useInvoicePolling } from '@/lib/billing';

/**
 * Modal thanh toán SePay — hiện QR + số tài khoản + nội dung chuyển khoản, poll hoá đơn
 * (Task 11 `useInvoicePolling`) tới khi `status === 'paid'` rồi báo thành công.
 */

const { Paragraph, Text } = Typography;

interface Props {
    open: boolean;
    session: CheckoutSession | null;
    invoiceId: number | null;
    onClose: () => void;
    onPaid?: () => void;
}

const vnd = (n?: number) => (n ? new Intl.NumberFormat('vi-VN').format(n) + '₫' : '');

export default function CheckoutModal({ open, session, invoiceId, onClose, onPaid }: Props) {
    const invoiceQ = useInvoicePolling(invoiceId, open);
    const paid = invoiceQ.data?.status === 'paid';

    // Gọi onPaid trong useEffect (không phải trong render) để tránh side-effect-during-render warning.
    useEffect(() => {
        if (paid) onPaid?.();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [paid]);

    return (
        <Modal open={open} title="Thanh toán" footer={null} onCancel={onClose} destroyOnClose width={460}>
            {paid ? (
                <Result
                    status="success"
                    icon={<CheckCircleOutlined />}
                    title="Thanh toán thành công"
                    subTitle="Gói của bạn đã được kích hoạt."
                />
            ) : session ? (
                <Space direction="vertical" style={{ width: '100%' }} size="middle">
                    {session.qr_url && (
                        <div style={{ textAlign: 'center' }}>
                            <img src={session.qr_url} alt="QR thanh toán" style={{ maxWidth: '100%', width: 240 }} />
                        </div>
                    )}
                    <Descriptions column={1} size="small" bordered>
                        <Descriptions.Item label="Ngân hàng">{session.bank_code}</Descriptions.Item>
                        <Descriptions.Item label="Số tài khoản">
                            <Text copyable>{session.account_no}</Text>
                        </Descriptions.Item>
                        <Descriptions.Item label="Chủ tài khoản">{session.account_name}</Descriptions.Item>
                        <Descriptions.Item label="Số tiền">{vnd(session.amount)}</Descriptions.Item>
                        <Descriptions.Item label="Nội dung CK">
                            <Text copyable>{session.memo}</Text>
                        </Descriptions.Item>
                    </Descriptions>
                    <Alert
                        type="info"
                        showIcon
                        message={
                            <Space>
                                <Spin size="small" />
                                Đang chờ xác nhận chuyển khoản… Gói sẽ tự kích hoạt sau khi nhận tiền.
                            </Space>
                        }
                    />
                    <Paragraph type="secondary" style={{ fontSize: 12 }}>
                        Chuyển khoản đúng nội dung <Text strong>{session.memo}</Text> để hệ thống tự đối soát.
                    </Paragraph>
                </Space>
            ) : null}
        </Modal>
    );
}
