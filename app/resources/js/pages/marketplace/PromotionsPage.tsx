import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Card, DatePicker, Empty, Input, Modal, Popconfirm, Radio, Select, Space, Table, Tabs, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CloudDownloadOutlined, DeleteOutlined, EditOutlined, PlusOutlined, StopOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { ChannelLogo } from '@/components/ChannelLogo';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import {
    useCreatePromotion, useDeletePromotion, useEndPromotion, usePromotionCapabilities, usePromotions, useSyncPromotions,
} from '@/features/promotions/hooks';
import type { DiscountType, Promotion } from '@/features/promotions/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    live: { color: 'success', label: 'Đang chạy' },
    ended: { color: 'default', label: 'Đã kết thúc' },
    failed: { color: 'red', label: 'Lỗi' },
};

/** Số ngày còn lại tới hạn (null nếu không có ends_at). */
function daysLeft(endsAt: string | null): number | null {
    if (!endsAt) return null;
    return dayjs(endsAt).startOf('day').diff(dayjs().startOf('day'), 'day');
}

export function PromotionsPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const { data: channelData } = useChannelAccounts();
    const accounts = useMemo(() => channelData?.data ?? [], [channelData]);

    const [shopId, setShopId] = useState<number | null>(null);
    const [tab, setTab] = useState<'pushed' | 'draft'>('pushed');
    const [createOpen, setCreateOpen] = useState(false);

    const activeShop = shopId ?? accounts[0]?.id ?? null;
    const { data: promotions, isFetching } = usePromotions(activeShop, tab);
    const sync = useSyncPromotions();
    const endPromo = useEndPromotion();
    const deletePromo = useDeletePromotion();

    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;
    const shopProvider = (id: number) => accounts.find((a) => a.id === id)?.provider ?? '';

    const handleSync = () => {
        if (activeShop == null) return;
        sync.mutate(activeShop, {
            onSuccess: (n) => message.success(`Đã đồng bộ ${n} chiến dịch từ sàn.`),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<Promotion> = [
        {
            title: 'Chiến dịch',
            key: 'title',
            render: (_, r) => (
                <Space direction="vertical" size={0}>
                    <Typography.Text strong>{r.title}</Typography.Text>
                    <Space size={4}>
                        <ChannelLogo provider={r.provider} size={14} />
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>{shopName(r.channel_account_id)}</Typography.Text>
                        {r.source === 'sync' && <Tag>Từ sàn</Tag>}
                    </Space>
                </Space>
            ),
        },
        {
            title: 'Giảm giá', dataIndex: 'discount_type', width: 110,
            render: (v: DiscountType) => <Tag>{v === 'percent' ? 'Theo %' : 'Giá cố định'}</Tag>,
        },
        { title: 'SKU', dataIndex: 'sku_count', width: 70, align: 'right', render: (v?: number) => v ?? '—' },
        {
            title: 'Thời gian', key: 'time', width: 230,
            render: (_, r) => (
                <Typography.Text style={{ fontSize: 12 }}>
                    {r.starts_at ? dayjs(r.starts_at).format('DD/MM/YY HH:mm') : '—'} → {r.ends_at ? dayjs(r.ends_at).format('DD/MM/YY HH:mm') : '—'}
                </Typography.Text>
            ),
        },
        {
            title: 'Trạng thái', key: 'status', width: 160,
            render: (_, r) => {
                const meta = STATUS_TAG[r.status] ?? STATUS_TAG.draft;
                const dl = daysLeft(r.ends_at);
                const expiring = r.status === 'live' && dl !== null && dl >= 0 && dl <= 5;
                return (
                    <Space size={4} wrap>
                        <Tag color={meta.color}>{meta.label}</Tag>
                        {expiring && <Tag color="warning">Sắp hết hạn ({dl}n)</Tag>}
                    </Space>
                );
            },
        },
        {
            title: '', key: 'actions', width: 200,
            render: (_, r) => (
                <Space>
                    {(r.status === 'draft' || r.status === 'failed') && (
                        <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/marketplace/promotions/${r.id}/edit`)}>Sửa</Button>
                    )}
                    {(r.status === 'live' || r.status === 'pushing') && (
                        <Popconfirm title="Kết thúc chiến dịch trên sàn?" okText="Kết thúc" cancelText="Hủy" okButtonProps={{ danger: true }}
                            onConfirm={() => endPromo.mutate(r.id, { onSuccess: () => message.success('Đã kết thúc.'), onError: (e) => message.error(errorMessage(e)) })}>
                            <Button size="small" danger icon={<StopOutlined />}>Kết thúc</Button>
                        </Popconfirm>
                    )}
                    {(r.status === 'draft' || r.status === 'failed' || r.status === 'ended') && (
                        <Popconfirm title="Xóa chiến dịch?" okText="Xóa" cancelText="Hủy" okButtonProps={{ danger: true }}
                            onConfirm={() => deletePromo.mutate(r.id, { onSuccess: () => message.success('Đã xóa.'), onError: (e) => message.error(errorMessage(e)) })}>
                            <Button size="small" danger icon={<DeleteOutlined />} />
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Chiến dịch giảm giá"
                subtitle="Tạo chương trình giảm giá nhiều SKU rồi đẩy lên sàn. Tab “Đã đẩy” đồng bộ chiến dịch đang có trên sàn."
                extra={
                    <Space>
                        {tab === 'pushed' && (
                            <Button icon={<CloudDownloadOutlined />} loading={sync.isPending} onClick={handleSync} disabled={activeShop == null}>Đồng bộ từ sàn</Button>
                        )}
                        <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateOpen(true)} disabled={accounts.length === 0}>Tạo chiến dịch</Button>
                    </Space>
                }
            />

            <Card styles={{ body: { padding: 16 } }}>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Select
                        style={{ minWidth: 260 }} placeholder="Chọn gian hàng" value={activeShop ?? undefined}
                        onChange={(v) => setShopId(v)} optionFilterProp="title"
                        options={accounts.map((a) => ({ value: a.id, title: a.name, label: <Space size={6}><ChannelLogo provider={a.provider} size={16} /><span>{a.name}</span></Space> }))}
                    />
                </Space>

                <Tabs
                    activeKey={tab}
                    onChange={(k) => setTab(k as 'pushed' | 'draft')}
                    items={[
                        { key: 'pushed', label: 'Đã đẩy lên sàn' },
                        { key: 'draft', label: 'Nháp chờ đẩy' },
                    ]}
                />

                <Table<Promotion>
                    rowKey="id"
                    size="middle"
                    loading={isFetching}
                    dataSource={promotions ?? []}
                    columns={columns}
                    locale={{ emptyText: <Empty description={tab === 'draft' ? 'Chưa có chiến dịch nháp.' : 'Chưa có chiến dịch trên sàn. Bấm “Đồng bộ từ sàn” hoặc tạo mới.'} /> }}
                    pagination={{ pageSize: 20, showSizeChanger: false }}
                />
            </Card>

            <CreatePromotionModal
                open={createOpen}
                accounts={accounts}
                defaultShopId={activeShop}
                shopProvider={shopProvider}
                onClose={() => setCreateOpen(false)}
            />
        </div>
    );
}

/** Modal tạo nháp: chọn shop + tiêu đề + kiểu giảm + thời gian (render khớp năng lực sàn). */
function CreatePromotionModal({
    open, accounts, defaultShopId, shopProvider, onClose,
}: {
    open: boolean;
    accounts: { id: number; name: string; provider: string }[];
    defaultShopId: number | null;
    shopProvider: (id: number) => string;
    onClose: () => void;
}) {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const create = useCreatePromotion();

    const [shopId, setShopId] = useState<number | null>(defaultShopId);
    const [title, setTitle] = useState('');
    const [discountType, setDiscountType] = useState<DiscountType>('fixed');
    const [range, setRange] = useState<[Dayjs, Dayjs] | null>(null);

    const provider = shopId != null ? shopProvider(shopId) : null;
    const { data: caps } = usePromotionCapabilities(provider);
    // % chọn được cho MỌI sàn — sàn không hỗ trợ % gốc thì hệ thống tự quy đổi sang giá sau giảm.
    const nativePercent = caps?.supports_percent ?? false;
    const withTime = caps?.supports_time_of_day ?? true;

    const submit = () => {
        if (shopId == null || !title.trim() || !range) {
            message.warning('Nhập đủ gian hàng, tiêu đề và thời gian.');
            return;
        }
        create.mutate(
            {
                channel_account_id: shopId,
                title: title.trim(),
                discount_type: discountType,
                starts_at: range[0].toISOString(),
                ends_at: range[1].toISOString(),
            },
            {
                onSuccess: (p) => { onClose(); navigate(`/marketplace/promotions/${p.id}/edit`); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    return (
        <Modal title="Tạo chiến dịch giảm giá" open={open} onCancel={onClose} okText="Tạo & chọn SKU" onOk={submit} okButtonProps={{ loading: create.isPending }} destroyOnClose>
            <Space direction="vertical" style={{ width: '100%' }} size={12}>
                <div>
                    <Typography.Text type="secondary">Gian hàng</Typography.Text>
                    <Select
                        style={{ width: '100%', marginTop: 4 }} placeholder="Chọn gian hàng" value={shopId ?? undefined} onChange={setShopId}
                        options={accounts.map((a) => ({ value: a.id, label: <Space size={6}><ChannelLogo provider={a.provider} size={16} /><span>{a.name}</span></Space> }))}
                    />
                </div>
                <div>
                    <Typography.Text type="secondary">Tên chiến dịch</Typography.Text>
                    <Input style={{ marginTop: 4 }} value={title} onChange={(e) => setTitle(e.target.value)} maxLength={255} placeholder="VD: Sale 6.6" />
                </div>
                <div>
                    <Typography.Text type="secondary">Kiểu giảm giá</Typography.Text>
                    <div style={{ marginTop: 4 }}>
                        <Radio.Group value={discountType} onChange={(e) => setDiscountType(e.target.value)}>
                            <Radio value="fixed">Giá cố định</Radio>
                            <Radio value="percent">Theo %</Radio>
                        </Radio.Group>
                    </div>
                    {discountType === 'percent' && !nativePercent && (
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>Sàn này dùng giá sau giảm — hệ thống tự quy đổi từ %.</Typography.Text>
                    )}
                </div>
                <div>
                    <Typography.Text type="secondary">Thời gian{!withTime && ' (sàn này chỉ theo ngày)'}</Typography.Text>
                    <div style={{ marginTop: 4 }}>
                        <DatePicker.RangePicker
                            style={{ width: '100%' }}
                            showTime={withTime ? { format: 'HH:mm' } : false}
                            format={withTime ? 'DD/MM/YYYY HH:mm' : 'DD/MM/YYYY'}
                            value={range ?? undefined}
                            onChange={(v) => setRange(v as [Dayjs, Dayjs] | null)}
                        />
                    </div>
                </div>
            </Space>
        </Modal>
    );
}
