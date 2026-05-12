import { Link } from 'react-router-dom';
import { Alert, Button, Card, Space, Tag, Typography } from 'antd';
import { UserOutlined } from '@ant-design/icons';
import { ReputationBadge } from '@/components/ReputationBadge';
import type { CustomerCard } from '@/lib/customers';

/**
 * The "Khách hàng" card shown on the order detail page (SPEC 0002 §3.1) — gives
 * the staffer the buyer's history *before* confirming the order.
 */
export function CustomerSummaryCard({ customer }: { customer?: CustomerCard | null }) {
    if (!customer) {
        return (
            <Card title="Khách hàng" size="small" style={{ marginBottom: 16 }}>
                <Typography.Text type="secondary">Không xác định được — SĐT người mua đã ẩn.</Typography.Text>
            </Card>
        );
    }
    if (customer.is_anonymized) {
        return (
            <Card title="Khách hàng" size="small" style={{ marginBottom: 16 }}>
                <Typography.Text type="secondary">Hồ sơ đã ẩn danh theo yêu cầu xoá dữ liệu — không thể xem chi tiết.</Typography.Text>
            </Card>
        );
    }

    const s = customer.lifetime_stats;
    const warn = customer.latest_warning_note;

    return (
        <Card
            title="Khách hàng" size="small" style={{ marginBottom: 16 }}
            extra={<Link to={`/customers/${customer.id}`}>Xem hồ sơ</Link>}
        >
            <Space direction="vertical" size={8} style={{ width: '100%' }}>
                <Space wrap>
                    <UserOutlined />
                    <Typography.Text strong>{customer.name ?? 'Khách lẻ'}</Typography.Text>
                    <Typography.Text type="secondary">{customer.phone_masked ?? ''}</Typography.Text>
                    <ReputationBadge label={customer.is_blocked ? 'blocked' : customer.reputation.label} score={customer.reputation.score} showOk size="small" />
                    {customer.tags?.map((t) => <Tag key={t} color={t === 'vip' ? 'purple' : 'blue'}>{t}</Tag>)}
                </Space>

                {customer.is_blocked && <Alert type="error" showIcon banner message="Khách này đã bị chặn." />}
                {warn && <Alert type={warn.severity === 'danger' ? 'error' : 'warning'} showIcon banner message={warn.note} />}

                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                    Tổng {s.orders_total} đơn · {s.orders_completed} hoàn thành · {s.orders_cancelled} huỷ
                    {s.orders_returned ? ` · ${s.orders_returned} hoàn` : ''}{s.orders_delivery_failed ? ` · ${s.orders_delivery_failed} giao hỏng` : ''}
                </Typography.Text>

                {customer.manual_note && (
                    <Typography.Paragraph style={{ marginBottom: 0, background: '#fffbe6', padding: '6px 8px', borderRadius: 4, fontSize: 13 }}>
                        📝 {customer.manual_note}
                    </Typography.Paragraph>
                )}

                <Button type="link" size="small" style={{ padding: 0 }}>
                    <Link to={`/customers/${customer.id}`}>+ Thêm ghi chú / xem lịch sử</Link>
                </Button>
            </Space>
        </Card>
    );
}
