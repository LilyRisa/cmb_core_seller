import { useMemo, useState } from 'react';
import {
    App, Button, Card, Drawer, Form, Input, InputNumber, Modal,
    Radio, Space, Table, Tag, Typography,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import {
    GiftOutlined, PercentageOutlined, ClockCircleOutlined, RocketOutlined,
    PlusOutlined, StopOutlined, SendOutlined, ReloadOutlined,
} from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import {
    useAdminVouchers, useAdminCreateVoucher, useAdminDisableVoucher,
    useAdminGrantVoucher, useAdminVoucher,
    type AdminVoucher, type VoucherKind,
} from '@/lib/admin';
import { errorMessage } from '@/lib/api';
import dayjs from 'dayjs';

const KIND_LABEL: Record<VoucherKind, string> = {
    percent: '% Giảm giá',
    fixed: 'Giảm VND',
    free_days: 'Tặng ngày',
    plan_upgrade: 'Tặng gói',
};

const KIND_ICON: Record<VoucherKind, React.ReactNode> = {
    percent: <PercentageOutlined />,
    fixed: <GiftOutlined />,
    free_days: <ClockCircleOutlined />,
    plan_upgrade: <RocketOutlined />,
};

export function AdminVouchersPage() {
    const { message, modal } = App.useApp();
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const [openVoucherId, setOpenVoucherId] = useState<number | null>(null);
    const [createOpen, setCreateOpen] = useState(false);

    const filters = useMemo(() => ({ q: q.trim() || undefined, page, per_page: 30 }), [q, page]);
    const { data, isFetching } = useAdminVouchers(filters);
    const disable = useAdminDisableVoucher();

    const columns: ColumnsType<AdminVoucher> = [
        {
            title: 'Mã', dataIndex: 'code', key: 'code',
            render: (v: string, r) => (
                <Space direction="vertical" size={0}>
                    <Typography.Text strong style={{ fontFamily: 'ui-monospace, SFMono-Regular, monospace' }}>{v}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.name}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Loại', dataIndex: 'kind', key: 'kind',
            render: (v: VoucherKind) => <Tag icon={KIND_ICON[v]} color="blue">{KIND_LABEL[v]}</Tag>,
        },
        {
            title: 'Giá trị', dataIndex: 'value', key: 'value',
            render: (v: number, r) => {
                if (r.kind === 'percent') return `${v}%`;
                if (r.kind === 'fixed') return new Intl.NumberFormat('vi-VN').format(v) + ' đ';
                if (r.kind === 'free_days') return `${v} ngày`;
                return `Plan #${v}`;
            },
        },
        {
            title: 'Đã dùng', key: 'count',
            render: (_: unknown, r) => `${r.redemption_count}${r.max_redemptions >= 0 ? `/${r.max_redemptions}` : ''}`,
        },
        {
            title: 'Hết hạn', dataIndex: 'expires_at', key: 'expires_at',
            render: (v: string | null) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—',
        },
        {
            title: 'Trạng thái', key: 'status',
            render: (_: unknown, r) => (
                <Space>
                    {r.is_active ? <Tag color="green">Đang chạy</Tag> : <Tag color="default">Đã tắt</Tag>}
                    {r.is_exhausted && <Tag color="orange">Hết lượt</Tag>}
                    {!r.is_in_window && r.is_active && <Tag color="red">Ngoài thời hạn</Tag>}
                </Space>
            ),
        },
        {
            title: 'Hành động', key: 'actions', width: 160,
            render: (_: unknown, r) => (
                <Space>
                    <Button size="small" onClick={() => setOpenVoucherId(r.id)}>Chi tiết</Button>
                    {r.is_active && (
                        <Button size="small" danger icon={<StopOutlined />} onClick={() => {
                            modal.confirm({
                                title: `Vô hiệu hoá voucher ${r.code}?`,
                                content: 'Lịch sử redemption vẫn được giữ. Có thể bật lại sau qua "Sửa".',
                                onOk: () => disable.mutateAsync(r.id).then(() => message.success('Đã vô hiệu hoá.')),
                            });
                        }} />
                    )}
                </Space>
            ),
        },
    ];

    return (
        <>
            <PageHeader title="Voucher & quà tặng" subtitle="Tạo mã ưu đãi cho khách hoặc tặng thẳng cho tenant cụ thể." />

            <Card>
                <Space style={{ marginBottom: 12, display: 'flex', justifyContent: 'space-between' }}>
                    <Input.Search
                        placeholder="Tìm theo mã hoặc tên..."
                        allowClear
                        value={q}
                        onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ width: 320 }}
                    />
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateOpen(true)}>Tạo voucher</Button>
                </Space>

                <Table
                    rowKey="id"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isFetching}
                    pagination={{
                        current: page, pageSize: 30, total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: false, onChange: setPage,
                    }}
                />
            </Card>

            <CreateVoucherModal open={createOpen} onClose={() => setCreateOpen(false)} />

            <VoucherDetailDrawer
                voucherId={openVoucherId}
                onClose={() => setOpenVoucherId(null)}
            />
        </>
    );
}

function CreateVoucherModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { message } = App.useApp();
    const create = useAdminCreateVoucher();
    const [form] = Form.useForm();
    const kind = Form.useWatch('kind', form) as VoucherKind | undefined;

    return (
        <Modal
            title="Tạo voucher mới"
            open={open}
            onCancel={() => { form.resetFields(); onClose(); }}
            onOk={() => form.submit()}
            okText="Tạo"
            cancelText="Huỷ"
            confirmLoading={create.isPending}
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                initialValues={{ kind: 'percent' as VoucherKind, max_redemptions: -1 }}
                onFinish={(v) => {
                    create.mutate(v, {
                        onSuccess: () => { message.success('Đã tạo voucher.'); form.resetFields(); onClose(); },
                        onError: (e) => message.error(errorMessage(e, 'Không tạo được.')),
                    });
                }}
            >
                <Form.Item name="code" label="Mã code (uppercase, gạch dưới)" rules={[{ required: true, pattern: /^[A-Z0-9_-]+$/, message: 'Chỉ A-Z, 0-9, _, -' }]}>
                    <Input placeholder="SUMMER20" />
                </Form.Item>
                <Form.Item name="name" label="Tên hiển thị" rules={[{ required: true }]}>
                    <Input placeholder="Khuyến mãi hè 2026" />
                </Form.Item>
                <Form.Item name="kind" label="Loại voucher" rules={[{ required: true }]}>
                    <Radio.Group>
                        <Radio.Button value="percent">% Giảm giá</Radio.Button>
                        <Radio.Button value="fixed">Giảm VND</Radio.Button>
                        <Radio.Button value="free_days">Tặng ngày</Radio.Button>
                        <Radio.Button value="plan_upgrade">Tặng gói</Radio.Button>
                    </Radio.Group>
                </Form.Item>
                <Form.Item
                    name="value"
                    label={
                        kind === 'percent' ? 'Phần trăm (1-100)' :
                        kind === 'fixed' ? 'Số tiền giảm (VND)' :
                        kind === 'free_days' ? 'Số ngày tặng (1-365)' :
                        'Plan ID (mục tiêu nâng gói)'
                    }
                    rules={[{ required: true, type: 'number', min: 1 }]}
                >
                    <InputNumber style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item name="max_redemptions" label="Tối đa lượt dùng (−1 = không giới hạn)">
                    <InputNumber style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item name="expires_at" label="Hết hạn (ISO date, để trống = vĩnh viễn)">
                    <Input placeholder="2026-12-31T23:59:59Z" />
                </Form.Item>
                <Form.Item name="description" label="Ghi chú nội bộ">
                    <Input.TextArea rows={2} />
                </Form.Item>
            </Form>
        </Modal>
    );
}

