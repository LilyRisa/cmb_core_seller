import { App, Button, Card, Form, Input, Radio, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { SendOutlined, NotificationOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { useAdminBroadcasts, useAdminCreateBroadcast, type AdminBroadcastRow } from '@/lib/admin';
import { errorMessage } from '@/lib/api';

export function AdminBroadcastsPage() {
    const { message } = App.useApp();
    const { data, isFetching } = useAdminBroadcasts();
    const create = useAdminCreateBroadcast();
    const [form] = Form.useForm();

    const columns: ColumnsType<AdminBroadcastRow> = [
        { title: 'ID', dataIndex: 'id', width: 64 },
        { title: 'Tiêu đề', dataIndex: 'subject' },
        {
            title: 'Audience', dataIndex: 'audience',
            render: (v: AdminBroadcastRow['audience']) => {
                if (v.kind === 'all_owners') return <Tag>Mọi chủ shop</Tag>;
                if (v.kind === 'all_admins_and_owners') return <Tag>Chủ + admin shop</Tag>;
                return <Tag>{v.tenant_ids?.length ?? 0} tenant cụ thể</Tag>;
            },
        },
        {
            title: 'Đã gửi', key: 'sent',
            render: (_, r) => `${r.sent_count}/${r.recipient_count}${r.skipped_count ? ` (skipped ${r.skipped_count})` : ''}`,
        },
        {
            title: 'Lúc', dataIndex: 'sent_at',
            render: (v: string | null) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—',
        },
    ];

    return (
        <>
            <PageHeader title="Broadcast email" subtitle="Gửi thông báo cho user của tenant — bảo trì, cập nhật, khuyến mãi..." />

            <Card title="Gửi broadcast mới" style={{ marginBottom: 24 }}>
                <Form
                    form={form}
                    layout="vertical"
                    initialValues={{ audience_kind: 'all_owners' }}
                    onFinish={(v) => {
                        const audience: { kind: string; tenant_ids?: number[] } = { kind: v.audience_kind };
                        if (v.audience_kind === 'tenant_ids') {
                            audience.tenant_ids = String(v.tenant_ids ?? '').split(',').map(s => Number(s.trim())).filter(Boolean);
                        }
                        create.mutate({ subject: v.subject, body_markdown: v.body_markdown, audience }, {
                            onSuccess: (b) => { message.success(`Đã gửi tới ${b.sent_count}/${b.recipient_count} người.`); form.resetFields(); },
                            onError: (e) => message.error(errorMessage(e, 'Gửi lỗi.')),
                        });
                    }}
                >
                    <Form.Item name="subject" label="Tiêu đề email" rules={[{ required: true, max: 255 }]}>
                        <Input placeholder="VD: Thông báo bảo trì hệ thống ngày 20/05" />
                    </Form.Item>

                    <Form.Item name="audience_kind" label="Đối tượng nhận">
                        <Radio.Group>
                            <Radio.Button value="all_owners"><NotificationOutlined /> Mọi chủ shop</Radio.Button>
                            <Radio.Button value="all_admins_and_owners">Chủ + admin shop</Radio.Button>
                            <Radio.Button value="tenant_ids">Tenant cụ thể</Radio.Button>
                        </Radio.Group>
                    </Form.Item>

                    <Form.Item shouldUpdate={(p, c) => p.audience_kind !== c.audience_kind} noStyle>
                        {({ getFieldValue }) => getFieldValue('audience_kind') === 'tenant_ids' && (
                            <Form.Item name="tenant_ids" label="Tenant IDs (cách bằng dấu phẩy)" rules={[{ required: true }]}>
                                <Input placeholder="12, 34, 56" />
                            </Form.Item>
                        )}
                    </Form.Item>

                    <Form.Item name="body_markdown" label="Nội dung (Markdown — HTML user nhập sẽ bị escape)" rules={[{ required: true, max: 50000 }]}>
                        <Input.TextArea rows={8} placeholder={'# Tiêu đề\n\nXin chào,\n\nHệ thống sẽ bảo trì lúc **22h** ngày 20/05. Vui lòng đóng giao dịch trước thời điểm này.'} />
                    </Form.Item>

                    <Button type="primary" htmlType="submit" icon={<SendOutlined />} loading={create.isPending}>
                        Gửi broadcast
                    </Button>
                    <Typography.Text type="secondary" style={{ marginLeft: 12 }}>
                        Giới hạn 5000 người/lần. Tenant suspended sẽ bị skip tự động.
                    </Typography.Text>
                </Form>
            </Card>

            <Card title="Lịch sử broadcast">
                <Table
                    rowKey="id" size="small"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isFetching}
                    pagination={{ pageSize: 30, total: data?.meta.pagination.total ?? 0, showSizeChanger: false }}
                />
            </Card>
        </>
    );
}
