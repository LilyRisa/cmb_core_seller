import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Image, Result, Space, Spin, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useListingsBulk } from '@/features/products/hooks';
import type { ListingDraftSku } from '@/features/products/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    failed: { color: 'red', label: 'Lỗi' },
};

/** Metadata hiển thị truyền từ ListingDraftsTable qua router state — tránh fetch lại. */
interface RowMeta {
    id: number;
    productName: string;
    productImage: string | null;
    shopName: string;
    provider: string;
}

/** Dòng đang sửa trong bảng — gộp metadata hiển thị + dữ liệu đầy đủ lấy về từ GET /listings/bulk. */
export interface BulkEditRow {
    id: number;
    productName: string;
    productImage: string | null;
    shopName: string;
    provider: string;
    channelAccountId: number;
    name: string;
    description: string;
    categoryId: string | null;
    brandId: string | null;
    attributes: Record<string, unknown>;
    mediaRefs: string[];
    logistics: Record<string, unknown>;
    skus: ListingDraftSku[];
    status: string;
    validationErrors: Record<string, string>;
}

/**
 * Trang bảng sửa nhiều bản nháp CÙNG NỀN TẢNG cùng lúc (SPEC 2026-07-15).
 * Điểm vào: `ListingDraftsTable` → nút "Chỉnh sửa hàng loạt", truyền `rows` qua
 * router state. Không có state (tải lại trang) ⇒ quay về danh sách.
 */
export function BulkListingEditPage() {
    const navigate = useNavigate();
    const location = useLocation();
    const { message } = AntApp.useApp();

    const rowsMeta = (location.state as { rows?: RowMeta[] } | null)?.rows ?? null;
    const ids = useMemo(() => (rowsMeta ?? []).map((r) => r.id), [rowsMeta]);

    const { data: fetched, isLoading, isError, error } = useListingsBulk(ids);
    const [rows, setRows] = useState<BulkEditRow[] | null>(null);

    useEffect(() => {
        if (!fetched || !rowsMeta) return;
        setRows(
            rowsMeta
                .map((meta): BulkEditRow | null => {
                    const d = fetched.find((x) => x.id === meta.id);
                    if (!d) return null;
                    return {
                        id: d.id,
                        productName: meta.productName,
                        productImage: meta.productImage,
                        shopName: meta.shopName,
                        provider: d.provider,
                        channelAccountId: d.channel_account_id,
                        name: d.name ?? '',
                        description: d.description ?? '',
                        categoryId: d.category_id,
                        brandId: d.brand_id,
                        attributes: d.attributes ?? {},
                        mediaRefs: d.media_refs ?? [],
                        logistics: d.logistics ?? {},
                        skus: d.skus,
                        status: d.status,
                        validationErrors: d.validation_errors ?? {},
                    };
                })
                .filter((r): r is BulkEditRow => r !== null),
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fetched]);

    const back = () => navigate('/marketplace/to-push');

    /** Ghi đè 1 field lên MỌI dòng đang có trong bảng — dùng cho nút "Áp dụng cho tất cả". */
    const applyToAllRows = (patch: Partial<Pick<BulkEditRow, 'categoryId' | 'brandId' | 'attributes' | 'logistics'>>) => {
        setRows((prev) => (prev ? prev.map((r) => ({ ...r, ...patch })) : prev));
    };

    const updateRow = (id: number, patch: Partial<BulkEditRow>) => {
        setRows((prev) => (prev ? prev.map((r) => (r.id === id ? { ...r, ...patch } : r)) : prev));
    };

    // Chưa dùng ở khung sườn này — Task 8-11 gọi `message`/`applyToAllRows`/`updateRow`
    // khi thêm ô sửa, nút "Áp dụng cho tất cả" và lưu. Giữ nguyên để tránh lỗi noUnusedLocals.
    void message;
    void applyToAllRows;
    void updateRow;

    const skuColumns: ColumnsType<ListingDraftSku> = [
        { title: 'SKU người bán', dataIndex: 'seller_sku', width: 160 },
        { title: 'Giá (VND)', dataIndex: 'price', width: 120 },
        { title: 'Tồn đẩy sàn', dataIndex: 'stock', width: 100 },
    ];

    const columns: ColumnsType<BulkEditRow> = [
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
                    <span>{r.shopName}</span>
                    <Tag>{r.provider}</Tag>
                </Space>
            ),
        },
        {
            title: 'Trạng thái',
            key: 'status',
            width: 120,
            render: (_, r) => {
                const meta = STATUS_TAG[r.status] ?? STATUS_TAG.draft;
                return <Tag color={meta.color}>{meta.label}</Tag>;
            },
        },
    ];

    if (!rowsMeta || rowsMeta.length === 0) {
        return (
            <Result
                status="warning"
                title="Chưa chọn nháp nào để sửa"
                subTitle="Vui lòng quay lại danh sách và chọn các nháp cần sửa hàng loạt."
                extra={<Button onClick={back}>Quay lại</Button>}
            />
        );
    }

    if (isError) {
        return (
            <Result status="error" title="Không tải được dữ liệu" subTitle={errorMessage(error)} extra={<Button onClick={back}>Quay lại</Button>} />
        );
    }

    return (
        <div>
            <PageHeader
                title={<Space><Button icon={<ArrowLeftOutlined />} onClick={back}>Quay lại</Button><span>Chỉnh sửa hàng loạt ({rowsMeta.length})</span></Space>}
                subtitle="Sửa nhiều bản nháp cùng 1 sàn cùng lúc — dùng nút “Áp dụng cho tất cả” để tránh nhập lại thông tin giống nhau."
            />
            {isLoading || !rows ? (
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            ) : (
                <Table<BulkEditRow>
                    rowKey="id"
                    dataSource={rows}
                    columns={columns}
                    pagination={false}
                    expandable={{
                        expandedRowRender: (r) => <Table<ListingDraftSku> rowKey="id" size="small" dataSource={r.skus} columns={skuColumns} pagination={false} />,
                        rowExpandable: (r) => r.skus.length > 0,
                    }}
                />
            )}
        </div>
    );
}
