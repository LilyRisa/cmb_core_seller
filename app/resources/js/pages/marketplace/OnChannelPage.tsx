import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Avatar, Button, Card, Checkbox, Empty, Input, Modal, Select, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CloudDownloadOutlined, CopyOutlined, EditOutlined, PictureOutlined, QuestionCircleOutlined, SearchOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { MoneyText } from '@/components/MoneyText';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { useSyncPolling } from '@/lib/syncPolling';
import { type ChannelListing, useChannelListings, useSyncChannelListings } from '@/lib/inventory';
import { useCloneChannelListingToShops } from '@/features/products/hooks';

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
    const [cloneFor, setCloneFor] = useState<ChannelListing | null>(null);
    const [cloneShopIds, setCloneShopIds] = useState<number[]>([]);

    const { data, isFetching, refetch } = useChannelListings({
        page,
        per_page: 20,
        q: q || undefined,
        channel_account_ids: shopIds.length ? shopIds.join(',') : undefined,
    });
    const syncListings = useSyncChannelListings();
    const syncPoll = useSyncPolling(() => refetch(), { durationMs: 90_000 });
    const clone = useCloneChannelListingToShops();

    // Gian hàng đích = mọi shop trừ shop nguồn của listing đang sao chép.
    const cloneTargets = useMemo(
        () => (cloneFor ? accounts.filter((a) => a.id !== cloneFor.channel_account_id) : []),
        [accounts, cloneFor],
    );

    const openClone = (listing: ChannelListing) => {
        setCloneFor(listing);
        setCloneShopIds([]);
    };

    const handleClone = () => {
        if (!cloneFor || cloneShopIds.length === 0) return;
        clone.mutate(
            { id: cloneFor.id, channelAccountIds: cloneShopIds },
            {
                onSuccess: (created) => {
                    setCloneFor(null);
                    const ready = created.filter((c) => c.status === 'ready').length;
                    message.success(`Đã sao chép sang ${created.length} gian hàng (${ready} sẵn sàng đẩy). Hoàn tất ở "Chờ đẩy lên sàn".`);
                    navigate('/marketplace/to-push');
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

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
            width: 220,
            render: (_, r) => {
                const provider = shopProvider(r.channel_account_id);
                return (
                    <Space size={4} wrap>
                        <ChannelBadge provider={provider} />
                        <Tag style={{ display: 'inline-flex', alignItems: 'center', gap: 4, paddingInline: 6 }}>
                            <ChannelLogo provider={provider} size={12} />
                            <span>{shopName(r.channel_account_id)}</span>
                        </Tag>
                    </Space>
                );
            },
        },
        {
            title: 'Giá gốc',
            dataIndex: 'original_price',
            width: 120,
            align: 'right',
            render: (v: number | null, r) => {
                const base = v ?? r.price;
                return base == null ? <Typography.Text type="secondary">—</Typography.Text> : <MoneyText value={base} currency={r.currency} />;
            },
        },
        {
            title: 'Giá sau giảm',
            key: 'sale_price',
            width: 150,
            align: 'right',
            // Giá đang bán = special_price (KM đang chạy trên sàn) nếu có, ngược lại giá thường.
            render: (_, r) => {
                const sale = r.special_price ?? r.price;
                if (sale == null) return <Typography.Text type="secondary">—</Typography.Text>;
                const base = r.original_price ?? null;
                const off = base != null && base > sale ? Math.round(((base - sale) / base) * 100) : 0;
                return (
                    <Space size={4}>
                        <MoneyText value={sale} currency={r.currency} strong />
                        {off > 0 && (
                            <Tooltip title={`Giảm ${off}% so với giá gốc`}>
                                <QuestionCircleOutlined style={{ color: '#bfbfbf', cursor: 'help' }} />
                            </Tooltip>
                        )}
                    </Space>
                );
            },
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
            width: 240,
            render: (_, r) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/marketplace/on-channel/${r.id}/edit`, { state: { listing: r } })}>
                        Sửa trên sàn
                    </Button>
                    <Button size="small" icon={<CopyOutlined />} onClick={() => openClone(r)}>
                        Sao chép sàn
                    </Button>
                </Space>
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

            <Modal
                title="Sao chép sang gian hàng khác"
                open={cloneFor !== null}
                onCancel={() => setCloneFor(null)}
                okText={cloneShopIds.length > 1 ? `Sao chép ${cloneShopIds.length} sàn` : 'Sao chép'}
                okButtonProps={{ disabled: cloneShopIds.length === 0, loading: clone.isPending }}
                onOk={handleClone}
            >
                <Typography.Paragraph type="secondary" style={{ marginBottom: 8 }}>
                    Cùng nền tảng sẽ đủ dữ liệu để đẩy luôn (sẵn sàng); khác nền tảng tạo bản nháp cần soạn lại
                    ngành hàng/thuộc tính. Tất cả đưa vào “Chờ đẩy lên sàn”.
                </Typography.Paragraph>
                {cloneTargets.length === 0 ? (
                    <Empty description="Không có gian hàng đích nào khác." />
                ) : (
                    <Checkbox.Group value={cloneShopIds} onChange={(v) => setCloneShopIds(v as number[])}>
                        <Space direction="vertical">
                            {cloneTargets.map((a) => (
                                <Checkbox key={a.id} value={a.id}>
                                    <Space size={6}>
                                        <ChannelLogo provider={a.provider} size={16} />
                                        <span>{a.name}</span>
                                        <Tag>{a.provider}</Tag>
                                    </Space>
                                </Checkbox>
                            ))}
                        </Space>
                    </Checkbox.Group>
                )}
            </Modal>
        </div>
    );
}
