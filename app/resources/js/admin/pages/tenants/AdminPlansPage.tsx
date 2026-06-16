import { useEffect, useState } from 'react';
import { App, Button, Card, Form, Input, InputNumber, Modal, Space, Switch, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { EditOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { useAdminPlans, useAdminUpdatePlan, type AdminPlan } from '@admin/lib/admin';
import { errorMessage } from '@/lib/api';

// Toàn bộ feature flags app kiểm tra (đồng bộ BillingPlanSeeder + middleware plan.feature).
const KNOWN_FEATURES = [
    'procurement', 'fifo_cogs', 'profit_reports', 'finance_settlements',
    'demand_planning', 'mass_listing', 'automation_rules', 'priority_support',
    'accounting_basic', 'accounting_advanced', 'messaging_inbox', 'messaging_ai',
    'marketing_facebook', 'marketing_tiktok', 'shop_health_reports', 'ai',
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
            <PageHeader
                title="Gói thuê bao (Plans)"
                subtitle="Sửa giá, hạn mức, tính năng và bật/tắt gói — không cần re-deploy. Gói được tạo sẵn theo SPEC, không thêm gói mới."
            />
            <Card>
                <Table rowKey="id" columns={columns} dataSource={data ?? []} loading={isLoading} pagination={false} />
            </Card>
            <PlanModal
                open={editing != null}
                plan={editing}
                onClose={() => setEditing(null)}
            />
        </>
    );
}

function valuesFromPlan(plan: AdminPlan) {
    return {
        name: plan.name, description: plan.description, is_active: plan.is_active, sort_order: plan.sort_order,
        price_monthly: plan.price_monthly, price_yearly: plan.price_yearly, trial_days: plan.trial_days,
        max_channel_accounts: plan.limits?.max_channel_accounts ?? 0,
        max_channel_accounts_per_platform: plan.limits?.max_channel_accounts_per_platform ?? -1,
        ai_credits_monthly: plan.limits?.ai_credits_monthly ?? 0,
        messaging_ai_replies_monthly: plan.limits?.messaging_ai_replies_monthly ?? 0,
        messaging_media_mb_daily: plan.limits?.messaging_media_mb_daily ?? 0,
        features: KNOWN_FEATURES.reduce((acc, k) => { acc[k] = !!plan.features?.[k]; return acc; }, {} as Record<string, boolean>),
    };
}

function PlanModal({ open, plan, onClose }: { open: boolean; plan: AdminPlan | null; onClose: () => void }) {
    const { message } = App.useApp();
    const update = useAdminUpdatePlan();
    const [form] = Form.useForm();

    // `form` (từ useForm) tồn tại xuyên suốt giữa các lần mở modal, nên `initialValues`
    // chỉ áp 1 lần → không cập nhật theo plan mới chọn. Chủ động nạp lại mỗi khi mở.
    useEffect(() => {
        if (open && plan) {
            form.setFieldsValue(valuesFromPlan(plan));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, plan?.id, form]);

    if (!plan) return null;

    const initialValues = valuesFromPlan(plan);

    const submit = (v: Record<string, unknown>) => {
        update.mutate({
            id: plan.id,
            name: v.name as string, description: v.description as string, is_active: v.is_active as boolean,
            sort_order: v.sort_order as number,
            price_monthly: v.price_monthly as number, price_yearly: v.price_yearly as number, trial_days: v.trial_days as number,
            limits: {
                max_channel_accounts: v.max_channel_accounts as number,
                max_channel_accounts_per_platform: v.max_channel_accounts_per_platform as number,
                ai_credits_monthly: v.ai_credits_monthly as number,
                messaging_ai_replies_monthly: v.messaging_ai_replies_monthly as number,
                messaging_media_mb_daily: v.messaging_media_mb_daily as number,
            },
            features: v.features as Record<string, boolean>,
        }, {
            onSuccess: () => { message.success('Đã cập nhật gói.'); onClose(); },
            onError: (e: unknown) => message.error(errorMessage(e, 'Không cập nhật được.')),
        });
    };

    return (
        <Modal
            title={`Sửa gói: ${plan.code}`}
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            okText="Lưu"
            cancelText="Huỷ"
            confirmLoading={update.isPending}
            destroyOnClose
            width={620}
        >
            {open && (
                <Form form={form} layout="vertical" initialValues={initialValues} onFinish={submit}>
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
                        <Form.Item name="max_channel_accounts" label="Số gian hàng (tổng)"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                        <Form.Item name="max_channel_accounts_per_platform" label="Gian hàng / nền tảng"><InputNumber style={{ width: 150 }} min={-1} /></Form.Item>
                        <Form.Item name="ai_credits_monthly" label="Lượt AI tặng / kỳ"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
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
