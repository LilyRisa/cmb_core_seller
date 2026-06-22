import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Badge, Button, Empty, Image, Popconfirm, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CloudUploadOutlined, CopyOutlined, DeleteOutlined, EditOutlined, LoadingOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { CloneListingModal } from './CloneListingModal';
import { PushProgressModal } from './PushProgressModal';
import { useBulkPush, useDeleteListing, useMasterProducts, usePushBatch, usePushListing } from './hooks';
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
    history = false,
    emptyText,
}: {
    statuses: string[];
    /** Hiện nút "Đẩy" mỗi dòng (ready) + "Đẩy hàng loạt" — chỉ màn "Chờ đẩy". */
    showPush?: boolean;
    /** Chế độ lịch sử (đã đẩy): thêm cột thời gian đẩy, ẩn nút đẩy. */
    history?: boolean;
    emptyText: string;
}) {
    const { message } = AntApp.useApp();
    const { data: products, isLoading, refetch } = useMasterProducts();
    const { data: channelData } = useChannelAccounts();
    const pushListing = usePushListing();
    const bulkPush = useBulkPush();
    const deleteListing = useDeleteListing();

    const navigate = useNavigate();
    const [selectedRowKeys, setSelectedRowKeys] = useState<number[]>([]);
    const [cloneFor, setCloneFor] = useState<ListingRow | null>(null);
    const [batchId, setBatchId] = useState<number | null>(null);
    const [pushModalOpen, setPushModalOpen] = useState(false);

    // Poll ở cấp trang (không phụ thuộc modal mở/đóng) để chỉ báo thu nhỏ luôn cập
    // nhật khi người dùng đã ẩn cửa sổ — việc đẩy chạy nền ở worker.
    const { data: pushBatch } = usePushBatch(batchId);
    const pushDone = pushBatch?.status === 'done';
    const pushFinished = (pushBatch?.succeeded ?? 0) + (pushBatch?.failed ?? 0);

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

    const handleDelete = (id: number) => {
        deleteListing.mutate(id, {
            onSuccess: () => message.success('Đã xóa bản nháp.'),
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
        ...(history
            ? [{
                title: 'Đã đẩy lúc',
                key: 'pushed_at',
                width: 170,
                render: (_: unknown, r: ListingRow) => (r.pushed_at ? new Date(r.pushed_at).toLocaleString('vi-VN') : '—'),
            } as ColumnsType<ListingRow>[number]]
            : []),
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
                    <Popconfirm
                        title={history ? 'Xóa khỏi lịch sử?' : 'Xóa bản nháp này?'}
                        description={history ? 'Chỉ gỡ khỏi danh sách trong app, KHÔNG gỡ sản phẩm đã đăng trên sàn.' : 'Bản nháp sẽ bị xóa khỏi danh sách chờ đẩy.'}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                        onConfirm={() => handleDelete(r.id)}
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
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
                batch={pushBatch}
                open={pushModalOpen}
                onHide={() => {
                    // Ẩn nhưng giữ batchId → chỉ báo thu nhỏ vẫn hiện, đẩy vẫn chạy nền.
                    setPushModalOpen(false);
                    refetch();
                }}
                onClose={() => {
                    setPushModalOpen(false);
                    setBatchId(null);
                    setSelectedRowKeys([]);
                    refetch();
                }}
            />

            {/* Chỉ báo thu nhỏ: hiện khi đã ẩn cửa sổ mà tiến trình còn theo dõi được.
                Bấm để mở lại xem log trạng thái đẩy. */}
            {batchId != null && !pushModalOpen && (
                <Button
                    type={pushDone ? 'default' : 'primary'}
                    icon={pushDone ? <CloudUploadOutlined /> : <LoadingOutlined />}
                    style={{ position: 'fixed', right: 24, bottom: 24, zIndex: 1000, boxShadow: '0 4px 12px rgba(0,0,0,0.15)' }}
                    onClick={() => setPushModalOpen(true)}
                >
                    <Badge
                        status={pushDone ? (pushBatch?.failed ? 'error' : 'success') : 'processing'}
                        text={
                            pushDone
                                ? `Đẩy xong: ${pushBatch?.succeeded ?? 0}/${pushBatch?.total ?? 0}`
                                : `Đang đẩy ${pushFinished}/${pushBatch?.total ?? 0}…`
                        }
                    />
                </Button>
            )}
        </div>
    );
}
