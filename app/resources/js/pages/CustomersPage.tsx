import { useMemo } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Card, Empty, Input, Select, Space, Table, Tabs, Tag, Typography } from 'antd';
import { CheckCircleOutlined, CloseCircleOutlined, SearchOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { ReputationBadge } from '@/components/ReputationBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { Customer, REPUTATION_TABS, useCustomers } from '@/lib/customers';

export function CustomersPage() {
    const [params, setParams] = useSearchParams();
    const tab = params.get('reputation') ?? '';
    const q = params.get('q') ?? '';
    const sort = params.get('sort') ?? '-last_seen_at';
    const page = Number(params.get('page') ?? 1);
    const perPage = Number(params.get('per_page') ?? 20);

    const set = (next: Record<string, string | number | undefined | null>) => {
        const merged = new URLSearchParams(params);
        Object.entries(next).forEach(([k, v]) => { if (v == null || v === '') merged.delete(k); else merged.set(k, String(v)); });
        if (!('page' in next)) merged.set('page', '1');
        setParams(merged, { replace: true });
    };

    const filters = useMemo(() => ({
        reputation: tab || undefined,
        q: q || undefined,
        sort, page, per_page: perPage,
    }), [tab, q, sort, page, perPage]);

    const { data, isFetching } = useCustomers(filters);

    const columns: ColumnsType<Customer> = [
        { title: 'Khách hàng', key: 'name', render: (_, c) => (
            <Space direction="vertical" size={2}>
                <Link to={`/customers/${c.id}`} style={{ fontWeight: 600 }}>{c.name ?? 'Khách lẻ'}</Link>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>{c.phone_masked ?? '—'}</Typography.Text>
            </Space>
        ) },
        { title: 'Uy tín', key: 'rep', width: 160, render: (_, c) => <ReputationBadge label={c.is_blocked ? 'blocked' : c.reputation.label} score={c.reputation.score} showOk /> },
        { title: 'Nhãn', dataIndex: 'tags', key: 'tags', width: 160, render: (tags: string[]) => <Space size={4} wrap>{(tags ?? []).map((t) => <Tag key={t} color={t === 'vip' ? 'purple' : 'blue'}>{t}</Tag>)}</Space> },
        { title: 'Đơn', key: 'orders', width: 200, render: (_, c) => {
            const s = c.lifetime_stats;
            return <Typography.Text type="secondary" style={{ fontSize: 13 }}>{s.orders_total} đơn · {s.orders_completed} <CheckCircleOutlined style={{ color: '#52c41a' }} /> · {s.orders_cancelled} <CloseCircleOutlined style={{ color: '#cf1322' }} /></Typography.Text>;
        } },
        { title: 'Doanh thu', key: 'revenue', width: 130, align: 'right', render: (_, c) => <MoneyText value={c.lifetime_stats.revenue_completed ?? 0} /> },
        { title: 'Gần nhất', dataIndex: 'last_seen_at', key: 'last_seen_at', width: 150, render: (v) => <DateText value={v} /> },
    ];

    return (
        <div>
            <PageHeader title="Khách hàng" subtitle="Sổ khách hàng nội bộ — khớp đơn theo số điện thoại, lịch sử mua & cờ rủi ro" />

            <Card styles={{ body: { padding: '8px 16px 0' } }}>
                <Tabs activeKey={tab} onChange={(k) => set({ reputation: k || undefined })}
                    items={REPUTATION_TABS.map((t) => ({ key: t.key, label: t.label }))} />
            </Card>

            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                <Space wrap style={{ marginBottom: 12 }}>
                    <Input.Search allowClear placeholder="Tên hoặc số điện thoại" prefix={<SearchOutlined />} style={{ width: 280 }} defaultValue={q} onSearch={(v) => set({ q: v || undefined })} />
                    <Select value={sort} style={{ width: 200 }} onChange={(v) => set({ sort: v })} options={[
                        { value: '-last_seen_at', label: 'Mua gần nhất' },
                        { value: '-lifetime_revenue', label: 'Doanh thu cao' },
                        { value: '-orders_total', label: 'Nhiều đơn nhất' },
                        { value: '-cancellation_rate', label: 'Huỷ nhiều nhất' },
                        { value: '-reputation_score', label: 'Uy tín cao' },
                        { value: 'reputation_score', label: 'Uy tín thấp' },
                    ]} />
                </Space>

                <Table<Customer>
                    rowKey="id" size="middle" loading={isFetching}
                    dataSource={data?.data ?? []} columns={columns}
                    locale={{ emptyText: <Empty description="Chưa có khách hàng. Khách sẽ tự xuất hiện khi đơn có số điện thoại được đồng bộ về." /> }}
                    pagination={{
                        current: data?.meta.pagination.page ?? page,
                        pageSize: data?.meta.pagination.per_page ?? perPage,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: true, pageSizeOptions: [20, 50, 100],
                        showTotal: (t) => `${t} khách`,
                        onChange: (p, ps) => set({ page: p, per_page: ps }),
                    }}
                />
            </Card>
        </div>
    );
}
