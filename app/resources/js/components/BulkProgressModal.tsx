import type { ReactNode } from 'react';
import { Modal, Progress, List, Tag, Space, Typography, Button } from 'antd';
import { CheckCircleTwoTone, MinusCircleTwoTone, CloseCircleTwoTone, LoadingOutlined, ClockCircleOutlined, SyncOutlined } from '@ant-design/icons';
import type { BulkItem } from '@/lib/useBulkAction';

const ICON: Record<BulkItem['status'], ReactNode> = {
    pending: <ClockCircleOutlined style={{ color: '#bfbfbf' }} />,
    running: <LoadingOutlined style={{ color: '#1677ff' }} />,
    // queued = đã nhận, đang chuẩn bị nền (job PrepareShipment) — kết quả thật hiện qua polling chip/slip.
    queued: <SyncOutlined spin style={{ color: '#1677ff' }} />,
    ok: <CheckCircleTwoTone twoToneColor="#52c41a" />,
    skipped: <MinusCircleTwoTone twoToneColor="#faad14" />,
    error: <CloseCircleTwoTone twoToneColor="#ff4d4f" />,
};

export function BulkProgressModal({ title, open, items, running, onRetry, onClose }: {
    title: string; open: boolean; items: BulkItem[]; running: boolean;
    onRetry: () => void; onClose: () => void;
}) {
    const done = items.filter((i) => i.status !== 'pending' && i.status !== 'running').length;
    const ok = items.filter((i) => i.status === 'ok').length;
    const skipped = items.filter((i) => i.status === 'skipped').length;
    const errors = items.filter((i) => i.status === 'error').length;
    const pct = items.length ? Math.round((done / items.length) * 100) : 0;

    // Cho phép ẩn cửa sổ KỂ CẢ khi đang chạy — quá trình chạy nền (hook useBulkAction không phụ thuộc trạng
    // thái mở modal), danh sách tự cập nhật khi xong ⇒ user không bị kẹt, tiếp tục thao tác/quét đơn khác được.
    return (
        <Modal title={`${title} — ${done}/${items.length}`} open={open} onCancel={onClose}
            maskClosable closable width={640}
            footer={[
                <Space key="sum" style={{ marginRight: 'auto' }}>
                    <Tag color="success">Thành công {ok}</Tag>
                    <Tag color="warning">Bỏ qua {skipped}</Tag>
                    <Tag color="error">Lỗi {errors}</Tag>
                </Space>,
                ...(running
                    ? [<Button key="hide" onClick={onClose}>Ẩn — tiếp tục chạy nền</Button>]
                    : [
                        <Button key="retry" onClick={onRetry} disabled={errors === 0}>Thử lại đơn lỗi</Button>,
                        <Button key="close" type="primary" onClick={onClose}>Đóng</Button>,
                    ]),
            ]}>
            {running && <Typography.Text type="secondary" style={{ display: 'block', marginBottom: 8, fontSize: 12 }}>Có thể ẩn cửa sổ này để tiếp tục thao tác — quá trình vẫn chạy nền, danh sách tự cập nhật khi xong.</Typography.Text>}
            <Progress percent={pct} status={running ? 'active' : errors ? 'exception' : 'success'} />
            <List size="small" style={{ maxHeight: 360, overflow: 'auto', marginTop: 12 }} dataSource={items}
                renderItem={(it) => (
                    <List.Item>
                        <Space direction="vertical" size={0} style={{ width: '100%' }}>
                            <Space>
                                {ICON[it.status]}
                                <Typography.Text strong>#{it.label}</Typography.Text>
                                {it.sub && <Typography.Text type="secondary">{it.sub}</Typography.Text>}
                                {it.reason && <Typography.Text type={it.status === 'error' ? 'danger' : 'secondary'}>— {it.reason}</Typography.Text>}
                            </Space>
                            {it.technical && <Typography.Text code style={{ fontSize: 11 }}>{it.technical}</Typography.Text>}
                        </Space>
                    </List.Item>
                )} />
        </Modal>
    );
}
