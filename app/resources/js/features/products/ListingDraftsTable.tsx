import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Empty, Image, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CloudUploadOutlined, CopyOutlined, EditOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { CloneListingModal } from './CloneListingModal';
import { PushProgressModal } from './PushProgressModal';
import { useBulkPush, useMasterProducts, usePushListing } from './hooks';
import type { ListingDraftSummary, MasterProduct } from './api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    reviewing: { color: 'gold', label: 'Đang duyệt' },
    live: { color: 'success', label: 'Đã đăng' },
    published: { color: 'success', label: 'Đã đăng' },
    failed: { color: 'red', label: 'Lỗi' },
};

/** Một dòng = một listing nháp gắn với sản phẩm gốc của nó. */
interface ListingRow extends ListingDraftSummary {
    productId: number;
    productName: string;
    productImage: string | null;
}

/**
 * Bảng listing dùng chung cho 2 màn: "Đã có trên sàn" (live/pushing) và
 * "Chờ đẩy lên sàn" (ready/draft/failed). Dữ liệu suy ra từ {@link useMasterProducts}
 * bằng cách trải phẳng `product.listings[]` rồi lọc theo `statuses`.
 *
 * Mọi modal (editor, clone, tiến trình đẩy) host tại đây để 2 màn dùng lại nguyên vẹn.
 */
export function ListingDraftsTable({
    statuses,
    showPush = false,
    emptyText,
}: {
    statuses: string[];
    /** Hiện nút "Đẩy" mỗi dòng (ready) + "Đẩy hàng loạt" — chỉ màn "Chờ đẩy". */
    showPush?: boolean;
    emptyText: string;
}) {
    const { message } = AntApp.useApp();
    const { data: products, isLoading, refetch } = useMasterProducts();
    const { data: channelData } = useChannelAccounts();
    const pushListing = usePushListing();
    const bulkPush = useBulkPush();

    const navigate = useNavigate();
    const [selectedRowKeys, setSelectedRowKeys] = useState<number[]>([]);
    const [cloneFor, setCloneFor] = useState<ListingRow | null>(null);
    const [batchId, setBatchId] = useState<number | null>(null);
    const [pushModalOpen, setPushModalOpen] = useState(false);

    const accounts = channelData?.data ?? [];
    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;

    const rows = useMemo<ListingRow[]>(() => {
        const out: ListingRow[] = [];
        for (const p of (products ?? []) as MasterProduct[]) {
            for (const l of p.listings ?? []) {
                if (statuses.includes(l.status)) {
                    out.push({ ...l, productId: p.id, productName: p.name, productImage: p.image });
                }
            }
        }
        return out;
    }, [products, statuses]);

    const readyIds = useMemo(
        () => rows.filter((r) => selectedRowKeys.includes(r.id) && r.status === 'ready').map((r) => r.id),
        [rows, selectedRowKeys],
    );

    const openEditor = (listingId: number) => navigate(`/marketplace/listings/${listingId}/edit`);

    const handlePush = (id: number) => {
        pushListing.mutate(id, {
            onSuccess: ({ batch_id }) => {
                setBatchId(batch_id);
                setPushModalOpen(true);
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const handleBulkPush = () => {
        if (readyIds.length === 0) return;
        bulkPush.mutate(readyIds, {
            onSuccess: ({ batch_id }) => {
                setBatchId(batch_id);
                setPushModalOpen(true);
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<ListingRow> = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, r) => (
                <Space>
                    {r.productImage ? (
                        <Image src={r.productImage} width={40} height={40} style={{ objectFit: 'cover', borderRadius: 6 }} />
                    ) : (
                        <div style={{ width: 40, height: 40, background: '#F1F5F9', borderRadius: 6 }} />
                    )}
                    <Typography.Text>{r.productName}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Gian hàng',
            key: 'shop',
            render: (_, r) => (
                <Space size={4}>
                    <span>{shopName(r.channel_account_id)}</span>
                    <Tag>{r.provider}</Tag>
                </Space>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 120,
            render: (s: string) => {
                const meta = STATUS_TAG[s] ?? STATUS_TAG.draft;
                return <Tag color={meta.color}>{meta.label}</Tag>;
            },
        },
        {
            title: '',
            key: 'actions',
            width: showPush ? 280 : 210,
            render: (_, r) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEditor(r.id)}>
                        Sửa
                    </Button>
                    {showPush && (
                        <Button
                            size="small"
                            type="primary"
                            icon={<CloudUploadOutlined />}
                            disabled={r.status !== 'ready'}
                            loading={pushListing.isPending}
                            onClick={() => handlePush(r.id)}
                        >
                            Đẩy
                        </Button>
                    )}
                    <Button size="small" icon={<CopyOutlined />} onClick={() => setCloneFor(r)}>
                        Sao chép
                    </Button>
                </Space>
            ),
        },
    ];

    return (
        <div>
            {showPush && (
                <Space style={{ marginBottom: 12 }}>
                    <Button
                        type="primary"
                        icon={<CloudUploadOutlined />}
                        disabled={readyIds.length === 0}
                        loading={bulkPush.isPending}
                        onClick={handleBulkPush}
                    >
                        Đẩy hàng loạt{readyIds.length ? ` (${readyIds.length})` : ''}
                    </Button>
                    <Typography.Text type="secondary">Chọn các listing “Sẵn sàng” để đẩy cùng lúc.</Typography.Text>
                </Space>
            )}

            <Table<ListingRow>
                rowKey="id"
                loading={isLoading}
                dataSource={rows}
                columns={columns}
                locale={{ emptyText: <Empty description={emptyText} /> }}
                rowSelection={
                    showPush
                        ? {
                              selectedRowKeys,
                              onChange: (keys) => setSelectedRowKeys(keys as number[]),
                              getCheckboxProps: (r) => ({ disabled: r.status !== 'ready' }),
                          }
                        : undefined
                }
                pagination={{ pageSize: 20, showSizeChanger: false }}
            />

            <CloneListingModal
                sourceListingId={cloneFor?.id ?? null}
                sourceProvider={cloneFor?.provider ?? null}
                sourceChannelAccountId={cloneFor?.channel_account_id ?? null}
                open={cloneFor !== null}
                onClose={() => setCloneFor(null)}
                onCloned={(draft, crossProvider) => {
                    refetch();
                    if (crossProvider) openEditor(draft.id);
                }}
            />

            <PushProgressModal
                batchId={batchId}
                open={pushModalOpen}
                onClose={() => {
                    setPushModalOpen(false);
                    setSelectedRowKeys([]);
                    refetch();
                }}
            />
        </div>
    );
}
