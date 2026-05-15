import { useState } from 'react';
import { App, Button, Descriptions, Drawer, Empty, Form, Input, Modal, Radio, Skeleton, Space, Table, Tabs, Tag, Typography } from 'antd';
import { DeleteOutlined, LockOutlined, ShopOutlined, SwapOutlined, UnlockOutlined, WarningOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import {
    useAdminChangePlan, useAdminDeleteChannel, useAdminReactivateTenant, useAdminSuspendTenant,
    useAdminTenant,
    type AdminChannelAccount,
} from '@/lib/admin';
import { errorMessage } from '@/lib/api';

/**
 * Drawer chi tiết 1 tenant cho super-admin. SPEC 0020 §3.3–3.5.
 * 3 tab: Gian hàng (gỡ kênh) · Gói (đổi gói) · Thành viên (read-only).
 */
export function AdminTenantDrawer({ tenantId, onClose }: { tenantId: number | null; onClose: () => void }) {
    const { data: t, isLoading } = useAdminTenant(tenantId);
    const { message, modal } = App.useApp();

    const suspend = useAdminSuspendTenant();
    const reactivate = useAdminReactivateTenant();

    const onSuspend = () => {
        let reason = '';
        modal.confirm({
            title: 'Tạm khoá gian hàng',
            content: (
                <Form layout="vertical" style={{ marginTop: 12 }}>
                    <Form.Item label="Lý do (≥10 ký tự)">
                        <Input.TextArea rows={3} onChange={(e) => { reason = e.target.value; }} />
                    </Form.Item>
                </Form>
            ),
            okText: 'Tạm khoá', okType: 'danger', cancelText: 'Huỷ',
            onOk: async () => {
                if (reason.trim().length < 10) {
                    message.error('Lý do phải có tối thiểu 10 ký tự.');
                    throw new Error('reason too short');
                }
                try {
                    await suspend.mutateAsync({ tenantId: tenantId!, reason: reason.trim() });
                    message.success('Đã tạm khoá tenant.');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    const onReactivate = () => {
        modal.confirm({
            title: 'Mở lại tenant?',
            content: 'Tenant sẽ trở lại trạng thái hoạt động bình thường.',
            okText: 'Mở lại', cancelText: 'Huỷ',
            onOk: async () => {
                try {
                    await reactivate.mutateAsync({ tenantId: tenantId! });
                    message.success('Đã mở lại tenant.');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    return (
        <Drawer
            open={tenantId !== null}
            onClose={onClose}
            width={840}
            destroyOnHidden
            title={t ? (
                <Space size={8}>
                    <Typography.Text strong>{t.name}</Typography.Text>
                    <Tag color={t.status === 'suspended' ? 'red' : 'green'}>
                        {t.status === 'suspended' ? 'Tạm khoá' : 'Hoạt động'}
                    </Tag>
                </Space>
            ) : 'Chi tiết tenant'}
            extra={t && (
                t.status === 'suspended'
                    ? <Button type="primary" icon={<UnlockOutlined />} onClick={onReactivate} loading={reactivate.isPending}>Mở lại</Button>
                    : <Button danger icon={<LockOutlined />} onClick={onSuspend} loading={suspend.isPending}>Tạm khoá</Button>
            )}
        >
            {isLoading || !t ? <Skeleton active /> : (
                <>
                    <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                        <Descriptions.Item label="Slug">{t.slug}</Descriptions.Item>
                        <Descriptions.Item label="Tạo lúc">{t.created_at ? dayjs(t.created_at).format('DD/MM/YYYY HH:mm') : '—'}</Descriptions.Item>
                        <Descriptions.Item label="Chủ sở hữu" span={2}>
                            {t.owner ? `${t.owner.name} <${t.owner.email}>` : '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Hạn mức kênh" span={2}>
                            <Space>
                                <Typography.Text strong style={{ color: t.usage.channel_accounts.over ? '#cf1322' : undefined }}>
                                    {t.usage.channel_accounts.used} / {t.usage.channel_accounts.limit < 0 ? '∞' : t.usage.channel_accounts.limit}
                                </Typography.Text>
                                {t.usage.channel_accounts.over && (
                                    <Tag color={t.subscription?.over_quota_locked ? 'red' : 'orange'}
                                        icon={t.subscription?.over_quota_locked ? <LockOutlined /> : <WarningOutlined />}>
                                        {t.subscription?.over_quota_locked ? 'Đã khoá (quá 48h)' : 'Đang đếm 48h ân hạn'}
                                    </Tag>
                                )}
                            </Space>
                        </Descriptions.Item>
                    </Descriptions>

                    <Tabs
                        items={[
                            { key: 'channels', label: <Space><ShopOutlined /> Gian hàng ({t.channel_accounts.length})</Space>, children: <ChannelsTab tenantId={t.id} accounts={t.channel_accounts} /> },
                            { key: 'plan', label: <Space><SwapOutlined /> Gói thuê bao</Space>, children: <PlanTab tenantId={t.id} sub={t.subscription} /> },
                            { key: 'members', label: `Thành viên (${t.members.length})`, children: <MembersTab members={t.members} /> },
                            { key: 'audit', label: 'Audit log gần đây', children: <AuditTab entries={t.recent_admin_actions} /> },
                        ]}
                    />
                </>
            )}
        </Drawer>
    );
}

// -- Channels tab ------------------------------------------------------------

function ChannelsTab({ tenantId, accounts }: { tenantId: number; accounts: AdminTenantDrawerChannelAccount[] }) {
    const del = useAdminDeleteChannel();
    const { message, modal } = App.useApp();

    const onDelete = (acc: AdminTenantDrawerChannelAccount) => {
        let reason = '';
        modal.confirm({
            title: <Space><WarningOutlined style={{ color: '#cf1322' }} /> Xoá kết nối «{acc.name}»?</Space>,
            content: (
                <div>
                    <Typography.Paragraph type="warning" style={{ marginBottom: 8 }}>
                        Hành động này KHÔNG hoàn tác: xoá kết nối + xoá đơn của gian hàng + huỷ liên kết SKU.
                        Tồn đã giữ chỗ sẽ được nhả.
                    </Typography.Paragraph>
                    <Form layout="vertical" style={{ marginTop: 12 }}>
                        <Form.Item label="Lý do (≥10 ký tự — sẽ ghi audit log)">
                            <Input.TextArea rows={3} onChange={(e) => { reason = e.target.value; }}
                                placeholder="vd: Khách yêu cầu gỡ kênh sau khi hạ gói về Starter." />
                        </Form.Item>
                    </Form>
                </div>
            ),
            okText: 'Xoá kết nối', okType: 'danger', cancelText: 'Huỷ',
            onOk: async () => {
                if (reason.trim().length < 10) {
                    message.error('Lý do phải có tối thiểu 10 ký tự.');
                    throw new Error('reason too short');
                }
                try {
                    const r = await del.mutateAsync({ tenantId, channelAccountId: acc.id, reason: reason.trim() });
                    message.success(`Đã xoá kết nối: ${r.deleted_orders} đơn + ${r.unlinked_skus} liên kết SKU.`);
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    if (accounts.length === 0) return <Empty description="Chưa có kết nối kênh." />;

    return (
        <Table<AdminTenantDrawerChannelAccount>
            size="small" rowKey="id" pagination={false}
            dataSource={accounts}
            columns={[
                { title: 'Tên', dataIndex: 'name', key: 'name',
                    render: (_v, r) => (
                        <Space direction="vertical" size={0}>
                            <Typography.Text strong>{r.name}</Typography.Text>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.provider} · #{r.external_shop_id}</Typography.Text>
                        </Space>
                    ) },
                { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 110,
                    render: (v: string) => <Tag color={v === 'active' ? 'green' : v === 'revoked' ? 'red' : 'orange'}>{v}</Tag> },
                { title: 'Đồng bộ gần nhất', dataIndex: 'last_synced_at', key: 'last_synced_at', width: 160,
                    render: (v: string | null) => v ? dayjs(v).format('DD/MM HH:mm') : '—' },
                { title: '', key: 'actions', width: 90,
                    render: (_v, r) => (
                        <Button danger size="small" icon={<DeleteOutlined />}
                            onClick={() => onDelete(r)} loading={del.isPending}>
                            Xoá
                        </Button>
                    ) },
            ]}
        />
    );
}

type AdminTenantDrawerChannelAccount = AdminChannelAccount;

// -- Plan tab ----------------------------------------------------------------

const PLAN_OPTIONS: Array<{ value: string; label: string }> = [
    { value: 'trial', label: 'Trial' },
    { value: 'starter', label: 'Starter' },
    { value: 'pro', label: 'Pro' },
    { value: 'business', label: 'Business' },
];

function PlanTab({ tenantId, sub }: { tenantId: number; sub: import('@/lib/admin').AdminSubscription | null }) {
    const change = useAdminChangePlan();
    const { message } = App.useApp();
    const [open, setOpen] = useState(false);
    const [planCode, setPlanCode] = useState<string>(sub?.plan_code ?? 'starter');
    const [cycle, setCycle] = useState<'monthly' | 'yearly' | 'trial'>('monthly');
    const [reason, setReason] = useState('');

    const submit = async () => {
        if (reason.trim().length < 10) {
            message.error('Lý do phải có tối thiểu 10 ký tự.');
            return;
        }
        try {
            await change.mutateAsync({ tenantId, plan_code: planCode, cycle, reason: reason.trim() });
            message.success('Đã đổi gói cho tenant.');
            setOpen(false); setReason('');
        } catch (e) { message.error(errorMessage(e)); }
    };

    return (
        <>
            {sub ? (
                <Descriptions size="small" column={2} bordered>
                    <Descriptions.Item label="Gói">{(sub.plan_code ?? '—').toUpperCase()}</Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">{sub.status}</Descriptions.Item>
                    <Descriptions.Item label="Chu kỳ">{sub.billing_cycle}</Descriptions.Item>
                    <Descriptions.Item label="Hết hạn">{sub.current_period_end ? dayjs(sub.current_period_end).format('DD/MM/YYYY') : '—'}</Descriptions.Item>
                    {sub.over_quota_warned_at && (
                        <Descriptions.Item label="Cảnh báo vượt mức" span={2}>
                            <Space>
                                <Typography.Text>{dayjs(sub.over_quota_warned_at).format('DD/MM/YYYY HH:mm')}</Typography.Text>
                                <Tag color={sub.over_quota_locked ? 'red' : 'orange'}>
                                    {sub.over_quota_locked ? 'Đã quá 48h — đang khoá' : 'Còn trong 48h ân hạn'}
                                </Tag>
                            </Space>
                        </Descriptions.Item>
                    )}
                </Descriptions>
            ) : <Empty description="Chưa có subscription" />}

            <div style={{ marginTop: 16 }}>
                <Button type="primary" icon={<SwapOutlined />} onClick={() => setOpen(true)}>Đổi gói</Button>
            </div>

            <Modal
                open={open} onCancel={() => setOpen(false)} title="Đổi gói cho tenant"
                okText="Xác nhận đổi" cancelText="Huỷ" onOk={submit} confirmLoading={change.isPending}
            >
                <Form layout="vertical">
                    <Form.Item label="Gói">
                        <Radio.Group value={planCode} onChange={(e) => setPlanCode(e.target.value)} optionType="button" buttonStyle="solid"
                            options={PLAN_OPTIONS} />
                    </Form.Item>
                    <Form.Item label="Chu kỳ">
                        <Radio.Group value={cycle} onChange={(e) => setCycle(e.target.value)} optionType="button" buttonStyle="solid"
                            options={[
                                { value: 'monthly', label: 'Tháng' },
                                { value: 'yearly', label: 'Năm' },
                                { value: 'trial', label: 'Trial' },
                            ]} />
                    </Form.Item>
                    <Form.Item label="Lý do (≥10 ký tự)" required>
                        <Input.TextArea rows={3} value={reason} onChange={(e) => setReason(e.target.value)}
                            placeholder="vd: Khách yêu cầu hạ gói về Starter. Ticket #1234." />
                    </Form.Item>
                    <Typography.Paragraph type="warning" style={{ fontSize: 12 }}>
                        Đổi gói tay không tạo hoá đơn. Subscription cũ ⇒ cancelled, subscription mới ⇒ active từ
                        thời điểm này. Nếu gói thấp hơn ⇒ tenant có thể bị vào trạng thái "vượt mức" (banner đếm 48h).
                    </Typography.Paragraph>
                </Form>
            </Modal>
        </>
    );
}

// -- Members tab -------------------------------------------------------------

function MembersTab({ members }: { members: import('@/lib/admin').AdminMember[] }) {
    if (members.length === 0) return <Empty description="Chưa có thành viên." />;

    return (
        <Table size="small" rowKey="user_id" pagination={false}
            dataSource={members}
            columns={[
                { title: 'Tên', dataIndex: 'name', key: 'name', render: (v) => v ?? '—' },
                { title: 'Email', dataIndex: 'email', key: 'email', render: (v) => v ?? '—' },
                { title: 'Vai trò', dataIndex: 'role', key: 'role', width: 140,
                    render: (v: string) => <Tag>{v}</Tag> },
                { title: 'Super admin', dataIndex: 'is_super_admin', key: 'sa', width: 110,
                    render: (v: boolean) => v ? <Tag color="purple">Có</Tag> : <Tag>—</Tag> },
            ]}
        />
    );
}

// -- Audit tab ---------------------------------------------------------------

function AuditTab({ entries }: { entries: import('@/lib/admin').AdminAuditEntry[] }) {
    if (entries.length === 0) return <Empty description="Chưa có thao tác admin nào trên tenant này." />;

    return (
        <Table size="small" rowKey="id" pagination={false}
            dataSource={entries}
            columns={[
                { title: 'Thời gian', dataIndex: 'created_at', key: 'created_at', width: 160,
                    render: (v: string | null) => v ? dayjs(v).format('DD/MM HH:mm:ss') : '—' },
                { title: 'Hành động', dataIndex: 'action', key: 'action', width: 220,
                    render: (v: string) => <Tag>{v}</Tag> },
                { title: 'Admin', dataIndex: 'user_id', key: 'user_id', width: 80 },
                { title: 'Lý do / chi tiết', dataIndex: 'changes', key: 'changes',
                    render: (v: Record<string, unknown> | null) => (
                        <Typography.Text style={{ fontSize: 12 }} type="secondary">
                            {v ? JSON.stringify(v) : '—'}
                        </Typography.Text>
                    ) },
                { title: 'IP', dataIndex: 'ip', key: 'ip', width: 120 },
            ]}
        />
    );
}
