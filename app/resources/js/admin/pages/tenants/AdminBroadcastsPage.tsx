import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { App, Button, Card, Form, Input, Radio, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { SendOutlined, NotificationOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { formatDate } from '@/lib/format';
import { useAdminBroadcasts, useAdminCreateBroadcast, type AdminBroadcastRow, type AdminTenantSummary } from '@admin/lib/admin';
import { TenantPicker, type TenantPickerOption } from '@admin/components/TenantPicker';
import { errorMessage } from '@/lib/api';

export function AdminBroadcastsPage() {
    const { message } = App.useApp();
    const location = useLocation();
    const navigate = useNavigate();
    const [page, setPage] = useState(1);
    const { data, isFetching } = useAdminBroadcasts({ page });
    const create = useAdminCreateBroadcast();
    const [form] = Form.useForm();
    const [presetTenants, setPresetTenants] = useState<AdminTenantSummary[]>([]);

    // Lối tắt từ AdminTenantsPage: chọn nhiều dòng tenant (checkbox) rồi bấm "Gửi broadcast cho N
    // tenant đã chọn" → điều hướng sang đây kèm `state.presetTenants` (mảng AdminTenantSummary đầy
    // đủ, không chỉ ID — để TenantPicker hiển thị đúng tên ngay, xem TenantPicker.tsx). Đây là lối
    // tắt BỔ SUNG — form "Tenant cụ thể" thủ công bên dưới (tìm & chọn qua TenantPicker) vẫn giữ
    // nguyên, vẫn hữu ích khi chưa có sẵn danh sách tenant lọc trước.
    useEffect(() => {
        const preset = (location.state as { presetTenants?: AdminTenantSummary[] } | null)?.presetTenants;
        if (preset && preset.length > 0) {
            form.setFieldsValue({ audience_kind: 'tenant_ids', tenant_ids: preset.map((t) => t.id) });
            setPresetTenants(preset);
            message.info(`Đã điền sẵn ${preset.length} tenant từ danh sách đã chọn ở trang Tenants.`);
            // Xoá state khỏi history sau khi dùng — tránh điền lại nếu admin F5 hoặc quay lại
            // trang này lần sau (history state của trình duyệt vẫn còn nếu không xoá).
            navigate(location.pathname, { replace: true, state: null });
        }
        // Chỉ chạy 1 lần lúc mount — location.state chỉ có ý nghĩa ở lần điều hướng đầu tiên tới
        // trang này, không phải mỗi khi form/location thay đổi.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const presetOptions: TenantPickerOption[] = presetTenants.map((t) => ({
        value: t.id,
        label: (
            <Space size={6}>
                <Typography.Text>{t.name}</Typography.Text>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                    · {t.code}{t.owner ? ` · ${t.owner.email}` : ''}
                </Typography.Text>
            </Space>
        ),
    }));

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
            render: (v: string | null) => formatDate(v),
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
                            audience.tenant_ids = (v.tenant_ids ?? []) as number[];
                        }
                        create.mutate({ subject: v.subject, body_markdown: v.body_markdown, audience }, {
                            onSuccess: (b) => {
                                message.success(`Đã gửi tới ${b.sent_count}/${b.recipient_count} người.`);
                                form.resetFields();
                                setPresetTenants([]);
                            },
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
                            <Form.Item name="tenant_ids" label="Tenant cụ thể" rules={[{ required: true }]}>
                                <TenantPicker mode="multiple" placeholder="Tìm theo mã / tên / email…" initialOptions={presetOptions} />
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
                    pagination={{
                        current: page,
                        pageSize: data?.meta.pagination.per_page ?? 30,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: false,
                        onChange: setPage,
                    }}
                />
            </Card>
        </>
    );
}
