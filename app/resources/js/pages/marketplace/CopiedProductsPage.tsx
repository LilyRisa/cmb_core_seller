import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Alert, App as AntApp, Button, Checkbox, Empty, Image, Modal, Popconfirm, Result, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ChromeOutlined, DeleteOutlined, PlusOutlined, ReloadOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { CHROME_EXTENSION_NAME, CHROME_EXTENSION_URL } from '@/lib/extension';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { useCreateListing, useDeleteMasterProduct, useMasterProducts } from '@/features/products/hooks';
import type { ListingDraftSummary, MasterProduct } from '@/features/products/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    reviewing: { color: 'gold', label: 'Đang duyệt' },
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
    const navigate = useNavigate();
    const createListing = useCreateListing();
    const deleteProduct = useDeleteMasterProduct();

    const [createForProduct, setCreateForProduct] = useState<MasterProduct | null>(null);
    const [targetShopIds, setTargetShopIds] = useState<number[]>([]);
    // Nhắc cài tiện ích — ẩn được (lưu localStorage) để người đã cài không bị làm phiền.
    const [extNoticeDismissed, setExtNoticeDismissed] = useState(() => localStorage.getItem('copy_ext_notice_dismissed') === '1');

    const accounts = channelData?.data ?? [];

    const openCreateModal = (product: MasterProduct) => {
        setCreateForProduct(product);
        setTargetShopIds(accounts[0] ? [accounts[0].id] : []);
    };

    // Tạo một bản nháp cho MỖI gian hàng đã chọn (import vào nhiều shop).
    const handleCreate = async () => {
        const product = createForProduct;
        if (!product || targetShopIds.length === 0) return;
        const shops = accounts.filter((a) => targetShopIds.includes(a.id));

        const createdIds: number[] = [];
        let failed = 0;
        await Promise.all(
            shops.map(async (shop) => {
                try {
                    const draft = await createListing.mutateAsync({ productId: product.id, channelAccountId: shop.id, provider: shop.provider });
                    createdIds.push(draft.id);
                } catch (e) {
                    failed += 1;
                    message.error(`${shop.name}: ${errorMessage(e)}`);
                }
            }),
        );

        setCreateForProduct(null);
        if (createdIds.length === 1 && failed === 0) {
            navigate(`/marketplace/listings/${createdIds[0]}/edit`);
        } else if (createdIds.length > 0) {
            message.success(`Đã tạo ${createdIds.length} bản nháp${failed ? `, ${failed} lỗi` : ''}. Soạn nốt ở mục "Chờ đẩy lên sàn".`);
            navigate('/marketplace/to-push');
        }
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
        { title: 'Tên sản phẩm', dataIndex: 'name', width: 320, ellipsis: { showTitle: true } },
        {
            title: 'Thương hiệu',
            dataIndex: 'brand',
            width: 140,
            ellipsis: { showTitle: true },
            render: (v: string | null) => v ?? <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Ngành hàng',
            dataIndex: 'category',
            width: 160,
            ellipsis: { showTitle: true },
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
            width: 230,
            render: (_, r) => (
                <Space>
                    <Button size="small" icon={<PlusOutlined />} onClick={() => openCreateModal(r)}>
                        Tạo nháp sàn
                    </Button>
                    <Popconfirm
                        title="Xóa sản phẩm copy?"
                        description="Gỡ sản phẩm này (và bản nháp sàn của nó) khỏi hệ thống. Không ảnh hưởng listing đã đăng trên sàn."
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                        onConfirm={() =>
                            deleteProduct.mutate(r.id, {
                                onSuccess: () => message.success('Đã xóa sản phẩm.'),
                                onError: (e) => message.error(errorMessage(e)),
                            })
                        }
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
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

            {!extNoticeDismissed && (
                <Alert
                    type="info"
                    showIcon
                    icon={<ChromeOutlined />}
                    closable
                    onClose={() => {
                        localStorage.setItem('copy_ext_notice_dismissed', '1');
                        setExtNoticeDismissed(true);
                    }}
                    style={{ marginBottom: 16 }}
                    message="Cần cài tiện ích Chrome để sao chép sản phẩm"
                    description={
                        <>
                            Để sao chép sản phẩm từ các sàn về đây, bạn cần cài tiện ích <b>{CHROME_EXTENSION_NAME}</b> cho
                            trình duyệt Chrome.
                        </>
                    }
                    action={
                        <Button type="primary" size="small" icon={<ChromeOutlined />} href={CHROME_EXTENSION_URL} target="_blank">
                            Cài đặt tiện ích
                        </Button>
                    }
                />
            )}

            <Table
                rowKey="id"
                loading={isLoading}
                dataSource={products ?? []}
                columns={columns}
                scroll={{ x: 'max-content' }}
                locale={{ emptyText: <Empty description="Chưa có sản phẩm gốc nào." /> }}
                pagination={{ pageSize: 20, showSizeChanger: false }}
            />

            <Modal
                title="Tạo bản nháp đăng sàn"
                open={createForProduct !== null}
                onCancel={() => setCreateForProduct(null)}
                okText={targetShopIds.length > 1 ? `Tạo ${targetShopIds.length} nháp` : 'Tạo & soạn nháp'}
                okButtonProps={{ disabled: targetShopIds.length === 0, loading: createListing.isPending }}
                onOk={handleCreate}
            >
                <Typography.Paragraph>
                    Sản phẩm: <b>{createForProduct?.name}</b>
                </Typography.Paragraph>
                <Typography.Text type="secondary">Chọn gian hàng đích (có thể chọn nhiều)</Typography.Text>
                <div style={{ marginTop: 8 }}>
                    {accounts.length === 0 ? (
                        <Empty description="Chưa kết nối gian hàng nào. Vào Gian hàng để kết nối." />
                    ) : (
                        <Checkbox.Group value={targetShopIds} onChange={(v) => setTargetShopIds(v as number[])}>
                            <Space direction="vertical">
                                {accounts.map((a) => (
                                    <Checkbox key={a.id} value={a.id}>
                                        {a.name} <Tag>{a.provider}</Tag>
                                    </Checkbox>
                                ))}
                            </Space>
                        </Checkbox.Group>
                    )}
                </div>
            </Modal>
        </div>
    );
}
