import { Alert, Button, Space, Typography } from 'antd';
import { Link } from 'react-router-dom';
import { LockOutlined, WarningOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { useSubscription } from '@/lib/billing';

/**
 * SPEC 0020 — banner cảnh báo / khoá khi tenant vượt hạn mức.
 *
 * - `over_quota_warned_at != null` & chưa quá grace ⇒ orange "Còn N giờ"
 * - `over_quota_locked = true`                       ⇒ red "Đã khoá thao tác ghi"
 * - không vượt ⇒ không render gì
 *
 * Cẩn thận: hook luôn gọi (rule of hooks) nhưng chỉ render khi có dữ liệu.
 */
export function OverQuotaBanner() {
    const { data } = useSubscription();
    const sub = data?.subscription;

    if (!sub || !sub.over_quota_warned_at) {
        return null;
    }

    const warnedAt = dayjs(sub.over_quota_warned_at);
    const graceHours = sub.over_quota_grace_hours ?? 48;
    const deadline = warnedAt.add(graceHours, 'hour');
    const hoursLeft = Math.max(0, Math.ceil(deadline.diff(dayjs(), 'hour', true)));
    const locked = sub.over_quota_locked === true;

    return (
        <Alert
            type={locked ? 'error' : 'warning'}
            showIcon
            icon={locked ? <LockOutlined /> : <WarningOutlined />}
            style={{ marginBottom: 12 }}
            message={(
                <Space size={6}>
                    <Typography.Text strong>
                        {locked
                            ? 'Tài khoản đang bị khoá thao tác do vượt hạn mức gói.'
                            : `Bạn đang vượt hạn mức gói. Còn ${hoursLeft} giờ trước khi bị khoá.`}
                    </Typography.Text>
                </Space>
            )}
            description={(
                <Space direction="vertical" size={4}>
                    <Typography.Text>
                        {locked
                            ? 'Mọi thao tác tạo/sửa/xoá (đơn, SKU, kết nối kênh…) đang bị chặn. Bạn vẫn xem được dữ liệu cũ.'
                            : 'Vui lòng nâng cấp gói hoặc gỡ bớt gian hàng thừa để tiếp tục sử dụng bình thường.'}
                    </Typography.Text>
                    <Space size={8}>
                        <Link to="/settings/plan"><Button size="small" type="primary">Nâng cấp gói</Button></Link>
                        <Link to="/channels"><Button size="small">Gỡ kênh thừa</Button></Link>
                    </Space>
                </Space>
            )}
        />
    );
}
