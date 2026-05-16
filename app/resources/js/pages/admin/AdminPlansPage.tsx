import { useState } from 'react';
import { App, Button, Card, Form, Input, InputNumber, Modal, Space, Switch, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { EditOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { useAdminPlans, useAdminUpdatePlan, type AdminPlan } from '@/lib/admin';
import { errorMessage } from '@/lib/api';

const KNOWN_FEATURES = [
    'procurement', 'fifo_cogs', 'profit_reports', 'finance_settlements',
    'demand_planning', 'mass_listing', 'automation_rules', 'accounting_basic',
];

export function AdminPlansPage() {
    const { data, isLoading } = useAdminPlans();
    const [editing, setEditing] = useState<AdminPlan | null>(null);

    const columns: ColumnsType<AdminPlan> = [
        {
            title: 'Mã / Tên', key: 'code',
            render: (_, r) => (
                <Space direction="vertical" size={0}>
                    <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{r.code}</Typography.Text>
                    <Typography.Text type="secondary">{r.name}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Trạng thái', dataIndex: 'is_active',
            render: (v: boolean) => v ? <Tag color="green">Đang bán</Tag> : <Tag>Tắt</Tag>,
        },
        {
            title: 'Giá tháng', dataIndex: 'price_monthly',
            render: (v: number) => new Intl.NumberFormat('vi-VN').format(v) + ' đ',
        },
        {
            title: 'Giá năm', dataIndex: 'price_yearly',
            render: (v: number) => new Intl.NumberFormat('vi-VN').format(v) + ' đ',
        },
        {
            title: 'Số gian hàng', key: 'shops',
            render: (_, r) => {
                const limit = r.limits?.max_channel_accounts ?? 0;
                return limit < 0 ? '∞' : limit;
            },
        },
        {
            title: 'Tính năng nâng cao', key: 'features',
            render: (_, r) => (
                <Space wrap size={4}>
                    {Object.entries(r.features ?? {}).filter(([, v]) => v).map(([k]) => (
                        <Tag key={k} color="blue">{k}</Tag>
                    ))}
                </Space>
            ),
        },
        {
            title: 'Hành động', key: 'actions', width: 100,
            render: (_, r) => <Button icon={<EditOutlined />} onClick={() => setEditing(r)}>Sửa</Button>,
        },
    ];

    return (
        <>
            <PageHeader title="Gói thuê bao (Plans)" subtitle="Sửa giá, hạn mức, tính năng — không cần re-deploy. Code/currency immutable." />
            <Card>
                <Table rowKey="id" columns={columns} dataSource={data ?? []} loading={isLoading} pagination={false} />
            </Card>
            <EditPlanModal plan={editing} onClose={() => setEditing(null)} />
        </>
    );
}

function EditPlanModal({ plan, onClose }: { plan: AdminPlan | null; onClose: () => void }) {
    const { message } = App.useApp();
    const update = useAdminUpdatePlan();
    const [form] = Form.useForm();

    return (
        <Modal
            title={plan ? `Sửa gói: ${plan.code}` : 'Sửa gói'}
            open={plan != null}
            onCancel={onClose}
            onOk={() => form.submit()}
            okText="Lưu"
            cancelText="Huỷ"
            confirmLoading={update.isPending}
            destroyOnClose
            width={620}
        >
            {plan && (
                <Form
                    form={form}
                    layout="vertical"
                    initialValues={{
                        name: plan.name,
                        description: plan.description,
                        is_active: plan.is_active,
                        price_monthly: plan.price_monthly,
                        price_yearly: plan.price_yearly,
                        trial_days: plan.trial_days,
                        max_channel_accounts: plan.limits?.max_channel_accounts ?? 0,
                        features: KNOWN_FEATURES.reduce((acc, k) => {
                            acc[k] = !!plan.features?.[k];
                            return acc;
                        }, {} as Record<string, boolean>),
                    }}
                    onFinish={(v) => {
                        update.mutate({
                            id: plan.id,
                            name: v.name, description: v.description, is_active: v.is_active,
                            price_monthly: v.price_monthly, price_yearly: v.price_yearly,
                            trial_days: v.trial_days,
                            limits: { max_channel_accounts: v.max_channel_accounts },
                            features: v.features,
                        }, {
                            onSuccess: () => { message.success('Đã cập nhật gói.'); onClose(); },
                            onError: (e) => message.error(errorMessage(e, 'Không cập nhật được.')),
                        });
                    }}
                >
                    <Form.Item name="name" label="Tên hiển thị" rules={[{ required: true }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="description" label="Mô tả">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Space>
                        <Form.Item name="price_monthly" label="Giá tháng (VND)"><InputNumber style={{ width: 160 }} /></Form.Item>
                        <Form.Item name="price_yearly" label="Giá năm (VND)"><InputNumber style={{ width: 160 }} /></Form.Item>
                        <Form.Item name="trial_days" label="Trial (ngày)"><InputNumber style={{ width: 100 }} min={0} max={365} /></Form.Item>
                    </Space>
                    <Form.Item name="max_channel_accounts" label="Số gian hàng tối đa (-1 = không giới hạn)">
                        <InputNumber style={{ width: 160 }} min={-1} />
                    </Form.Item>
                    <Form.Item name="is_active" label="Đang bán?" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Form.Item label="Tính năng nâng cao">
                        <Space wrap>
                            {KNOWN_FEATURES.map((k) => (
                                <Form.Item key={k} name={['features', k]} valuePropName="checked" noStyle>
                                    <FeatureToggle name={k} />
                                </Form.Item>
                            ))}
                        </Space>
                    </Form.Item>
                </Form>
            )}
        </Modal>
    );
}

function FeatureToggle({ name, checked, onChange }: { name: string; checked?: boolean; onChange?: (v: boolean) => void }) {
    return (
        <Tag.CheckableTag
            checked={!!checked}
            onChange={(v) => onChange?.(v)}
            style={{ padding: '4px 10px', borderRadius: 6, border: '1px solid #d9d9d9' }}
        >
            {name}
        </Tag.CheckableTag>
    );
}
