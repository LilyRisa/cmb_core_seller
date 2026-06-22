import { Button, List, Modal, Progress, Tag, Typography } from 'antd';
import {
    CheckCircleTwoTone,
    ClockCircleOutlined,
    CloseCircleTwoTone,
    LoadingOutlined,
    MinusOutlined,
} from '@ant-design/icons';
import type { PushBatch, PushJob } from './api';

function JobStatusIcon({ status }: { status: PushJob['status'] }) {
    switch (status) {
        case 'running':
            return <LoadingOutlined style={{ color: '#2563EB' }} />;
        case 'success':
            return <CheckCircleTwoTone twoToneColor="#10B981" />;
        case 'failed':
            return <CloseCircleTwoTone twoToneColor="#EF4444" />;
        default:
            return <ClockCircleOutlined style={{ color: '#94A3B8' }} />;
    }
}

const STATUS_LABEL: Record<PushJob['status'], string> = {
    queued: 'Đang chờ',
    running: 'Đang đẩy',
    success: 'Thành công',
    failed: 'Thất bại',
};

/**
 * Tiến trình đẩy sản phẩm lên sàn. Việc đẩy chạy nền ở worker nên cửa sổ này CÓ THỂ
 * ẩn (`onHide`) bất cứ lúc nào — đẩy vẫn tiếp tục, người dùng mở lại để xem log trạng
 * thái. Khi xong mới `onClose` (xác nhận, dọn trạng thái). Component thuần hiển thị:
 * dữ liệu batch + polling do trang cha quản lý để chỉ báo thu nhỏ luôn cập nhật.
 */
export function PushProgressModal({
    batch,
    open,
    onHide,
    onClose,
}: {
    batch: PushBatch | undefined;
    open: boolean;
    /** Ẩn cửa sổ nhưng GIỮ tiến trình (worker vẫn chạy). */
    onHide: () => void;
    /** Đóng hẳn: tiến trình đã xong / người dùng xác nhận xong. */
    onClose: () => void;
}) {
    const done = batch?.status === 'done';
    const total = batch?.total ?? 0;
    const finished = (batch?.succeeded ?? 0) + (batch?.failed ?? 0);
    const percent = total > 0 ? Math.round((finished / total) * 100) : 0;

    return (
        <Modal
            title="Đẩy sản phẩm lên sàn"
            open={open}
            // X / nền: khi chưa xong thì ẩn (không hủy), khi xong thì đóng hẳn.
            onCancel={done ? onClose : onHide}
            maskClosable
            closable
            keyboard
            footer={
                done ? (
                    <Button type="primary" onClick={onClose}>
                        Đóng
                    </Button>
                ) : (
                    <Button icon={<MinusOutlined />} onClick={onHide}>
                        Ẩn cửa sổ
                    </Button>
                )
            }
        >
            <Progress
                percent={percent}
                status={batch?.failed ? 'exception' : done ? 'success' : 'active'}
            />
            <Typography.Paragraph type="secondary" style={{ marginTop: 8 }}>
                {done
                    ? `Hoàn tất: ${batch?.succeeded ?? 0} thành công, ${batch?.failed ?? 0} lỗi.`
                    : `Đang xử lý ${finished}/${total}… Bạn có thể ẩn cửa sổ — hệ thống vẫn tiếp tục đẩy ở chế độ nền.`}
            </Typography.Paragraph>

            <List
                size="small"
                dataSource={batch?.jobs ?? []}
                rowKey={(j) => j.listing_id}
                renderItem={(job) => (
                    <List.Item>
                        <List.Item.Meta
                            avatar={<JobStatusIcon status={job.status} />}
                            title={
                                <span>
                                    Listing #{job.listing_id}{' '}
                                    <Tag>{STATUS_LABEL[job.status]}</Tag>
                                </span>
                            }
                            description={
                                <>
                                    {job.step_label && (
                                        <Typography.Text type="secondary">{job.step_label}</Typography.Text>
                                    )}
                                    {job.status === 'failed' && job.error && (
                                        <div style={{ color: '#EF4444', marginTop: 2 }}>{job.error}</div>
                                    )}
                                </>
                            }
                        />
                    </List.Item>
                )}
            />
        </Modal>
    );
}
