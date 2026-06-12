import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Avatar, Button, Card, Empty, Input, Select, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CloudDownloadOutlined, EditOutlined, PictureOutlined, SearchOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { MoneyText } from '@/components/MoneyText';
import { ChannelLogo } from '@/components/ChannelLogo';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { useSyncPolling } from '@/lib/syncPolling';
import { type ChannelListing, useChannelListings, useSyncChannelListings } from '@/lib/inventory';

const SYNC_TAG: Record<string, { color: string; label: string }> = {
    ok: { color: 'green', label: 'Đã đồng bộ' },
    pending: { color: 'gold', label: 'Đang chờ' },
    error: { color: 'red', label: 'Lỗi' },
};

/**
 * Trang "Sản phẩm đã có trên sàn" — listing thật kéo từ gian hàng về (ChannelListing).
 * Đồng bộ (FetchChannelListings) → hiển thị → sửa (giá/tiêu đề/mô tả/ảnh, đẩy lên sàn) →
 * đồng bộ lại. Tồn kho KHÔNG sửa ở đây — tồn đẩy theo master SKU (mapping).
 */
export function OnChannelPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const { data: channelData } = useChannelAccounts();
    const accounts = useMemo(() => channelData?.data ?? [], [channelData]);

    const [shopIds, setShopIds] = useState<number[]>([]);
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);

    const { data, isFetching, refetch } = useChannelListings({
        page,
        per_page: 20,
        q: q || undefined,
        channel_account_ids: shopIds.length ? shopIds.join(',') : undefined,
    });
    const syncListings = useSyncChannelListings();
    const syncPoll = useSyncPolling(() => refetch(), { durationMs: 90_000 });

    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;
    const shopProvider = (id: number) => accounts.find((a) => a.id === id)?.provider ?? '';

    const handleSync = () => {
        syncListings.mutate(undefined, {
            onSuccess: (r) => {
                if (r.queued > 0) {
                    message.success(`Đang đồng bộ sản phẩm từ ${r.queued} gian hàng…`);
                    syncPoll.start();
                } else {
                    message.info('Chưa có gian hàng nào hỗ trợ đồng bộ sản phẩm.');
                }
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<ChannelListing> = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, r) => (
                <Space>
                    <Avatar shape="square" size={44} src={r.image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf', flex: 'none' }} />
                    <div style={{ minWidth: 0 }}>
                        <Typography.Text strong ellipsis={{ tooltip: r.title ?? r.external_sku_id }} style={{ display: 'block', maxWidth: 340 }}>
                            {r.title ?? r.external_sku_id}
                        </Typography.Text>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                            {[r.variation, r.seller_sku ? `SKU: ${r.seller_sku}` : null].filter(Boolean).join(' · ') || '—'}
                        </Typography.Text>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Gian hàng',
            key: 'shop',
            width: 180,
            render: (_, r) => (
                <Space size={4}>
                    <span>{shopName(r.channel_account_id)}</span>
                    <Tag>{shopProvider(r.channel_account_id)}</Tag>
                </Space>
            ),
        },
        {
            title: 'Giá',
            dataIndex: 'price',
            width: 120,
            align: 'right',
            render: (v: number | null, r) => (v == null ? <Typography.Text type="secondary">—</Typography.Text> : <MoneyText value={v} currency={r.currency} />),
        },
        {
            title: 'Tồn sàn',
            dataIndex: 'channel_stock',
            width: 90,
            align: 'right',
            render: (v: number | null) => v ?? <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'sync_status',
            width: 130,
            render: (s: string, r) => {
                const meta = SYNC_TAG[s] ?? SYNC_TAG.pending;
                return (
                    <Space size={4}>
                        <Tag color={meta.color}>{meta.label}</Tag>
                        {!r.is_active && <Tag>Ẩn</Tag>}
                    </Space>
                );
            },
        },
        {
            title: '',
            key: 'actions',
            width: 130,
            render: (_, r) => (
                <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/marketplace/on-channel/${r.id}/edit`)}>
                    Sửa trên sàn
                </Button>
            ),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Sản phẩm đã có trên sàn"
                subtitle="Đồng bộ sản phẩm thật từ các gian hàng đã kết nối — sửa giá / tiêu đề / mô tả / ảnh rồi đẩy lại. Tồn kho đẩy theo SKU ở mục Tồn kho."
                extra={
                    <Button
                        type="primary"
                        icon={<CloudDownloadOutlined />}
                        loading={syncListings.isPending || syncPoll.isPolling}
                        onClick={handleSync}
                    >
                        Đồng bộ sản phẩm
                    </Button>
                }
            />

            <Card styles={{ body: { padding: 16 } }}>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Select
                        mode="multiple"
                        allowClear
                        style={{ minWidth: 300 }}
                        placeholder="Lọc theo gian hàng"
                        value={shopIds}
                        onChange={(v) => { setShopIds(v as number[]); setPage(1); }}
                        optionFilterProp="title"
                        maxTagCount="responsive"
                        options={accounts.map((a) => ({
                            value: a.id,
                            title: a.name,
                            label: (
                                <Space size={6}>
                                    <ChannelLogo provider={a.provider} size={16} />
                                    <span>{a.name}</span>
                                </Space>
                            ),
                        }))}
                    />
                    <Input
                        allowClear
                        prefix={<SearchOutlined />}
                        placeholder="Tìm theo tên / SKU sàn"
                        style={{ width: 260 }}
                        value={q}
                        onChange={(e) => { setQ(e.target.value); setPage(1); }}
                    />
                </Space>

                <Table<ChannelListing>
                    rowKey="id"
                    size="middle"
                    loading={isFetching}
                    dataSource={data?.data ?? []}
                    columns={columns}
                    locale={{ emptyText: <Empty description="Chưa có sản phẩm nào. Bấm “Đồng bộ sản phẩm” để kéo sản phẩm của gian hàng về." /> }}
                    pagination={{
                        current: page,
                        pageSize: 20,
                        total: data?.meta?.pagination?.total ?? 0,
                        showSizeChanger: false,
                        onChange: setPage,
                    }}
                />
            </Card>
        </div>
    );
}
