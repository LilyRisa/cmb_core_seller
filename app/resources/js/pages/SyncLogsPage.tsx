import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { App, Button, Card, Empty, Popconfirm, Select, Space, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import { ReloadOutlined, RedoOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { DateText } from '@/components/MoneyText';
import { useChannelAccounts } from '@/lib/channels';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';
import {
    SyncRun, WebhookEvent,
    useSyncRuns, useWebhookEvents, useRedriveSyncRun, useRedriveWebhook,
    SYNC_RUN_TYPE_LABEL, SYNC_RUN_STATUS, WEBHOOK_STATUS, WEBHOOK_EVENT_TYPE_LABEL,
} from '@/lib/syncLogs';

export function SyncLogsPage() {
    const [params, setParams] = useSearchParams();
    const canManage = useCan('channels.manage');
    const tab = params.get('tab') === 'webhooks' ? 'webhooks' : 'runs';

    return (
        <div>
            <PageHeader
                title="Nhật ký đồng bộ"
                subtitle="Theo dõi các lần đồng bộ đơn (định kỳ / lấy lại lịch sử) và webhook từ sàn — chạy lại khi thất bại"
            />
            <Card styles={{ body: { padding: '8px 16px 0' } }}>
                <Tabs
                    activeKey={tab}
                    onChange={(k) => { const m = new URLSearchParams(); m.set('tab', k); setParams(m, { replace: true }); }}
                    items={[
                        { key: 'runs', label: 'Lần đồng bộ' },
                        { key: 'webhooks', label: 'Webhook' },
                    ]}
                />
            </Card>
            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                {tab === 'runs' ? <SyncRunsTab canManage={canManage} /> : <WebhookEventsTab canManage={canManage} />}
            </Card>
        </div>
    );
}

function useShopOptions() {
    const { data } = useChannelAccounts();
    return (data?.data ?? []).map((a) => ({
        value: a.id,
        label: (
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                <ChannelLogo provider={a.provider} size={14} />
                {a.shop_name ?? a.external_shop_id}
            </span>
        ),
    }));
}

// --- Sync runs --------------------------------------------------------------

function SyncRunsTab({ canManage }: { canManage: boolean }) {
    const { message } = App.useApp();
    const [params, setParams] = useSearchParams();
    const shopOptions = useShopOptions();
    const redrive = useRedriveSyncRun();

    const channelAccountId = params.get('channel_account_id') ?? '';
    const type = params.get('type') ?? '';
    const status = params.get('status') ?? '';
    const page = Number(params.get('page') ?? 1);
    const perPage = Number(params.get('per_page') ?? 20);

    const set = (next: Record<string, string | number | undefined | null>) => {
        const merged = new URLSearchParams(params);
        Object.entries(next).forEach(([k, v]) => { if (v == null || v === '') merged.delete(k); else merged.set(k, String(v)); });
        if (!('page' in next)) merged.set('page', '1');
        setParams(merged, { replace: true });
    };

    const filters = useMemo(() => ({
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        type: type || undefined,
        status: status || undefined,
        page, per_page: perPage,
    }), [channelAccountId, type, status, page, perPage]);

    const { data, isFetching, refetch } = useSyncRuns(filters);

    const onRedrive = (run: SyncRun) => redrive.mutate(run.id, {
        onSuccess: () => message.success('Đã đưa vào hàng đợi để chạy lại.'),
        onError: (e) => message.error(errorMessage(e)),
    });

    const columns: ColumnsType<SyncRun> = [
        { title: 'Gian hàng', key: 'shop', width: 220, render: (_, r) => <Space size={6}>{r.provider && <ChannelBadge provider={r.provider} />}<span>{r.shop_name ?? `#${r.channel_account_id}`}</span></Space> },
        { title: 'Loại', dataIndex: 'type', key: 'type', width: 130, render: (v) => SYNC_RUN_TYPE_LABEL[v] ?? v },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 120, render: (v, r) => <Space>{r.status === 'failed' && <WarningOutlined style={{ color: '#cf1322' }} />}<Tag color={SYNC_RUN_STATUS[v]?.color ?? 'default'} style={{ marginInlineEnd: 0 }}>{SYNC_RUN_STATUS[v]?.label ?? v}</Tag></Space> },
        { title: 'Kết quả', key: 'stats', width: 280, render: (_, r) => (
            <Space size={4} wrap>
                <Tag>nhận {r.stats.fetched}</Tag>
                {r.stats.created > 0 && <Tag color="green">mới {r.stats.created}</Tag>}
                {r.stats.updated > 0 && <Tag color="blue">cập nhật {r.stats.updated}</Tag>}
                {r.stats.skipped > 0 && <Tag>bỏ qua {r.stats.skipped}</Tag>}
                {r.stats.errors > 0 && <Tag color="red">lỗi {r.stats.errors}</Tag>}
            </Space>
        ) },
        { title: 'Bắt đầu', dataIndex: 'started_at', key: 'started_at', width: 150, render: (v) => <DateText value={v} /> },
        { title: 'Thời lượng', dataIndex: 'duration_seconds', key: 'dur', width: 100, render: (v) => (v == null ? '—' : `${v}s`) },
        { title: 'Lỗi', dataIndex: 'error', key: 'error', ellipsis: true, render: (v) => (v ? <Tooltip title={v}><Typography.Text type="danger" ellipsis style={{ maxWidth: 240 }}>{v}</Typography.Text></Tooltip> : '—') },
        ...(canManage ? [{
            title: '', key: 'action', width: 110, fixed: 'right' as const,
            render: (_: unknown, r: SyncRun) => (
                <Popconfirm title="Chạy lại đồng bộ cho gian hàng này?" onConfirm={() => onRedrive(r)} okText="Chạy lại" cancelText="Huỷ">
                    <Button size="small" icon={<RedoOutlined />} loading={redrive.isPending && redrive.variables === r.id}>Chạy lại</Button>
                </Popconfirm>
            ),
        }] : []),
    ];

    return (
        <>
            <Space wrap style={{ marginBottom: 12 }}>
                <Select allowClear placeholder="Tất cả gian hàng" style={{ width: 240 }} value={channelAccountId || undefined} onChange={(v) => set({ channel_account_id: v })} options={shopOptions} />
                <Select allowClear placeholder="Tất cả loại" style={{ width: 170 }} value={type || undefined} onChange={(v) => set({ type: v })} options={[
                    { value: 'poll', label: 'Định kỳ' }, { value: 'backfill', label: 'Lấy lại lịch sử' }, { value: 'webhook', label: 'Webhook' },
                ]} />
                <Select allowClear placeholder="Tất cả trạng thái" style={{ width: 170 }} value={status || undefined} onChange={(v) => set({ status: v })} options={[
                    { value: 'running', label: 'Đang chạy' }, { value: 'done', label: 'Hoàn tất' }, { value: 'failed', label: 'Thất bại' },
                ]} />
                <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
            </Space>
            <Table<SyncRun>
                rowKey="id" size="middle" loading={isFetching} scroll={{ x: 1100 }}
                dataSource={data?.data ?? []} columns={columns}
                locale={{ emptyText: <Empty description="Chưa có lần đồng bộ nào. Kết nối gian hàng để bắt đầu." /> }}
                rowClassName={(r) => (r.status === 'failed' ? 'row-has-issue' : '')}
                pagination={{
                    current: data?.meta.pagination.page ?? page,
                    pageSize: data?.meta.pagination.per_page ?? perPage,
                    total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: true, pageSizeOptions: [20, 50, 100],
                    showTotal: (t) => `${t} lần`,
                    onChange: (p, ps) => set({ page: p, per_page: ps }),
                }}
            />
        </>
    );
}

// --- Webhook events ---------------------------------------------------------

function WebhookEventsTab({ canManage }: { canManage: boolean }) {
    const { message } = App.useApp();
    const [params, setParams] = useSearchParams();
    const shopOptions = useShopOptions();
    const redrive = useRedriveWebhook();

    const channelAccountId = params.get('channel_account_id') ?? '';
    const eventType = params.get('event_type') ?? '';
    const status = params.get('status') ?? '';
    const page = Number(params.get('page') ?? 1);
    const perPage = Number(params.get('per_page') ?? 20);

    const set = (next: Record<string, string | number | undefined | null>) => {
        const merged = new URLSearchParams(params);
        Object.entries(next).forEach(([k, v]) => { if (v == null || v === '') merged.delete(k); else merged.set(k, String(v)); });
        if (!('page' in next)) merged.set('page', '1');
        setParams(merged, { replace: true });
    };

    const filters = useMemo(() => ({
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        event_type: eventType || undefined,
        status: status || undefined,
        page, per_page: perPage,
    }), [channelAccountId, eventType, status, page, perPage]);

    const { data, isFetching, refetch } = useWebhookEvents(filters);

    const onRedrive = (ev: WebhookEvent) => redrive.mutate(ev.id, {
        onSuccess: () => message.success('Đã đưa webhook vào hàng đợi để xử lý lại.'),
        onError: (e) => message.error(errorMessage(e)),
    });

    const columns: ColumnsType<WebhookEvent> = [
        { title: 'Sàn / Gian hàng', key: 'shop', width: 220, render: (_, e) => <Space direction="vertical" size={2}><ChannelBadge provider={e.provider} /><Typography.Text type="secondary" style={{ fontSize: 12 }}>{e.shop_name ?? e.external_shop_id ?? '—'}</Typography.Text></Space> },
        { title: 'Sự kiện', dataIndex: 'event_type', key: 'event_type', width: 200, render: (v, e) => <Space direction="vertical" size={0}><span>{WEBHOOK_EVENT_TYPE_LABEL[v] ?? v}</span>{e.raw_type && <Typography.Text type="secondary" style={{ fontSize: 12 }}>raw: {e.raw_type}</Typography.Text>}</Space> },
        { title: 'Mã tham chiếu', dataIndex: 'external_id', key: 'external_id', width: 170, render: (v) => v ?? '—' },
        { title: 'Chữ ký', dataIndex: 'signature_ok', key: 'sig', width: 90, render: (v) => v ? <Tag color="green">Hợp lệ</Tag> : <Tag color="red">Sai</Tag> },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 120, render: (v) => <Tag color={WEBHOOK_STATUS[v]?.color ?? 'default'} style={{ marginInlineEnd: 0 }}>{WEBHOOK_STATUS[v]?.label ?? v}</Tag> },
        { title: 'Số lần', dataIndex: 'attempts', key: 'attempts', width: 80, align: 'right' },
        { title: 'Nhận lúc', dataIndex: 'received_at', key: 'received_at', width: 150, render: (v) => <DateText value={v} /> },
        { title: 'Lỗi', dataIndex: 'error', key: 'error', ellipsis: true, render: (v) => (v ? <Tooltip title={v}><Typography.Text type="danger" ellipsis style={{ maxWidth: 220 }}>{v}</Typography.Text></Tooltip> : '—') },
        ...(canManage ? [{
            title: '', key: 'action', width: 110, fixed: 'right' as const,
            render: (_: unknown, e: WebhookEvent) => (
                <Popconfirm title="Xử lý lại webhook này?" onConfirm={() => onRedrive(e)} okText="Xử lý lại" cancelText="Huỷ">
                    <Button size="small" icon={<RedoOutlined />} loading={redrive.isPending && redrive.variables === e.id}>Xử lý lại</Button>
                </Popconfirm>
            ),
        }] : []),
    ];

    return (
        <>
            <Space wrap style={{ marginBottom: 12 }}>
                <Select allowClear placeholder="Tất cả gian hàng" style={{ width: 240 }} value={channelAccountId || undefined} onChange={(v) => set({ channel_account_id: v })} options={shopOptions} />
                <Select allowClear placeholder="Tất cả sự kiện" style={{ width: 220 }} value={eventType || undefined} onChange={(v) => set({ event_type: v })} options={Object.entries(WEBHOOK_EVENT_TYPE_LABEL).map(([value, label]) => ({ value, label }))} />
                <Select allowClear placeholder="Tất cả trạng thái" style={{ width: 170 }} value={status || undefined} onChange={(v) => set({ status: v })} options={[
                    { value: 'pending', label: 'Chờ xử lý' }, { value: 'processed', label: 'Đã xử lý' }, { value: 'ignored', label: 'Bỏ qua' }, { value: 'failed', label: 'Thất bại' },
                ]} />
                <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
            </Space>
            <Table<WebhookEvent>
                rowKey="id" size="middle" loading={isFetching} scroll={{ x: 1200 }}
                dataSource={data?.data ?? []} columns={columns}
                locale={{ emptyText: <Empty description="Chưa nhận webhook nào." /> }}
                rowClassName={(e) => (e.status === 'failed' ? 'row-has-issue' : '')}
                pagination={{
                    current: data?.meta.pagination.page ?? page,
                    pageSize: data?.meta.pagination.per_page ?? perPage,
                    total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: true, pageSizeOptions: [20, 50, 100],
                    showTotal: (t) => `${t} webhook`,
                    onChange: (p, ps) => set({ page: p, per_page: ps }),
                }}
            />
        </>
    );
}
