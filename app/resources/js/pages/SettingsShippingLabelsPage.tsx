import { App as AntApp, Button, Card, Modal, Space, Table, Tag, Tooltip, Typography } from 'antd';
import { CopyOutlined, DeleteOutlined, EditOutlined, PlusOutlined, StarFilled, StarOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { useShippingLabelTemplates, useDeleteShippingLabelTemplate, useSetDefaultShippingLabelTemplate, useDuplicateShippingLabelTemplate } from '@/lib/shippingLabels';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';
import type { Template } from '@/lib/shippingLabelTypes';

export function SettingsShippingLabelsPage() {
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const canManage = useCan('tenant.settings');
    const { data: items = [], isLoading } = useShippingLabelTemplates();
    const del = useDeleteShippingLabelTemplate();
    const setDefault = useSetDefaultShippingLabelTemplate();
    const duplicate = useDuplicateShippingLabelTemplate();

    const onDelete = (t: Template) => {
        Modal.confirm({
            title: `Xoá template "${t.name}"?`,
            content: 'Template đã xoá sẽ không dùng được khi in nữa. Phiếu in đã render trước đó vẫn xem lại được.',
            okType: 'danger', okText: 'Xoá',
            onOk: () => del.mutateAsync(t.id)
                .then(() => message.success('Đã xoá'))
                .catch((e) => message.error(errorMessage(e))),
        });
    };

    const columns = [
        { title: 'Tên', dataIndex: 'name', render: (n: string, t: Template) => (
            <Space>
                <a onClick={() => navigate(`/settings/shipping-labels/${t.id}`)}>{n}</a>
                {t.is_default && <Tag color="gold" icon={<StarFilled />}>Mặc định</Tag>}
            </Space>
        ) },
        { title: 'Khổ giấy', dataIndex: 'paper', render: (p: string, t: Template) => `${p} (${t.paper_w_mm}×${t.paper_h_mm || 'auto'}mm)` },
        { title: 'Cập nhật', dataIndex: 'updated_at', render: (v: string) => new Date(v).toLocaleString('vi-VN') },
        { title: '', key: 'actions', width: 180, render: (_: unknown, t: Template) => (
            <Space size={2}>
                <Tooltip title="Sửa"><Button type="text" icon={<EditOutlined />} onClick={() => navigate(`/settings/shipping-labels/${t.id}`)} /></Tooltip>
                {canManage && <Tooltip title={t.is_default ? 'Đang là mặc định' : 'Đặt mặc định'}>
                    <Button type="text" icon={t.is_default ? <StarFilled style={{ color: '#faad14' }} /> : <StarOutlined />}
                        disabled={t.is_default} onClick={() => setDefault.mutate(t.id)} />
                </Tooltip>}
                {canManage && <Tooltip title="Nhân bản"><Button type="text" icon={<CopyOutlined />} onClick={() => duplicate.mutate(t.id)} /></Tooltip>}
                {canManage && <Tooltip title="Xoá"><Button type="text" danger icon={<DeleteOutlined />} onClick={() => onDelete(t)} /></Tooltip>}
            </Space>
        ) },
    ];

    return (
        <Card title={<Space><Typography.Title level={4} style={{ margin: 0 }}>Mẫu phiếu giao hàng</Typography.Title></Space>}
              extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/settings/shipping-labels/new')}>Tạo template</Button>}>
            <Typography.Paragraph type="secondary">
                Thiết kế template phiếu giao hàng cho <b>đơn manual</b> theo khổ giấy của bạn. Khi in,
                nhân viên chọn template từ danh sách này. Đơn của sàn TMĐT vẫn dùng AWB thật của sàn.
            </Typography.Paragraph>
            <Table rowKey="id" loading={isLoading} dataSource={items} columns={columns} pagination={false} />
        </Card>
    );
}
