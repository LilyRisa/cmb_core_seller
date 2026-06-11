import { useMemo, useState } from 'react';
import {
    App as AntApp,
    Button,
    Empty,
    Image,
    Modal,
    Radio,
    Result,
    Space,
    Table,
    Tag,
    Typography,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CloudUploadOutlined, PlusOutlined, ReloadOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { ListingEditorDrawer } from '@/features/products/ListingEditorDrawer';
import { PushProgressModal } from '@/features/products/PushProgressModal';
import { useBulkPush, useCreateListing, useMasterProducts } from '@/features/products/hooks';
import type { ListingDraftSummary, MasterProduct } from '@/features/products/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    published: { color: 'success', label: 'Đã đăng' },
    failed: { color: 'red', label: 'Lỗi' },
};

function ListingTags({ listings }: { listings?: ListingDraftSummary[] }) {
    if (!listings || listings.length === 0) return <Typography.Text type="secondary">—</Typography.Text>;
    return (
        <Space size={4} wrap>
            {listings.map((l) => {
                const meta = STATUS_TAG[l.status] ?? STATUS_TAG.draft;
                return (
                    <Tag key={l.id} color={meta.color}>
                        {l.provider}: {meta.label}
                    </Tag>
                );
            })}
        </Space>
    );
}

