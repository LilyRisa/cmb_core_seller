import { useEffect, useState } from 'react';
import { App as AntApp, Alert, Avatar, Empty, Modal, Skeleton, Space, Table, Tag, Typography } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { errorMessage } from '@/lib/api';
import { SkuPickerField } from '@/components/SkuPicker';
import { useLinkOrderSkus, useUnmappedSkus, type UnmappedSkuGroup } from '@/lib/orders';

/**
 * Quick "link SKU" modal (SPEC 0004 §3.3): merges identical channel SKUs across the
 * given orders into one row each, lets you pick a master SKU per row (suggested when
 * the code matches), then links + re-resolves all affected orders. `orderIds` empty
 * ⇒ every order still missing a SKU mapping.
 */
export function LinkSkusModal({ open, orderIds, onClose }: { open: boolean; orderIds?: number[]; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const groupsQ = useUnmappedSkus(orderIds, open);
    const link = useLinkOrderSkus();
    const [picked, setPicked] = useState<Record<string, number | undefined>>({});  // keyed by `${cid}|${extSku}`

    const groups = groupsQ.data ?? [];
    const keyOf = (g: UnmappedSkuGroup) => `${g.channel_account_id}|${g.external_sku_id ?? g.seller_sku ?? ''}`;

    useEffect(() => {
        if (open && groups.length) {
            setPicked((prev) => {
                const next = { ...prev };
                for (const g of groups) { const k = keyOf(g); if (next[k] === undefined && g.suggested_sku_id) next[k] = g.suggested_sku_id; }
                return next;
            });
        }
        if (!open) setPicked({});
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, groupsQ.data]);

    const submit = () => {
        const links = groups
            .filter((g) => picked[keyOf(g)])
            .map((g) => ({ channel_account_id: g.channel_account_id, external_sku_id: g.external_sku_id ?? undefined, seller_sku: g.seller_sku ?? undefined, sku_id: picked[keyOf(g)]! }));
        if (links.length === 0) { message.warning('Hãy chọn master SKU cho ít nhất một dòng.'); return; }
        link.mutate(links, {
            onSuccess: (r) => { message.success(`Đã liên kết ${r.linked} SKU sàn · ${r.orders_resolved} đơn được xử lý lại.`); onClose(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<UnmappedSkuGroup> = [
        { title: 'SKU sàn', key: 'sku', render: (_, g) => (
            <Space size={10} align="center" style={{ minWidth: 0 }}>
                <Avatar shape="square" size={36} src={g.sample_image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf', flex: 'none' }} />
                <Space direction="vertical" size={0} style={{ minWidth: 0, maxWidth: 260 }}>
                    <Typography.Text strong ellipsis={{ tooltip: g.sample_name }}>{g.sample_name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }} ellipsis={{ tooltip: `SKU sàn: ${g.seller_sku ?? g.external_sku_id ?? '—'} · ${g.channel_account_name}` }}>SKU sàn: {g.seller_sku ?? g.external_sku_id ?? '—'} · {g.channel_account_name}</Typography.Text>
                </Space>
            </Space>
        ) },
        { title: 'Đơn', key: 'orders', width: 80, align: 'center', render: (_, g) => <Tag>{g.order_count} đơn</Tag> },
        { title: 'Liên kết master SKU', key: 'pick', width: 340, render: (_, g) => (
            <SkuPickerField value={picked[keyOf(g)]} onChange={(v) => setPicked((p) => ({ ...p, [keyOf(g)]: v ?? undefined }))} />
        ) },
    ];

    return (
        <Modal
            title="Liên kết SKU nhanh" open={open} onCancel={onClose} okText="Liên kết & xử lý lại" width={760}
            confirmLoading={link.isPending} onOk={submit} okButtonProps={{ disabled: groups.length === 0 }}
        >
            <Typography.Paragraph type="secondary">Các đơn dùng cùng một SKU sàn đã được gộp — chỉ cần chọn master SKU một lần. Hệ thống sẽ tạo liên kết, tự áp lại tồn và tải lại danh sách.</Typography.Paragraph>
            {groupsQ.isLoading ? <Skeleton active /> : groups.length === 0 ? <Empty description="Không có SKU sàn nào cần liên kết." /> : (
                <>
                    <Table<UnmappedSkuGroup> rowKey={keyOf} size="small" pagination={false} dataSource={groups} columns={columns} />
                    {groups.some((g) => !g.suggested_sku_id) && <Alert type="info" showIcon style={{ marginTop: 12 }} message="Dòng chưa có gợi ý: chọn tay master SKU, hoặc tạo SKU mới ở trang Tồn kho rồi quay lại." />}
                </>
            )}
        </Modal>
    );
}
