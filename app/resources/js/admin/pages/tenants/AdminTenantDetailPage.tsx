import { type ReactNode, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
    App, Button, Descriptions, Empty, Form, Input, InputNumber, Modal, Radio, Segmented, Space, Spin, Table, Tabs, Tag, Typography,
} from 'antd';
import { ArrowLeftOutlined, DeleteOutlined, FacebookFilled, LockOutlined, SwapOutlined, TikTokOutlined, UnlockOutlined, WarningOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { ChannelLogo } from '@/components/ChannelLogo';
import { CHANNEL_META, formatDate, formatDateShort, formatDateTimeSeconds } from '@/lib/format';
import { errorMessage } from '@/lib/api';
import { useReasonConfirm } from '@admin/components/ReasonConfirmModal';
import {
    useAdminChangePlan, useAdminDeleteChannel, useAdminPlans, useAdminReactivateTenant, useAdminSuspendTenant,
    useAdminTenant, useAdminTenantAiCreditAdjust, useAdminTenantAuditLogs, useAdminTenantDailyOrderStats,
    useAdminTenantLoginHistory, useAdminTenantOrderStatusHistory,
    type AdminChannelAccount, type AdminAdAccount, type AdminMember, type AdminTenantDetail, type AdminFullAuditEntry,
} from '@admin/lib/admin';

/**
 * Trang chi tiết 1 tenant cho super-admin (design 2026-07-15) — thay `AdminTenantDrawer`.
 * Tab: Tổng quan (gói/suspend — port từ Drawer cũ) · Kênh kết nối · Quảng cáo · Thành viên ·
 * Hạn mức AI (mới) · SKU & đơn hàng (mới) · Audit log đầy đủ (mới) · Lịch sử đăng nhập (mới).
 */
export function AdminTenantDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const tenantId = id ? Number(id) : null;
    const { data: t, isLoading } = useAdminTenant(tenantId);

    if (isLoading || !t) {
        return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;
    }

    return (
        <div>
            <PageHeader
                title={(
                    <Space size={8}>
                        <Typography.Text strong style={{ fontSize: 20 }}>{t.name}</Typography.Text>
                        <Tag color={t.status === 'suspended' ? 'red' : 'green'}>
                            {t.status === 'suspended' ? 'Tạm khoá' : 'Hoạt động'}
                        </Tag>
                    </Space>
                )}
                subtitle={t.slug}
                extra={<Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/admin/tenants')}>Quay lại danh sách</Button>}
            />
            <Tabs
                defaultActiveKey="overview"
                items={[
                    { key: 'overview', label: 'Tổng quan', children: <OverviewTab t={t} /> },
                    { key: 'channels', label: `Kênh kết nối (${t.channel_accounts.length})`, children: <ChannelsTab tenantId={t.id} accounts={t.channel_accounts} /> },
                    { key: 'ads', label: `Quảng cáo (${t.ad_accounts?.length ?? 0})`, children: <AdsTab accounts={t.ad_accounts ?? []} /> },
                    { key: 'members', label: `Thành viên (${t.members.length})`, children: <MembersTab members={t.members} /> },
                    { key: 'ai', label: 'Hạn mức AI', children: <AiCreditTab tenantId={tenantId!} t={t} /> },
                    { key: 'orders', label: 'SKU & đơn hàng', children: <OrdersStatsTab tenantId={tenantId!} skuCount={t.sku_count} /> },
                    { key: 'audit', label: 'Audit log đầy đủ', children: <AuditLogTab tenantId={tenantId!} /> },
                    { key: 'logins', label: 'Lịch sử đăng nhập', children: <LoginHistoryTab tenantId={tenantId!} /> },
                ]}
            />
        </div>
    );
}

// -- Overview tab (port nguyên vẹn: descriptions + suspend/reactivate + gói) -------------------

const PLAN_OPTIONS: Array<{ value: string; label: string }> = [
    { value: 'trial', label: 'Dùng thử' },
    { value: 'starter', label: 'Cơ bản' },
    { value: 'pro', label: 'Chuyên nghiệp' },
];

function OverviewTab({ t }: { t: AdminTenantDetail }) {
    const { message, modal } = App.useApp();
    const confirmWithReason = useReasonConfirm();
    const suspend = useAdminSuspendTenant();
    const reactivate = useAdminReactivateTenant();
    const change = useAdminChangePlan();
    const allPlans = useAdminPlans().data as Array<{ code: string; name: string; is_active: boolean }> | undefined;
    const planOptions = (allPlans ?? []).filter((p) => p.is_active).map((p) => ({ value: p.code, label: p.name }));

    const [planOpen, setPlanOpen] = useState(false);
    const [planCode, setPlanCode] = useState<string>(t.subscription?.plan_code ?? 'starter');
    const [cycle, setCycle] = useState<'monthly' | 'yearly' | 'trial'>('monthly');
    const [planForm] = Form.useForm<{ reason: string }>();

    const onSuspend = () => {
        confirmWithReason({
            title: 'Tạm khoá gian hàng',
            danger: true,
            okText: 'Tạm khoá',
            onConfirm: async (reason) => {
                await suspend.mutateAsync({ tenantId: t.id, reason });
                message.success('Đã tạm khoá tenant.');
            },
        });
    };

    const onReactivate = () => {
        // Mở lại KHÔNG cần lý do — AdminTenantService::reactivate() không nhận reason (khác
        // suspend/changePlan), khớp tier "standard" cho hành động khôi phục quyền truy cập.
        modal.confirm({
            title: 'Mở lại tenant?',
            content: 'Tenant sẽ trở lại trạng thái hoạt động bình thường.',
            okText: 'Mở lại', cancelText: 'Huỷ',
            onOk: async () => {
                try {
                    await reactivate.mutateAsync({ tenantId: t.id });
                    message.success('Đã mở lại tenant.');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    const openPlanModal = () => {
        planForm.resetFields();
        setPlanOpen(true);
    };

    const submitPlan = async () => {
        let values: { reason: string };
        try {
            values = await planForm.validateFields();
        } catch {
            return; // Lỗi hiển thị ngay dưới field "Lý do" — không cần toast riêng.
        }
        try {
            await change.mutateAsync({ tenantId: t.id, plan_code: planCode, cycle, reason: values.reason.trim() });
            message.success('Đã đổi gói cho tenant.');
            setPlanOpen(false);
            planForm.resetFields();
        } catch (e) { message.error(errorMessage(e)); }
    };

    const sub = t.subscription;

    return (
        <>
            <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Slug">{t.slug}</Descriptions.Item>
                <Descriptions.Item label="Tạo lúc">{formatDate(t.created_at)}</Descriptions.Item>
                <Descriptions.Item label="Chủ sở hữu" span={2}>
                    {t.owner ? `${t.owner.name} <${t.owner.email}>` : '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Hạn mức kênh" span={2}>
                    <Space>
                        <Typography.Text strong style={{ color: t.usage.channel_accounts.over ? '#cf1322' : undefined }}>
                            {t.usage.channel_accounts.used} / {t.usage.channel_accounts.limit < 0 ? '∞' : t.usage.channel_accounts.limit}
                        </Typography.Text>
                        {t.usage.channel_accounts.over && (
                            <Tag color={sub?.over_quota_locked ? 'red' : 'orange'}
                                icon={sub?.over_quota_locked ? <LockOutlined /> : <WarningOutlined />}>
                                {sub?.over_quota_locked ? 'Đã khoá (quá 48h)' : 'Đang đếm 48h ân hạn'}
                            </Tag>
                        )}
                    </Space>
                </Descriptions.Item>
            </Descriptions>

            <div style={{ marginBottom: 24 }}>
                {t.status === 'suspended'
                    ? <Button type="primary" icon={<UnlockOutlined />} onClick={onReactivate} loading={reactivate.isPending}>Mở lại</Button>
                    : <Button danger icon={<LockOutlined />} onClick={onSuspend} loading={suspend.isPending}>Tạm khoá</Button>}
            </div>

            <Typography.Title level={5}>Gói thuê bao</Typography.Title>
            {sub ? (
                <Descriptions size="small" column={2} bordered>
                    <Descriptions.Item label="Gói">{(sub.plan_code ?? '—').toUpperCase()}</Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">{sub.status}</Descriptions.Item>
                    <Descriptions.Item label="Chu kỳ">{sub.billing_cycle}</Descriptions.Item>
                    <Descriptions.Item label="Hết hạn">{formatDate(sub.current_period_end, false)}</Descriptions.Item>
                    {sub.over_quota_warned_at && (
                        <Descriptions.Item label="Cảnh báo vượt mức" span={2}>
                            <Space>
                                <Typography.Text>{formatDate(sub.over_quota_warned_at)}</Typography.Text>
                                <Tag color={sub.over_quota_locked ? 'red' : 'orange'}>
                                    {sub.over_quota_locked ? 'Đã quá 48h — đang khoá' : 'Còn trong 48h ân hạn'}
                                </Tag>
                            </Space>
                        </Descriptions.Item>
                    )}
                </Descriptions>
            ) : <Empty description="Chưa có subscription" />}

            <div style={{ marginTop: 16 }}>
                <Button type="primary" icon={<SwapOutlined />} onClick={openPlanModal}>Đổi gói</Button>
            </div>

            <Modal
                open={planOpen} onCancel={() => { setPlanOpen(false); planForm.resetFields(); }} title="Đổi gói cho tenant"
                okText="Xác nhận đổi" cancelText="Huỷ" onOk={submitPlan} confirmLoading={change.isPending}
            >
                <Form form={planForm} layout="vertical">
                    <Form.Item label="Gói">
                        <Radio.Group value={planCode} onChange={(e) => setPlanCode(e.target.value)} optionType="button" buttonStyle="solid"
                            options={planOptions.length ? planOptions : PLAN_OPTIONS} />
                    </Form.Item>
                    <Form.Item label="Chu kỳ">
                        <Radio.Group value={cycle} onChange={(e) => setCycle(e.target.value)} optionType="button" buttonStyle="solid"
                            options={[
                                { value: 'monthly', label: 'Tháng' },
                                { value: 'yearly', label: 'Năm' },
                                { value: 'trial', label: 'Trial' },
                            ]} />
                    </Form.Item>
                    <Form.Item
                        name="reason"
                        label="Lý do (≥10 ký tự)"
                        rules={[{ required: true, min: 10, message: 'Lý do phải có tối thiểu 10 ký tự.' }]}
                    >
                        <Input.TextArea rows={3} placeholder="vd: Khách yêu cầu hạ gói về Starter. Ticket #1234." />
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

// -- Channels tab (port nguyên vẹn từ Drawer) --------------------------------

function ChannelsTab({ tenantId, accounts }: { tenantId: number; accounts: AdminChannelAccount[] }) {
    const del = useAdminDeleteChannel();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();

    const onDelete = (acc: AdminChannelAccount) => {
        confirmWithReason({
            title: <Space><WarningOutlined style={{ color: '#cf1322' }} /> Xoá kết nối «{acc.name}»?</Space>,
            danger: true,
            okText: 'Xoá kết nối',
            warningText: 'Hành động này KHÔNG hoàn tác: xoá kết nối + xoá đơn của gian hàng + huỷ liên kết SKU. Tồn đã giữ chỗ sẽ được nhả.',
            reasonPlaceholder: 'vd: Khách yêu cầu gỡ kênh sau khi hạ gói về Starter.',
            onConfirm: async (reason) => {
                const r = await del.mutateAsync({ tenantId, channelAccountId: acc.id, reason });
                message.success(`Đã xoá kết nối: ${r.deleted_orders} đơn + ${r.unlinked_skus} liên kết SKU.`);
            },
        });
    };

    if (accounts.length === 0) return <Empty description="Chưa có kết nối kênh." />;

    return (
        <Table<AdminChannelAccount>
            size="small" rowKey="id" pagination={false}
            dataSource={accounts}
            columns={[
                { title: 'Tên', dataIndex: 'name', key: 'name',
                    render: (_v, r) => (
                        <Space size={10} align="center">
                            <ChannelLogo provider={r.provider} size={28} />
                            <Space direction="vertical" size={0}>
                                <Typography.Text strong>{r.name}</Typography.Text>
                                <Typography.Text type="secondary" style={{ fontSize: 12 }}>{CHANNEL_META[r.provider]?.name ?? r.provider} · #{r.external_shop_id}</Typography.Text>
                            </Space>
                        </Space>
                    ) },
                { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 110,
                    render: (v: string) => <Tag color={v === 'active' ? 'green' : v === 'revoked' ? 'red' : 'orange'}>{v}</Tag> },
                { title: 'Đồng bộ gần nhất', dataIndex: 'last_synced_at', key: 'last_synced_at', width: 160,
                    render: (v: string | null) => formatDateShort(v) },
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

// -- Ads tab (port nguyên vẹn từ Drawer, tách Facebook / TikTok) -------------

function AdsTab({ accounts }: { accounts: AdminAdAccount[] }) {
    if (accounts.length === 0) return <Empty description="Tenant chưa liên kết tài khoản quảng cáo nào." />;

    const groups: { key: string; label: ReactNode; rows: typeof accounts }[] = [
        { key: 'facebook', label: <Space><FacebookFilled style={{ color: '#1877f2' }} /> Facebook Ads</Space>, rows: accounts.filter((a) => a.provider === 'facebook') },
        { key: 'tiktok', label: <Space><TikTokOutlined /> TikTok Ads</Space>, rows: accounts.filter((a) => a.provider === 'tiktok') },
    ];

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            {groups.filter((g) => g.rows.length > 0).map((g) => (
                <div key={g.key}>
                    <Typography.Text strong>{g.label} ({g.rows.length})</Typography.Text>
                    <Table size="small" rowKey="id" pagination={false} style={{ marginTop: 8 }}
                        dataSource={g.rows}
                        columns={[
                            { title: 'Tên', dataIndex: 'name', key: 'name', render: (v: string | null, r) => v ?? r.external_account_id },
                            { title: 'ID', dataIndex: 'external_account_id', key: 'eid', width: 160,
                                render: (v: string) => <Typography.Text copyable={{ text: v }} style={{ fontSize: 12 }}>{v}</Typography.Text> },
                            { title: 'BC/BM', dataIndex: 'business_name', key: 'bn', render: (v: string | null) => v ?? '—' },
                            { title: 'Tiền tệ', dataIndex: 'currency', key: 'cur', width: 90, render: (v: string | null) => v ?? '—' },
                            { title: 'Trạng thái', dataIndex: 'status', key: 'st', width: 110, render: (v: string) => <Tag>{v}</Tag> },
                            { title: 'Đồng bộ gần nhất', dataIndex: 'last_synced_at', key: 'sync', width: 150,
                                render: (v: string | null) => formatDateShort(v) },
                        ]}
                    />
                </div>
            ))}
        </Space>
    );
}

// -- Members tab (port nguyên vẹn, read-only) --------------------------------

function MembersTab({ members }: { members: AdminMember[] }) {
    if (members.length === 0) return <Empty description="Chưa có thành viên." />;

    return (
        <Table size="small" rowKey="user_id" pagination={false}
            dataSource={members}
            columns={[
                { title: 'Tên', dataIndex: 'name', key: 'name', render: (v) => v ?? '—' },
                { title: 'Email', dataIndex: 'email', key: 'email', render: (v) => v ?? '—' },
                { title: 'Vai trò', dataIndex: 'role', key: 'role', width: 140,
                    render: (v: string) => <Tag>{v}</Tag> },
                { title: 'Xác minh email', dataIndex: 'email_verified_at', key: 'verified', width: 130,
                    render: (v: string | null | undefined) => (v ? <Tag color="green">Đã xác minh</Tag> : <Tag color="red">Chưa xác minh</Tag>) },
                { title: 'Super admin', dataIndex: 'is_super_admin', key: 'sa', width: 110,
                    render: (v: boolean) => v ? <Tag color="purple">Có</Tag> : <Tag>—</Tag> },
            ]}
        />
    );
}

// -- AI credit tab (mới) ------------------------------------------------------

function AiCreditTab({ tenantId, t }: { tenantId: number; t: AdminTenantDetail }) {
    const adjust = useAdminTenantAiCreditAdjust();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();
    const [amount, setAmount] = useState<number | null>(null);

    const c = t.ai_credit;

    const onApply = () => {
        if (!amount) {
            message.error('Số lượng phải khác 0.');
            return;
        }
        const amt = amount;
        confirmWithReason({
            title: amt > 0 ? `Cộng ${amt} lượt AI cho tenant?` : `Trừ ${Math.abs(amt)} lượt AI của tenant?`,
            okText: 'Xác nhận',
            onConfirm: async (reason) => {
                await adjust.mutateAsync({ tenantId, amount: amt, reason });
                message.success('Đã cập nhật hạn mức AI.');
                setAmount(null);
            },
        });
    };

    return (
        <>
            <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Bật AI">{c.enabled ? <Tag color="green">Bật</Tag> : <Tag color="red">Tắt</Tag>}</Descriptions.Item>
                <Descriptions.Item label="Không giới hạn">{c.unlimited ? <Tag color="purple">Có</Tag> : <Tag>Không</Tag>}</Descriptions.Item>
                <Descriptions.Item label="Hạn mức tháng">{c.monthly_allowance}</Descriptions.Item>
                <Descriptions.Item label="Đã dùng trong kỳ">{c.period_used}</Descriptions.Item>
                <Descriptions.Item label="Số dư mua thêm">{c.purchased_balance}</Descriptions.Item>
                <Descriptions.Item label="Còn lại">{c.available == null ? '∞' : c.available}</Descriptions.Item>
            </Descriptions>

            <Typography.Title level={5}>Cộng / trừ hạn mức tay</Typography.Title>
            <Space align="start" style={{ marginBottom: 24 }} wrap>
                <InputNumber value={amount} onChange={(v) => setAmount(v)} placeholder="vd: 100 hoặc -50" style={{ width: 160 }} />
                <Button type="primary" onClick={onApply} loading={adjust.isPending}>Áp dụng</Button>
            </Space>

            <Typography.Title level={5}>Lượt dùng theo tháng</Typography.Title>
            <Table size="small" rowKey="period_ym" pagination={false} style={{ marginBottom: 24 }}
                dataSource={t.ai_usage_history.by_month}
                columns={[
                    { title: 'Tháng', dataIndex: 'period_ym', key: 'period_ym' },
                    { title: 'Số lượt', dataIndex: 'count', key: 'count' },
                ]}
                locale={{ emptyText: 'Chưa có lượt dùng.' }}
            />

            <Typography.Title level={5}>Lượt dùng theo tính năng</Typography.Title>
            <Table size="small" rowKey="feature" pagination={false}
                dataSource={t.ai_usage_history.by_feature}
                columns={[
                    { title: 'Tính năng', dataIndex: 'feature', key: 'feature' },
                    { title: 'Số lượt', dataIndex: 'count', key: 'count' },
                ]}
                locale={{ emptyText: 'Chưa có lượt dùng.' }}
            />
        </>
    );
}

// -- SKU & order stats tab (mới) ----------------------------------------------

function OrdersStatsTab({ tenantId, skuCount }: { tenantId: number; skuCount: number }) {
    const { data: daily, isLoading: dailyLoading } = useAdminTenantDailyOrderStats(tenantId, 30);
    const [page, setPage] = useState(1);
    const { data: history, isFetching: historyFetching } = useAdminTenantOrderStatusHistory(tenantId, page);

    return (
        <>
            <Space style={{ marginBottom: 16 }}>
                <Typography.Text>Số SKU:</Typography.Text>
                <Tag color="blue">{skuCount}</Tag>
            </Space>

            <Typography.Title level={5}>Đơn hàng theo ngày (30 ngày gần nhất)</Typography.Title>
            <Table size="small" rowKey="date" loading={dailyLoading} pagination={false} style={{ marginBottom: 24 }}
                dataSource={daily ?? []}
                columns={[
                    { title: 'Ngày', dataIndex: 'date', key: 'date' },
                    { title: 'Số đơn', dataIndex: 'count', key: 'count' },
                    { title: 'Tổng giá trị', dataIndex: 'grand_total_sum', key: 'grand_total_sum',
                        render: (v: number) => `${v.toLocaleString('vi-VN')} đ` },
                ]}
            />

            <Typography.Title level={5}>Lịch sử chuyển trạng thái đơn</Typography.Title>
            <Table size="small" rowKey={(r) => `${r.order_id}-${r.changed_at}`} loading={historyFetching}
                dataSource={history?.data ?? []}
                columns={[
                    { title: 'Đơn', dataIndex: 'order_number', key: 'order_number', render: (v: string | null) => v ?? '—' },
                    { title: 'Chuyển trạng thái', key: 'transition',
                        render: (_v, r) => <span>{r.from_status ?? '—'} → {r.to_status}</span> },
                    { title: 'Nguồn', dataIndex: 'source', key: 'source' },
                    { title: 'Thời gian', dataIndex: 'changed_at', key: 'changed_at',
                        render: (v: string | null) => formatDateTimeSeconds(v) },
                ]}
                pagination={{
                    current: page,
                    pageSize: history?.meta.pagination.per_page ?? 30,
                    total: history?.meta.pagination.total ?? 0,
                    showSizeChanger: false,
                    onChange: setPage,
                }}
            />
        </>
    );
}

// -- Full audit log tab (mới) --------------------------------------------------

function AuditLogTab({ tenantId }: { tenantId: number }) {
    const [page, setPage] = useState(1);
    // Mặc định chỉ xem hành động admin — tab cũ chỉ hiện admin.*, tránh bị chìm giữa log nghiệp vụ thường.
    const [scope, setScope] = useState<'admin' | 'all'>('admin');
    const { data, isFetching } = useAdminTenantAuditLogs(tenantId, page, scope === 'admin' ? 'admin.' : undefined);

    return (
        <>
            <Segmented
                style={{ marginBottom: 12 }}
                value={scope}
                onChange={(v) => { setScope(v as 'admin' | 'all'); setPage(1); }}
                options={[
                    { label: 'Chỉ hành động Admin', value: 'admin' },
                    { label: 'Tất cả', value: 'all' },
                ]}
            />
            <Table size="small" rowKey="id" loading={isFetching}
                dataSource={data?.data ?? []}
                columns={[
                    { title: 'Thời gian', dataIndex: 'created_at', key: 'created_at', width: 160,
                        render: (v: string | null) => formatDateTimeSeconds(v) },
                    { title: 'Hành động', dataIndex: 'action', key: 'action', render: (v: string) => <Tag>{v}</Tag> },
                    { title: 'Người thực hiện', key: 'actor', width: 120,
                        render: (_: unknown, row: AdminFullAuditEntry) => (
                            // `admin_user_id` chỉ được set bởi `AuditLog::record()`, vốn luôn ghi
                            // `tenant_id = NULL` nên hàng đó không bao giờ lọt vào endpoint scope-theo-tenant
                            // này — tín hiệu đáng tin cho "đây là hành động admin" là tiền tố `action`
                            // (`admin.*`), cùng tiêu chí Segmented filter của tab này đang dùng.
                            row.action.startsWith('admin.') ? `Admin #${row.admin_user_id ?? row.user_id ?? '?'}`
                                : row.user_id != null ? `User #${row.user_id}`
                                    : '—'
                        ) },
                    { title: 'Chi tiết', dataIndex: 'changes', key: 'changes',
                        render: (v: Record<string, unknown> | null) => (
                            <pre style={{ margin: 0, fontSize: 12, maxWidth: 420, whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                                {v ? JSON.stringify(v) : '—'}
                            </pre>
                        ) },
                    { title: 'IP', dataIndex: 'ip', key: 'ip', width: 120, render: (v: string | null) => v ?? '—' },
                ]}
                pagination={{
                    current: page,
                    pageSize: data?.meta.pagination.per_page ?? 30,
                    total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: false,
                    onChange: setPage,
                }}
            />
        </>
    );
}

// -- Login history tab (mới) ---------------------------------------------------

function LoginHistoryTab({ tenantId }: { tenantId: number }) {
    const [page, setPage] = useState(1);
    const { data, isFetching } = useAdminTenantLoginHistory(tenantId, page);

    return (
        <Table size="small" rowKey={(r) => `${r.user_id}-${r.logged_in_at}`} loading={isFetching}
            dataSource={data?.data ?? []}
            columns={[
                { title: 'Tên', dataIndex: 'name', key: 'name', render: (v: string | null) => v ?? '—' },
                { title: 'Email', dataIndex: 'email', key: 'email', render: (v: string | null) => v ?? '—' },
                { title: 'IP', dataIndex: 'ip_address', key: 'ip_address', render: (v: string | null) => v ?? '—' },
                { title: 'User agent', dataIndex: 'user_agent', key: 'user_agent',
                    render: (v: string | null) => (
                        <Typography.Text style={{ fontSize: 12 }} type="secondary" ellipsis={{ tooltip: v ?? undefined }}>
                            {v ?? '—'}
                        </Typography.Text>
                    ) },
                { title: 'Đăng nhập lúc', dataIndex: 'logged_in_at', key: 'logged_in_at',
                    render: (v: string) => formatDateTimeSeconds(v) },
            ]}
            pagination={{
                current: page,
                pageSize: data?.meta.pagination.per_page ?? 30,
                total: data?.meta.pagination.total ?? 0,
                showSizeChanger: false,
                onChange: setPage,
            }}
        />
    );
}
