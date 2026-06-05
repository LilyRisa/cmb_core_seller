import { Link, useParams } from 'react-router-dom';
import { App, Button, Card, Result, Skeleton, Space, Tag, Tooltip } from 'antd';
import { ArrowLeftOutlined, EditOutlined, LinkOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { DateText } from '@/components/MoneyText';
import { OrderDetailBody } from '@/components/OrderDetailBody';
import { errorMessage } from '@/lib/api';
import { useOrder } from '@/lib/orders';
import { useCan } from '@/lib/tenant';
import { publicTrackingUrl } from '@/lib/publicTracking';

export function OrderDetailPage() {
    const { id } = useParams();
    const { data: order, isLoading, isError, error } = useOrder(id);
    const canUpdate = useCan('orders.update');
    const { message } = App.useApp();

    if (isError) return <Result status="error" title="Không tải được đơn hàng" subTitle={errorMessage(error)} extra={<Link to="/orders"><Button>Về danh sách đơn</Button></Link>} />;
    if (isLoading || !order) return <Card><Skeleton active paragraph={{ rows: 8 }} /></Card>;

    // SPEC 2026-05-17 — nút "Sửa đơn" chỉ cho đơn manual chưa terminal. Đơn từ sàn (Shopee/TikTok…)
    // đồng bộ 2 chiều ⇒ không sửa local; đơn đã giao xong / đã hoàn cũng không sửa (terminal).
    const canEdit = canUpdate && order.source === 'manual' && !order.is_terminal;

    // SPEC 0030 — link tra cứu công khai chỉ cho đơn tự tạo (manual) có mã đơn.
    const trackable = order.source === 'manual' && !!order.order_number;
    const copyTrackingLink = async () => {
        const url = publicTrackingUrl(order.order_number as string);
        try {
            await navigator.clipboard.writeText(url);
            message.success('Đã copy link tra cứu — gửi cho khách để theo dõi đơn.');
        } catch {
            message.warning(url);
        }
    };

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
                extra={(trackable || canEdit) ? (
                    <Space>
                        {trackable && (
                            <Tooltip title="Copy link công khai để khách tự tra cứu hành trình (đã ẩn SĐT & địa chỉ chi tiết)">
                                <Button icon={<LinkOutlined />} onClick={copyTrackingLink}>Link tra cứu</Button>
                            </Tooltip>
                        )}
                        {canEdit && (
                            <Tooltip title={order.is_pushed_to_carrier ? 'Đơn đã đẩy ĐVVC — chỉnh sửa chỉ áp dụng local' : 'Sửa sản phẩm / địa chỉ / thanh toán'}>
                                <Link to={`/orders/${order.id}/edit`}>
                                    <Button type="primary" icon={<EditOutlined />}>Sửa đơn</Button>
                                </Link>
                            </Tooltip>
                        )}
                    </Space>
                ) : undefined}
            />
            <OrderDetailBody order={order} />
        </div>
    );
}
