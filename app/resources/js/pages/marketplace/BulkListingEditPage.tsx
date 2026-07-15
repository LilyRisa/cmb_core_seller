import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Image, Input, Modal, Popover, Result, Select, Space, Spin, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ArrowLeftOutlined, CopyOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useBrands, useListingLimits, useListingsBulk } from '@/features/products/hooks';
import type { ListingDraftSku } from '@/features/products/api';
import { CategoryPicker } from '@/features/products/CategoryPicker';
import { RichTextEditor } from '@/components/RichTextEditor';

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

/** Ô chọn thương hiệu cho 1 dòng — tách riêng vì `useBrands` phụ thuộc `categoryId` từng dòng. */
function BrandCell({ row, onChange, onApplyAll }: { row: BulkEditRow; onChange: (brandId: string | null) => void; onApplyAll: (brandId: string | null) => void }) {
    const { data: brands, isFetching } = useBrands(row.provider, row.channelAccountId, row.categoryId);
    const options = (brands ?? []).map((b) => ({ value: b.id, label: b.mandatory ? `${b.name} (bắt buộc)` : b.name }));
    return (
        <Space>
            <Select
                style={{ width: 180 }}
                size="small"
                disabled={!row.categoryId}
                loading={isFetching}
                value={row.brandId ?? undefined}
                onChange={onChange}
                allowClear
                showSearch
                filterOption={(input, opt) => (opt?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                options={options}
                status={!row.brandId ? 'error' : undefined}
            />
            <Tooltip title="Áp dụng thương hiệu này cho mọi dòng đang chọn">
                <Button size="small" icon={<CopyOutlined />} disabled={!row.brandId} onClick={() => onApplyAll(row.brandId)} />
            </Tooltip>
        </Space>
    );
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

    const provider = rowsMeta?.[0]?.provider ?? '';
    const { data: limits } = useListingLimits(provider || null);
    const titleMax = limits?.title_max_length ?? 255;
    const richDescription = provider === 'tiktok' || provider === 'lazada';

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

    // Chưa dùng ở khung sườn này — Task 11 gọi `message` khi lưu. Giữ nguyên để tránh lỗi noUnusedLocals.
    void message;

    const [descRowId, setDescRowId] = useState<number | null>(null);
    const descRow = rows?.find((r) => r.id === descRowId) ?? null;

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
            title: 'Tiêu đề',
            key: 'title',
            width: 260,
            render: (_, r) => (
                <Input
                    value={r.name}
                    maxLength={titleMax}
                    showCount
                    status={r.name.length > titleMax ? 'error' : undefined}
                    onChange={(e) => updateRow(r.id, { name: e.target.value })}
                />
            ),
        },
        {
            title: 'Mô tả',
            key: 'description',
            width: 120,
            render: (_, r) => <Button size="small" onClick={() => setDescRowId(r.id)}>Sửa mô tả</Button>,
        },
        {
            title: 'Ngành hàng',
            key: 'category',
            width: 220,
            render: (_, r) => (
                <Space>
                    <Popover
                        trigger="click"
                        placement="bottomLeft"
                        content={<div style={{ width: 320 }}><CategoryPicker provider={r.provider} channelAccountId={r.channelAccountId} value={r.categoryId} onChange={(cid) => updateRow(r.id, { categoryId: cid, brandId: null })} /></div>}
                    >
                        <Button size="small" danger={!r.categoryId}>{r.categoryId ? 'Đã chọn' : 'Chưa chọn'}</Button>
                    </Popover>
                    <Tooltip title="Áp dụng ngành hàng này cho mọi dòng đang chọn">
                        <Button size="small" icon={<CopyOutlined />} disabled={!r.categoryId} onClick={() => applyToAllRows({ categoryId: r.categoryId })} />
                    </Tooltip>
                </Space>
            ),
        },
        {
            title: 'Thương hiệu',
            key: 'brand',
            width: 260,
            render: (_, r) => (
                <BrandCell
                    row={r}
                    onChange={(bid) => updateRow(r.id, { brandId: bid })}
                    onApplyAll={(bid) => applyToAllRows({ brandId: bid })}
                />
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
                <>
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
                    <Modal
                        title="Sửa mô tả"
                        open={descRowId !== null}
                        onCancel={() => setDescRowId(null)}
                        onOk={() => setDescRowId(null)}
                        width={720}
                    >
                        {descRow && (richDescription ? (
                            <RichTextEditor value={descRow.description} onChange={(html) => updateRow(descRow.id, { description: html })} />
                        ) : (
                            <Input.TextArea rows={6} value={descRow.description} onChange={(e) => updateRow(descRow.id, { description: e.target.value })} />
                        ))}
                    </Modal>
                </>
            )}
        </div>
    );
}
