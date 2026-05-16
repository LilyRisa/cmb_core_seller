import { Link, useParams } from 'react-router-dom';
import { Button, Card, Result, Skeleton, Space, Tag } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { DateText } from '@/components/MoneyText';
import { OrderDetailBody } from '@/components/OrderDetailBody';
import { errorMessage } from '@/lib/api';
import { useOrder } from '@/lib/orders';

export function OrderDetailPage() {
    const { id } = useParams();
    const { data: order, isLoading, isError, error } = useOrder(id);

    if (isError) return <Result status="error" title="Không tải được đơn hàng" subTitle={errorMessage(error)} extra={<Link to="/orders"><Button>Về danh sách đơn</Button></Link>} />;
    if (isLoading || !order) return <Card><Skeleton active paragraph={{ rows: 8 }} /></Card>;

    return (
        <div>
            <PageHeader
                title={<Space size="middle">
                    <Link to="/orders"><Button type="text" icon={<ArrowLeftOutlined />} /></Link>
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
                </Space>}
                subtitle={<>Đặt lúc <DateText value={order.placed_at} /> · cập nhật <DateText value={order.created_at} /></>}
            />
            <OrderDetailBody order={order} />
        </div>
    );
}
