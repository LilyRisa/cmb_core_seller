import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { App, Button, Card, Input, Modal, Result, Skeleton, Space, Tag, Tooltip, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined, LinkOutlined, WarningOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { DateText } from '@/components/MoneyText';
import { OrderDetailBody } from '@/components/OrderDetailBody';
import { errorMessage } from '@/lib/api';
import { useOrder, type Order } from '@/lib/orders';
import { useReportBadOrder } from '@/lib/customers';
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
                extra={(trackable || canEdit || order.can_bad_report) ? (
                    <Space>
                        <BadReportButton order={order} />
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

/**
 * SPEC 0038 v2 — nút "Báo cáo bom hàng" trên đơn thủ công đã hoàn/thất bại. Nhập lý do →
 * tạo report (theo SĐT khách). Mỗi đơn chỉ báo 1 lần ⇒ đã báo thì khoá nút.
 */
function BadReportButton({ order }: { order: Order }) {
    const canReport = useCan('customers.note');
    const { message } = App.useApp();
    const [open, setOpen] = useState(false);
    const [reason, setReason] = useState('');
    const report = useReportBadOrder();

    if (!order.can_bad_report || !canReport) return null;
    if (order.bad_reported) {
        return (
            <Tooltip title="Đơn này đã được báo cáo bom hàng">
                <Button icon={<WarningOutlined />} disabled>Đã báo cáo</Button>
            </Tooltip>
        );
    }

    const submit = async () => {
        const r = reason.trim();
        if (!r) { message.warning('Nhập lý do bom hàng.'); return; }
        try {
            await report.mutateAsync({ order_id: order.id, reason: r });
            message.success('Đã tạo báo cáo bom hàng.');
            setOpen(false);
            setReason('');
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    return (
        <>
            <Tooltip title="Báo cáo khách bom hàng cho đơn này">
                <Button danger icon={<WarningOutlined />} onClick={() => setOpen(true)}>Báo cáo bom hàng</Button>
            </Tooltip>
            <Modal
                title="Báo cáo bom hàng" open={open} onCancel={() => setOpen(false)} onOk={submit}
                confirmLoading={report.isPending} okText="Tạo báo cáo" okButtonProps={{ danger: true }}
            >
                <Typography.Paragraph type="secondary" style={{ fontSize: 13 }}>
                    Đơn {order.order_number ?? `#${order.id}`} — tạo cảnh báo bom hàng theo số điện thoại khách. Mỗi đơn chỉ báo 1 lần.
                </Typography.Paragraph>
                <Input.TextArea
                    value={reason} onChange={(e) => setReason(e.target.value)} rows={3} maxLength={255}
                    placeholder="Lý do (vd: hẹn giao nhiều lần không nhận, bom hàng…)"
                />
            </Modal>
        </>
    );
}