export function ProductsPublishPage() {
    const { message } = AntApp.useApp();
    const { data: products, isLoading, isError, error, refetch } = useMasterProducts();
    const { data: channelData } = useChannelAccounts();
    const createListing = useCreateListing();
    const bulkPush = useBulkPush();

    const [selectedRowKeys, setSelectedRowKeys] = useState<number[]>([]);
    const [editorListingId, setEditorListingId] = useState<number | null>(null);
    const [editorOpen, setEditorOpen] = useState(false);

    // Modal "Tạo nháp sàn"
    const [createForProduct, setCreateForProduct] = useState<MasterProduct | null>(null);
    const [targetShopId, setTargetShopId] = useState<number | null>(null);

    // Bulk push progress
    const [bulkBatchId, setBulkBatchId] = useState<number | null>(null);
    const [bulkModalOpen, setBulkModalOpen] = useState(false);

    const accounts = channelData?.data ?? [];

    // Tập hợp listing nháp đang ở trạng thái 'ready' để bật nút "Đẩy hàng loạt".
    const readyListingIds = useMemo(() => {
        const set = new Map<number, ListingDraftSummary>();
        for (const p of products ?? []) {
            if (selectedRowKeys.includes(p.id)) {
                for (const l of p.listings ?? []) {
                    if (l.status === 'ready') set.set(l.id, l);
                }
            }
        }
        return [...set.keys()];
    }, [products, selectedRowKeys]);

    const openCreateModal = (product: MasterProduct) => {
        setCreateForProduct(product);
        setTargetShopId(accounts[0]?.id ?? null);
    };

    const handleCreate = () => {
        const product = createForProduct;
        const shop = accounts.find((a) => a.id === targetShopId);
        if (!product || !shop) return;
        createListing.mutate(
            { productId: product.id, channelAccountId: shop.id, provider: shop.provider },
            {
                onSuccess: (draft) => {
                    setCreateForProduct(null);
                    setEditorListingId(draft.id);
                    setEditorOpen(true);
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const handleBulkPush = () => {
        if (readyListingIds.length === 0) return;
        bulkPush.mutate(readyListingIds, {
            onSuccess: ({ batch_id }) => {
                setBulkBatchId(batch_id);
                setBulkModalOpen(true);
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<MasterProduct> = [
        {
            title: 'Ảnh',
            dataIndex: 'image',
            width: 72,
            render: (src: string | null) =>
                src ? (
                    <Image src={src} width={48} height={48} style={{ objectFit: 'cover', borderRadius: 6 }} />
                ) : (
                    <div style={{ width: 48, height: 48, background: '#F1F5F9', borderRadius: 6 }} />
                ),
        },
        { title: 'Tên sản phẩm', dataIndex: 'name' },
        {
            title: 'Thương hiệu',
            dataIndex: 'brand',
            render: (v: string | null) => v ?? <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Ngành hàng',
            dataIndex: 'category',
            render: (v: string | null) => v ?? <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Sàn',
            key: 'listings',
            render: (_: unknown, r) => <ListingTags listings={r.listings} />,
        },
        {
            title: '',
            key: 'actions',
            width: 130,
            render: (_: unknown, r) => (
                <Button size="small" icon={<PlusOutlined />} onClick={() => openCreateModal(r)}>
                    Tạo nháp sàn
                </Button>
            ),
        },
    ];

    if (isError) {
        return (
            <Result
                status="error"
                title="Không tải được danh sách sản phẩm"
                subTitle={errorMessage(error)}
                extra={<Button onClick={() => refetch()}>Thử lại</Button>}
            />
        );
    }

    return (
        <div>
            <PageHeader
                title="Đăng sản phẩm lên sàn"
                subtitle="Tạo bản nháp listing từ sản phẩm gốc rồi đẩy lên các gian hàng đã kết nối"
                extra={
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isLoading}>
                        Làm mới
                    </Button>
                }
            />

            {/* Thanh công cụ luôn hiển thị (validate-by-disable, không ẩn) */}
            <Space style={{ marginBottom: 12 }}>
                <Button
                    type="primary"
                    icon={<CloudUploadOutlined />}
                    disabled={readyListingIds.length === 0}
                    loading={bulkPush.isPending}
                    onClick={handleBulkPush}
                >
                    Đẩy hàng loạt{readyListingIds.length ? ` (${readyListingIds.length})` : ''}
                </Button>
                <Typography.Text type="secondary">
                    Chọn sản phẩm có bản nháp “Sẵn sàng” để đẩy cùng lúc.
                </Typography.Text>
            </Space>

            <Table
                rowKey="id"
                loading={isLoading}
                dataSource={products ?? []}
                columns={columns}
                locale={{ emptyText: <Empty description="Chưa có sản phẩm gốc nào." /> }}
                rowSelection={{
                    selectedRowKeys,
                    onChange: (keys) => setSelectedRowKeys(keys as number[]),
                }}
                pagination={{ pageSize: 20, showSizeChanger: false }}
            />

            {/* Modal chọn gian hàng đích để tạo nháp */}
            <Modal
                title="Tạo bản nháp đăng sàn"
                open={createForProduct !== null}
                onCancel={() => setCreateForProduct(null)}
                okText="Tạo & soạn nháp"
                okButtonProps={{ disabled: targetShopId == null, loading: createListing.isPending }}
                onOk={handleCreate}
            >
                <Typography.Paragraph>
                    Sản phẩm: <b>{createForProduct?.name}</b>
                </Typography.Paragraph>
                <Typography.Text type="secondary">Chọn gian hàng đích</Typography.Text>
                <div style={{ marginTop: 8 }}>
                    {accounts.length === 0 ? (
                        <Empty description="Chưa kết nối gian hàng nào. Vào Gian hàng để kết nối." />
                    ) : (
                        <Radio.Group
                            value={targetShopId ?? undefined}
                            onChange={(e) => setTargetShopId(e.target.value)}
                        >
                            <Space direction="vertical">
                                {accounts.map((a) => (
                                    <Radio key={a.id} value={a.id}>
                                        {a.name} <Tag>{a.provider}</Tag>
                                    </Radio>
                                ))}
                            </Space>
                        </Radio.Group>
                    )}
                </div>
            </Modal>

            <ListingEditorDrawer
                listingId={editorListingId}
                open={editorOpen}
                onClose={() => {
                    setEditorOpen(false);
                    refetch();
                }}
            />

            <PushProgressModal
                batchId={bulkBatchId}
                open={bulkModalOpen}
                onClose={() => {
                    setBulkModalOpen(false);
                    setSelectedRowKeys([]);
                    refetch();
                }}
            />
        </div>
    );
}
