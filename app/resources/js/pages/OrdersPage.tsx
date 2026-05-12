import { useMemo } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Avatar, Badge, Button, Card, DatePicker, Empty, Input, Select, Space, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import { ReloadOutlined, SearchOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { ORDER_STATUS_TABS } from '@/lib/format';
import { Order, useOrders, useOrderStats } from '@/lib/orders';
import { useChannelAccounts } from '@/lib/channels';

const { RangePicker } = DatePicker;

export function OrdersPage() {
    const [params, setParams] = useSearchParams();
    const { data: channelsData } = useChannelAccounts();
    const accounts = channelsData?.data ?? [];

    const tabKey = params.get('tab') ?? (params.get('has_issue') ? 'issue' : '');
    const statusParam = params.get('status') ?? '';
    const q = params.get('q') ?? '';
    const channelAccountId = params.get('channel_account_id') ?? '';
    const placedFrom = params.get('placed_from') ?? '';
    const placedTo = params.get('placed_to') ?? '';
    const page = Number(params.get('page') ?? 1);
    const perPage = Number(params.get('per_page') ?? 20);
    const sort = params.get('sort') ?? '-placed_at';

    const set = (next: Record<string, string | number | undefined | null>) => {
        const merged = new URLSearchParams(params);
        Object.entries(next).forEach(([k, v]) => { if (v == null || v === '') merged.delete(k); else merged.set(k, String(v)); });
        if (!('page' in next)) merged.set('page', '1');
        setParams(merged, { replace: true });
    };

    const activeTab = ORDER_STATUS_TABS.find((t) => t.key === tabKey) ?? ORDER_STATUS_TABS[0];
    const effectiveStatus = tabKey === 'issue' ? '' : (statusParam || activeTab.statuses.join(','));

    const filters = useMemo(() => ({
        status: effectiveStatus || undefined,
        q: q || undefined,
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        placed_from: placedFrom || undefined,
        placed_to: placedTo || undefined,
        has_issue: tabKey === 'issue' || params.get('has_issue') === '1' ? true : undefined,
        sort, page, per_page: perPage,
    }), [effectiveStatus, q, channelAccountId, placedFrom, placedTo, tabKey, params, sort, page, perPage]);

    const { data, isFetching, refetch } = useOrders(filters);
    const { data: stats } = useOrderStats({
        q: q || undefined,
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        placed_from: placedFrom || undefined,
        placed_to: placedTo || undefined,
    });

    const countFor = (statuses: string[]) => statuses.reduce((s, st) => s + (stats?.by_status?.[st] ?? 0), 0);

    const columns: ColumnsType<Order> = [
        {
            title: 'Đơn hàng', key: 'order', width: 230,
            render: (_, o) => (
                <Space direction="vertical" size={2}>
                    <Link to={`/orders/${o.id}`} style={{ fontWeight: 600 }}>{o.order_number ?? o.external_order_id ?? `#${o.id}`}</Link>
                    <Space size={4}>
                        <ChannelBadge provider={o.source} />
                        {o.is_cod && <Tag color="gold">COD</Tag>}
                        {o.has_issue && <Tooltip title={o.issue_reason ?? 'Đơn có vấn đề'}><Tag color="error" icon={<WarningOutlined />}>Lỗi</Tag></Tooltip>}
                    </Space>
                </Space>
            ),
        },
        {
            title: 'Sản phẩm', key: 'items',
            render: (_, o) => (
                <Space>
                    <Avatar shape="square" size={40} style={{ background: '#f0f0f0' }}>
                        {(o.items_count ?? 0)}
                    </Avatar>
                    <Typography.Text type="secondary">{o.items_count ?? 0} mặt hàng</Typography.Text>
                </Space>
            ),
        },
        { title: 'Người mua', dataIndex: 'buyer_name', key: 'buyer', width: 180, render: (v, o) => <Space direction="vertical" size={0}><span>{v ?? '—'}</span><Typography.Text type="secondary" style={{ fontSize: 12 }}>{o.buyer_phone_masked ?? ''}</Typography.Text></Space> },
        { title: 'Tổng tiền', dataIndex: 'grand_total', key: 'total', width: 130, align: 'right', render: (v, o) => <MoneyText value={v} currency={o.currency} strong /> },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 140, render: (v, o) => <StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /> },
        { title: 'Đặt lúc', dataIndex: 'placed_at', key: 'placed_at', width: 150, render: (v) => <DateText value={v} /> },
        { title: '', key: 'action', width: 60, render: (_, o) => <Link to={`/orders/${o.id}`}>Xem</Link> },
    ];

    return (
        <div>
            <PageHeader
                title="Đơn hàng"
                subtitle="Đơn từ mọi sàn — đồng bộ tự động qua webhook + polling"
                extra={<Space><Link to="/orders/new"><Button type="primary">Tạo đơn</Button></Link><Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button></Space>}
            />

            <Card styles={{ body: { padding: '8px 16px 0' } }}>
                <Tabs
                    activeKey={tabKey}
                    onChange={(k) => set({ tab: k || undefined, status: undefined, has_issue: k === 'issue' ? '1' : undefined })}
                    items={[
                        ...ORDER_STATUS_TABS.map((t) => ({
                            key: t.key,
                            label: <span>{t.label}{t.key !== '' && stats ? <Badge count={countFor(t.statuses)} overflowCount={9999} showZero={false} style={{ marginInlineStart: 6, background: '#f0f0f0', color: '#595959' }} /> : null}</span>,
                        })),
                        { key: 'issue', label: <span>Có vấn đề{stats?.has_issue ? <Badge count={stats.has_issue} style={{ marginInlineStart: 6 }} /> : null}</span> },
                    ]}
                />
            </Card>

            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                <Space wrap style={{ marginBottom: 12 }}>
                    <Input.Search allowClear placeholder="Mã đơn / tên người mua" prefix={<SearchOutlined />} style={{ width: 260 }} defaultValue={q} onSearch={(v) => set({ q: v || undefined })} />
                    <Select allowClear placeholder="Tất cả gian hàng" style={{ width: 220 }} value={channelAccountId || undefined} onChange={(v) => set({ channel_account_id: v })} options={accounts.map((a) => ({ value: a.id, label: `${a.shop_name ?? a.external_shop_id} (${a.provider})` }))} />
                    <RangePicker
                        value={placedFrom && placedTo ? [dayjs(placedFrom), dayjs(placedTo)] : null}
                        onChange={(v) => set({ placed_from: v?.[0]?.format('YYYY-MM-DD'), placed_to: v?.[1]?.format('YYYY-MM-DD') })}
                        placeholder={['Đặt từ', 'đến']}
                    />
                    <Select value={sort} style={{ width: 180 }} onChange={(v) => set({ sort: v })} options={[
                        { value: '-placed_at', label: 'Mới đặt trước' },
                        { value: 'placed_at', label: 'Cũ trước' },
                        { value: '-grand_total', label: 'Giá trị cao trước' },
                        { value: 'grand_total', label: 'Giá trị thấp trước' },
                    ]} />
                </Space>

                <Table<Order>
                    rowKey="id"
                    size="middle"
                    loading={isFetching}
                    dataSource={data?.data ?? []}
                    columns={columns}
                    locale={{ emptyText: <Empty description="Chưa có đơn hàng. Kết nối gian hàng để đơn tự về." /> }}
                    rowClassName={(o) => (o.has_issue ? 'row-has-issue' : '')}
                    pagination={{
                        current: data?.meta.pagination.page ?? page,
                        pageSize: data?.meta.pagination.per_page ?? perPage,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: true, pageSizeOptions: [20, 50, 100],
                        showTotal: (t) => `${t} đơn`,
                        onChange: (p, ps) => set({ page: p, per_page: ps }),
                    }}
                />
            </Card>
        </div>
    );
}
