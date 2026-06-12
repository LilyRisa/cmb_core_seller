import { useState } from 'react';
import { App as AntApp, Button, Empty, Image, Modal, Radio, Result, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { PlusOutlined, ReloadOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { ListingEditorDrawer } from '@/features/products/ListingEditorDrawer';
import { useCreateListing, useMasterProducts } from '@/features/products/hooks';
import type { ListingDraftSummary, MasterProduct } from '@/features/products/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    live: { color: 'success', label: 'Đã đăng' },
    published: { color: 'success', label: 'Đã đăng' },
    failed: { color: 'red', label: 'Lỗi' },
};

function ListingTags({ listings }: { listings?: ListingDraftSummary[] }) {
    if (!listings || listings.length === 0) return <Typography.Text type="secondary">Chưa đăng sàn nào</Typography.Text>;
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

/**
 * Trang 1 — "Sản phẩm copy": danh sách sản phẩm gốc (nguồn chủ yếu từ Chrome
 * extension copy sản phẩm). Từ đây tạo bản nháp đăng sàn cho một gian hàng rồi soạn.
 */
export function CopiedProductsPage() {
    const { message } = AntApp.useApp();
    const { data: products, isLoading, isError, error, refetch } = useMasterProducts();
    const { data: channelData } = useChannelAccounts();
    const createListing = useCreateListing();

    const [editorListingId, setEditorListingId] = useState<number | null>(null);
    const [editorOpen, setEditorOpen] = useState(false);
    const [createForProduct, setCreateForProduct] = useState<MasterProduct | null>(null);
    const [targetShopId, setTargetShopId] = useState<number | null>(null);

    const accounts = channelData?.data ?? [];

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
            title: 'Đăng sàn',
            key: 'listings',
            render: (_, r) => <ListingTags listings={r.listings} />,
        },
        {
            title: '',
            key: 'actions',
            width: 150,
            render: (_, r) => (
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
                title="Sản phẩm copy"
                subtitle="Sản phẩm gốc đã copy về (từ Chrome extension hoặc tạo tay) — tạo bản nháp để đăng lên gian hàng"
                extra={
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isLoading}>
                        Làm mới
                    </Button>
                }
            />

            <Table
                rowKey="id"
                loading={isLoading}
                dataSource={products ?? []}
                columns={columns}
                locale={{ emptyText: <Empty description="Chưa có sản phẩm gốc nào." /> }}
                pagination={{ pageSize: 20, showSizeChanger: false }}
            />

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
                        <Radio.Group value={targetShopId ?? undefined} onChange={(e) => setTargetShopId(e.target.value)}>
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
        </div>
    );
}