function VoucherDetailDrawer({ voucherId, onClose }: { voucherId: number | null; onClose: () => void }) {
    const { message } = App.useApp();
    const { data, isLoading, refetch } = useAdminVoucher(voucherId);
    const grant = useAdminGrantVoucher();
    const [grantForm] = Form.useForm();
    const [grantOpen, setGrantOpen] = useState(false);

    return (
        <Drawer
            open={voucherId != null}
            onClose={onClose}
            width={560}
            title={data ? <Space>{KIND_ICON[data.kind]} {data.code}</Space> : 'Chi tiết voucher'}
            extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()} />}
        >
            {isLoading || !data ? (
                <Typography.Text type="secondary">Đang tải...</Typography.Text>
            ) : (
                <>
                    <Card size="small" style={{ marginBottom: 16 }}>
                        <Typography.Paragraph><strong>{data.name}</strong></Typography.Paragraph>
                        <Typography.Paragraph type="secondary">{data.description ?? 'Không có ghi chú.'}</Typography.Paragraph>
                        <Space>
                            <Tag>{KIND_LABEL[data.kind]}</Tag>
                            <Typography.Text>Giá trị: <strong>{data.value}</strong></Typography.Text>
                            <Typography.Text>Đã dùng: <strong>{data.redemption_count}{data.max_redemptions >= 0 ? `/${data.max_redemptions}` : ''}</strong></Typography.Text>
                        </Space>
                    </Card>

                    {(data.kind === 'free_days' || data.kind === 'plan_upgrade') && (
                        <Card size="small" title="Tặng cho tenant cụ thể" style={{ marginBottom: 16 }}>
                            <Button block icon={<SendOutlined />} onClick={() => setGrantOpen(true)}>Grant tới tenant…</Button>
                        </Card>
                    )}

                    <Card size="small" title="Lịch sử redemption">
                        {data.recent_redemptions.length === 0 ? (
                            <Typography.Text type="secondary">Chưa có redemption.</Typography.Text>
                        ) : (
                            <Table
                                size="small" rowKey="id"
                                pagination={false}
                                columns={[
                                    { title: 'Tenant', dataIndex: 'tenant_id' },
                                    {
                                        title: 'Discount/Days', key: 'eff',
                                        render: (_, r) => (r.discount_amount > 0 ? `${new Intl.NumberFormat('vi-VN').format(r.discount_amount)} đ` : `${r.granted_days} ngày`),
                                    },
                                    { title: 'Lúc', dataIndex: 'created_at', render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—' },
                                ]}
                                dataSource={data.recent_redemptions}
                            />
                        )}
                    </Card>

                    <Modal
                        title={`Grant ${data.code} cho tenant`}
                        open={grantOpen}
                        onCancel={() => { grantForm.resetFields(); setGrantOpen(false); }}
                        onOk={() => grantForm.submit()}
                        okText="Grant"
                        confirmLoading={grant.isPending}
                    >
                        <Form
                            form={grantForm}
                            layout="vertical"
                            onFinish={(v) => {
                                grant.mutate({ voucherId: data.id, tenantId: Number(v.tenant_id), reason: v.reason }, {
                                    onSuccess: () => {
                                        message.success('Đã grant thành công.');
                                        grantForm.resetFields(); setGrantOpen(false);
                                        refetch();
                                    },
                                    onError: (e) => message.error(errorMessage(e, 'Grant lỗi.')),
                                });
                            }}
                        >
                            <Form.Item name="tenant_id" label="Tenant ID" rules={[{ required: true }]}>
                                <InputNumber style={{ width: '100%' }} placeholder="VD: 12" />
                            </Form.Item>
                            <Form.Item name="reason" label="Lý do (≥10 ký tự)" rules={[{ required: true, min: 10 }]}>
                                <Input.TextArea rows={3} placeholder="VD: Khách VIP — quà sinh nhật" />
                            </Form.Item>
                        </Form>
                    </Modal>
                </>
            )}
        </Drawer>
    );
}
