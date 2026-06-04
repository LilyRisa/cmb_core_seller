import { useSearchParams } from 'react-router-dom';
import { App, Badge, Button, Card, Empty, Popconfirm, Segmented, Space, Table, Tag } from 'antd';
import { CheckOutlined, CloseOutlined, ReloadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { ChannelLogo } from '@/components/ChannelLogo';
import { MoneyText, DateText } from '@/components/MoneyText';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';
import {
    AFTER_SALES_STATUS_LABEL, AfterSalesStatus, KIND_LABEL, ReturnRecord,
    useReturns, useReturnStats, useDecideReturn,
} from '@/lib/returns';

const STATUS_COLOR: Record<AfterSalesStatus, string> = {
    requested: 'gold', approved: 'blue', processing: 'cyan', completed: 'green', rejected: 'red', cancelled: 'default',
};

const KIND_COLOR: Record<ReturnRecord['kind'], string> = { cancel: 'volcano', return: 'orange', refund: 'purple' };

export function ReturnsPage() {
    const [params, setParams] = useSearchParams();
    const { message } = App.useApp();
    const canManage = useCan('orders.update');
    const decide = useDecideReturn();

    // filter "view": chờ duyệt | đang mở | tất cả
    const view = params.get('view') ?? 'requested';
    const filters = {
        ...(view === 'requested' ? { status: 'requested' } : view === 'open' ? { open_only: true } : {}),
        ...(params.get('kind') ? { kind: params.get('kind') as string } : {}),
        page: Number(params.get('page') ?? 1),
    };
    const { data, isLoading, refetch, isFetching } = useReturns(filters);
    const { data: stats } = useReturnStats();

    const setView = (v: string) => { const m = new URLSearchParams(params); m.set('view', v); m.delete('page'); setParams(m, { replace: true }); };

    const onDecide = (id: number, action: 'approve' | 'reject') => {
        decide.mutate({ id, action }, {
            onSuccess: () => message.success(action === 'approve' ? 'Đã duyệt yêu cầu.' : 'Đã từ chối yêu cầu.'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<ReturnRecord> = [
        {
            title: 'Đơn', key: 'order', render: (_, r) => (
                <Space size={6}>
                    <ChannelLogo provider={r.source} size={16} />
                    <span>{r.order_number ?? r.external_order_id ?? '—'}</span>
                </Space>
            ),
        },
        { title: 'Loại', dataIndex: 'kind', key: 'kind', render: (k: ReturnRecord['kind']) => <Tag color={KIND_COLOR[k]}>{KIND_LABEL[k] ?? k}</Tag> },
        {
            title: 'Trạng thái', dataIndex: 'status', key: 'status',
            render: (s: AfterSalesStatus) => <Tag color={STATUS_COLOR[s] ?? 'default'}>{AFTER_SALES_STATUS_LABEL[s] ?? s}</Tag>,
        },
        { title: 'Lý do', dataIndex: 'reason', key: 'reason', ellipsis: true, render: (v: string | null) => v || '—' },
        { title: 'Hoàn tiền', dataIndex: 'refund_amount', key: 'refund_amount', align: 'right', render: (v: number, r) => <MoneyText value={v} currency={r.currency} /> },
        { title: 'Yêu cầu lúc', dataIndex: 'requested_at', key: 'requested_at', render: (v: string | null) => <DateText value={v} /> },
        {
            title: '', key: 'actions', align: 'right', render: (_, r) => (
                canManage && r.status === 'requested' ? (
                    <Space>
                        <Popconfirm title="Duyệt yêu cầu này?" onConfirm={() => onDecide(r.id, 'approve')} okText="Duyệt" cancelText="Huỷ">
                            <Button size="small" type="primary" icon={<CheckOutlined />} loading={decide.isPending}>Duyệt</Button>
                        </Popconfirm>
                        <Popconfirm title="Từ chối yêu cầu này?" onConfirm={() => onDecide(r.id, 'reject')} okText="Từ chối" okButtonProps={{ danger: true }} cancelText="Huỷ">
                            <Button size="small" danger icon={<CloseOutlined />} loading={decide.isPending}>Từ chối</Button>
                        </Popconfirm>
                    </Space>
                ) : null
            ),
        },
    ];

    return (
        <div>
            <PageHeader
                title={<Space>Đơn Hoàn & Hủy {stats?.requested ? <Badge count={stats.requested} /> : null}</Space>}
                subtitle="Yêu cầu hoàn/trả/hủy từ sàn (TikTok · Shopee · Lazada) — duyệt hoặc từ chối"
                extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>}
            />
            <Card styles={{ body: { padding: 16 } }}>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Segmented
                        value={view}
                        onChange={(v) => setView(String(v))}
                        options={[
                            { label: `Chờ duyệt${stats?.requested ? ` (${stats.requested})` : ''}`, value: 'requested' },
                            { label: `Đang mở${stats?.open ? ` (${stats.open})` : ''}`, value: 'open' },
                            { label: 'Tất cả', value: 'all' },
                        ]}
                    />
                    <Segmented
                        value={params.get('kind') ?? 'all'}
                        onChange={(v) => { const m = new URLSearchParams(params); if (v === 'all') m.delete('kind'); else m.set('kind', String(v)); m.delete('page'); setParams(m, { replace: true }); }}
                        options={[{ label: 'Mọi loại', value: 'all' }, { label: 'Trả hàng', value: 'return' }, { label: 'Hoàn tiền', value: 'refund' }, { label: 'Hủy đơn', value: 'cancel' }]}
                    />
                </Space>
                <Table<ReturnRecord>
                    rowKey="id"
                    size="small"
                    loading={isLoading}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    locale={{ emptyText: <Empty description="Chưa có đơn hoàn/hủy" /> }}
                    pagination={{
                        current: data?.meta.pagination.page ?? 1,
                        pageSize: data?.meta.pagination.per_page ?? 20,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: false,
                        onChange: (p) => { const m = new URLSearchParams(params); m.set('page', String(p)); setParams(m, { replace: true }); },
                    }}
                />
            </Card>
        </div>
    );
}
