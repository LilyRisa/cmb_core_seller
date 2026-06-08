import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Badge, Button, Empty, List, Popover, Spin, Tooltip, Typography } from 'antd';
import {
    BellOutlined,
    CheckOutlined,
    ExclamationCircleTwoTone,
    InfoCircleTwoTone,
    WarningTwoTone,
} from '@ant-design/icons';
import {
    useMarkAllNotificationsRead,
    useMarkNotificationRead,
    useNotifications,
    type AppNotification,
    type NotificationLevel,
} from '@/lib/notifications';

/** Icon theo mức độ (font icon @ant-design/icons — không dùng emoji). */
function levelIcon(level: NotificationLevel) {
    if (level === 'critical') return <ExclamationCircleTwoTone twoToneColor="#ff4d4f" />;
    if (level === 'warning') return <WarningTwoTone twoToneColor="#faad14" />;
    return <InfoCircleTwoTone twoToneColor="#1677ff" />;
}

/** Thời gian tương đối ngắn gọn tiếng Việt. */
function timeAgo(iso: string | null): string {
    if (!iso) return '';
    const diff = Date.now() - new Date(iso).getTime();
    const m = Math.floor(diff / 60_000);
    if (m < 1) return 'vừa xong';
    if (m < 60) return `${m} phút trước`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h} giờ trước`;
    const d = Math.floor(h / 24);
    return `${d} ngày trước`;
}

/**
 * Chuông thông báo in-app (SPEC 0036) — Badge số chưa đọc + Popover danh sách. Click 1
 * mục → đánh dấu đã đọc + điều hướng `action_url`. Realtime do `useNotificationsRealtime`
 * (mount ở AppLayout) lo; component này chỉ đọc cache + thao tác đọc.
 */
export function NotificationBell() {
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const { data, isLoading } = useNotifications();
    const markRead = useMarkNotificationRead();
    const markAll = useMarkAllNotificationsRead();

    const items = data?.data ?? [];
    const unread = data?.meta.unread_count ?? 0;

    const onClickItem = (n: AppNotification) => {
        if (!n.is_read) markRead.mutate(n.id);
        setOpen(false);
        if (n.action_url) navigate(n.action_url);
    };

    const content = (
        <div style={{ width: 360, maxWidth: '90vw' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '4px 4px 8px' }}>
                <Typography.Text strong>Thông báo</Typography.Text>
                <Button
                    type="link" size="small" icon={<CheckOutlined />}
                    disabled={unread === 0 || markAll.isPending}
                    onClick={() => markAll.mutate()}
                >
                    Đọc tất cả
                </Button>
            </div>
            {isLoading ? (
                <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
            ) : items.length === 0 ? (
                <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Chưa có thông báo" style={{ padding: 16 }} />
            ) : (
                <List
                    size="small"
                    style={{ maxHeight: 420, overflowY: 'auto' }}
                    dataSource={items}
                    renderItem={(n) => (
                        <List.Item
                            onClick={() => onClickItem(n)}
                            style={{ cursor: 'pointer', alignItems: 'flex-start', background: n.is_read ? undefined : '#f0f7ff', padding: '8px 10px', borderRadius: 6 }}
                        >
                            <List.Item.Meta
                                avatar={levelIcon(n.level)}
                                title={<Typography.Text strong={!n.is_read} style={{ fontSize: 13 }}>{n.title}</Typography.Text>}
                                description={
                                    <span style={{ fontSize: 12, color: '#64748b' }}>
                                        {n.body ? <div>{n.body}</div> : null}
                                        <span>{timeAgo(n.created_at)}</span>
                                    </span>
                                }
                            />
                        </List.Item>
                    )}
                />
            )}
        </div>
    );

    return (
        <Popover content={content} trigger="click" open={open} onOpenChange={setOpen} placement="bottomRight">
            <Tooltip title="Thông báo">
                <Badge count={unread} size="small" overflowCount={99}>
                    <Button type="text" icon={<BellOutlined />} />
                </Badge>
            </Tooltip>
        </Popover>
    );
}
