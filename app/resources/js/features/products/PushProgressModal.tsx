import { List, Modal, Progress, Tag, Typography } from 'antd';
import {
    CheckCircleTwoTone,
    ClockCircleOutlined,
    CloseCircleTwoTone,
    LoadingOutlined,
} from '@ant-design/icons';
import { usePushBatch } from './hooks';
import type { PushJob } from './api';

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

export function PushProgressModal({
    batchId,
    open,
    onClose,
}: {
    batchId: number | null;
    open: boolean;
    onClose: () => void;
}) {
    const { data: batch } = usePushBatch(open ? batchId : null);

    const done = batch?.status === 'done';
    const total = batch?.total ?? 0;
    const finished = (batch?.succeeded ?? 0) + (batch?.failed ?? 0);
    const percent = total > 0 ? Math.round((finished / total) * 100) : 0;

    return (
        <Modal
            title="Đẩy sản phẩm lên sàn"
            open={open}
            onCancel={done ? onClose : undefined}
            maskClosable={done}
            closable={done}
            keyboard={done}
            footer={null}
        >
            <Progress
                percent={percent}
                status={batch?.failed ? 'exception' : done ? 'success' : 'active'}
            />
            <Typography.Paragraph type="secondary" style={{ marginTop: 8 }}>
                {done
                    ? `Hoàn tất: ${batch?.succeeded ?? 0} thành công, ${batch?.failed ?? 0} lỗi.`
                    : `Đang xử lý ${finished}/${total}… vui lòng không đóng cửa sổ.`}
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
