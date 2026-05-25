import { useState } from 'react';
import { App, Button, Card, Form, Input, InputNumber, Modal, Space, Switch, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { EditOutlined, PlusOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { useAdminCreatePlan, useAdminPlans, useAdminUpdatePlan, type AdminPlan } from '@admin/lib/admin';
import { errorMessage } from '@/lib/api';

// Toàn bộ feature flags app kiểm tra (đồng bộ BillingPlanSeeder + middleware plan.feature).
const KNOWN_FEATURES = [
    'procurement', 'fifo_cogs', 'profit_reports', 'finance_settlements',
    'demand_planning', 'mass_listing', 'automation_rules', 'priority_support',
    'accounting_basic', 'accounting_advanced', 'messaging_inbox', 'messaging_ai',
];

export function AdminPlansPage() {
    const { data, isLoading } = useAdminPlans();
    const [editing, setEditing] = useState<AdminPlan | null>(null);
    const [creating, setCreating] = useState(false);

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
            <PageHeader
                title="Gói thuê bao (Plans)"
                subtitle="Tạo / sửa giá, hạn mức, tính năng — không cần re-deploy. Sau khi tạo, code/currency immutable."
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={() => setCreating(true)}>Tạo gói</Button>}
            />
            <Card>
                <Table rowKey="id" columns={columns} dataSource={data ?? []} loading={isLoading} pagination={false} />
            </Card>
            <PlanModal
                open={creating || editing != null}
                plan={editing}
                onClose={() => { setCreating(false); setEditing(null); }}
            />
        </>
    );
}

function PlanModal({ open, plan, onClose }: { open: boolean; plan: AdminPlan | null; onClose: () => void }) {
    const { message } = App.useApp();
    const update = useAdminUpdatePlan();
    const create = useAdminCreatePlan();
    const [form] = Form.useForm();
    const isCreate = plan == null;

    const initialValues = isCreate
        ? {
            code: '', name: '', description: '', is_active: true, sort_order: 0,
            price_monthly: 0, price_yearly: 0, trial_days: 0,
            max_channel_accounts: 2, messaging_ai_replies_monthly: 0, messaging_media_mb_daily: 0,
            features: KNOWN_FEATURES.reduce((acc, k) => { acc[k] = false; return acc; }, {} as Record<string, boolean>),
        }
        : {
            name: plan.name, description: plan.description, is_active: plan.is_active, sort_order: plan.sort_order,
            price_monthly: plan.price_monthly, price_yearly: plan.price_yearly, trial_days: plan.trial_days,
            max_channel_accounts: plan.limits?.max_channel_accounts ?? 0,
            messaging_ai_replies_monthly: plan.limits?.messaging_ai_replies_monthly ?? 0,
            messaging_media_mb_daily: plan.limits?.messaging_media_mb_daily ?? 0,
            features: KNOWN_FEATURES.reduce((acc, k) => { acc[k] = !!plan.features?.[k]; return acc; }, {} as Record<string, boolean>),
        };

    const submit = (v: Record<string, unknown>) => {
        const payload = {
            name: v.name as string, description: v.description as string, is_active: v.is_active as boolean,
            sort_order: v.sort_order as number,
            price_monthly: v.price_monthly as number, price_yearly: v.price_yearly as number, trial_days: v.trial_days as number,
            limits: {
                max_channel_accounts: v.max_channel_accounts as number,
                messaging_ai_replies_monthly: v.messaging_ai_replies_monthly as number,
                messaging_media_mb_daily: v.messaging_media_mb_daily as number,
            },
            features: v.features as Record<string, boolean>,
        };
        if (isCreate) {
            create.mutate({ code: v.code as string, ...payload }, {
                onSuccess: () => { message.success('Đã tạo gói mới.'); onClose(); },
                onError: (e: unknown) => message.error(errorMessage(e, 'Không tạo được gói.')),
            });
        } else {
            update.mutate({ id: plan.id, ...payload }, {
                onSuccess: () => { message.success('Đã cập nhật gói.'); onClose(); },
                onError: (e: unknown) => message.error(errorMessage(e, 'Không cập nhật được.')),
            });
        }
    };

    return (
        <Modal
            title={isCreate ? 'Tạo gói mới' : `Sửa gói: ${plan.code}`}
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            okText={isCreate ? 'Tạo' : 'Lưu'}
            cancelText="Huỷ"
            confirmLoading={isCreate ? create.isPending : update.isPending}
            destroyOnClose
            width={620}
        >
            {open && (
                <Form form={form} layout="vertical" initialValues={initialValues} onFinish={submit}>
                    {isCreate && (
                        <Form.Item
                            name="code" label="Mã gói (a-z, 0-9, _ — đặt 1 lần, không sửa được)"
                            rules={[{ required: true }, { pattern: /^[a-z0-9_]+$/, message: 'Chỉ gồm a-z, 0-9, _' }]}
                        >
                            <Input placeholder="vd: test_unlimited" />
                        </Form.Item>
                    )}
                    <Form.Item name="name" label="Tên hiển thị" rules={[{ required: true }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="description" label="Mô tả">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Space wrap>
                        <Form.Item name="price_monthly" label="Giá tháng (VND)"><InputNumber style={{ width: 150 }} min={0} /></Form.Item>
                        <Form.Item name="price_yearly" label="Giá năm (VND)"><InputNumber style={{ width: 150 }} min={0} /></Form.Item>
                        <Form.Item name="trial_days" label="Trial (ngày)"><InputNumber style={{ width: 100 }} min={0} max={365} /></Form.Item>
                        <Form.Item name="sort_order" label="Thứ tự"><InputNumber style={{ width: 90 }} min={0} /></Form.Item>
                    </Space>
                    <Typography.Text type="secondary">Hạn mức — đặt <b>-1</b> để không giới hạn.</Typography.Text>
                    <Space wrap style={{ marginTop: 8 }}>
                        <Form.Item name="max_channel_accounts" label="Số gian hàng"><InputNumber style={{ width: 130 }} min={-1} /></Form.Item>
                        <Form.Item name="messaging_ai_replies_monthly" label="AI reply / tháng"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                        <Form.Item name="messaging_media_mb_daily" label="Media MB / ngày"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                    </Space>
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
