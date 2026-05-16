import { Link } from 'react-router-dom';
import { Button, Modal, Result, Skeleton, Space, Tag } from 'antd';
import { ExportOutlined } from '@ant-design/icons';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { OrderDetailBody } from '@/components/OrderDetailBody';
import { errorMessage } from '@/lib/api';
import { useOrder } from '@/lib/orders';

/** Quick-view of an order in a modal (opened from the orders list "Xem" action). */
export function OrderDetailModal({ orderId, open, onClose }: { orderId: number | null; open: boolean; onClose: () => void }) {
    const { data: order, isLoading, isError, error } = useOrder(open && orderId ? orderId : undefined);

    return (
        <Modal
            open={open} onCancel={onClose} width={1000} footer={null} destroyOnClose
            title={order
                ? <Space size="middle" wrap>
                    <span>Đơn {order.order_number ?? order.external_order_id ?? `#${order.id}`}</span>
                    <ChannelBadge provider={order.source} />
                    {order.channel_account?.name && (
                        <Tag style={{ display: 'inline-flex', alignItems: 'center', gap: 4, paddingInline: 6 }}>
                            <ChannelLogo provider={order.channel_account.provider ?? order.source} size={12} />
                            <span>{order.channel_account.name}</span>
                        </Tag>
                    )}
                    <StatusTag status={order.status} label={order.status_label} rawStatus={order.raw_status} />
                    {order.is_cod && <Tag color="gold">COD</Tag>}
                    <Link to={`/orders/${order.id}`} onClick={onClose}><Button type="link" size="small" icon={<ExportOutlined />}>Mở trang đầy đủ</Button></Link>
                </Space>
                : 'Chi tiết đơn'}
        >
            {isError ? <Result status="error" title="Không tải được đơn hàng" subTitle={errorMessage(error)} />
                : isLoading || !order ? <Skeleton active paragraph={{ rows: 8 }} />
                    : <OrderDetailBody order={order} />}
        </Modal>
    );
}
