import { useState } from 'react';
import { App as AntApp, Button, Card, Drawer, Empty, Popconfirm, Space, Spin, Table, Tag, Tooltip, Typography } from 'antd';
import { DeleteOutlined, EyeOutlined, ReloadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type KnowledgeDoc, useDeleteKnowledge, useKnowledgeChunks, useKnowledgeDocs, useReindexKnowledge } from '@/lib/messagingConfig';
import { PageScopeTags } from '@/components/messaging/PageScope';

const STATUS: Record<KnowledgeDoc['status'], { color: string; label: string }> = {
    pending: { color: 'processing', label: 'Đang xử lý' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    failed: { color: 'red', label: 'Lỗi' },
};

/** Panel "Tài liệu (chữ)" trong trang AI training (RAG). SPEC-0024 §6.2.
 * `provider` lọc picker theo nền tảng (vd 'facebook_page', 'zalo_oa'). */
export function KnowledgeDocsPanel({ provider }: { provider?: string }) {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.ai.train');
    const { data, isFetching } = useKnowledgeDocs(provider);
    const del = useDeleteKnowledge();
    const reindex = useReindexKnowledge();
    const [viewingId, setViewingId] = useState<number | null>(null);

    const columns: ColumnsType<KnowledgeDoc> = [
        { title: 'Tiêu đề', dataIndex: 'title' },
        { title: 'Nguồn', dataIndex: 'source', width: 110, render: (v) => <Tag>{v}</Tag> },
        { title: 'Phạm vi trang', width: 200, render: (_, r) => (
            <PageScopeTags appliesAllPages={r.applies_all_pages} channelAccountIds={r.channel_account_ids} />
        ) },
        { title: 'Trạng thái', dataIndex: 'status', width: 140, render: (v: KnowledgeDoc['status'], r) => <Space direction="vertical" size={0}><Tag color={STATUS[v].color}>{STATUS[v].label}</Tag>{r.error && <span style={{ fontSize: 11, color: '#EF4444' }}>{r.error}</span>}</Space> },
        { title: 'Số đoạn', dataIndex: 'chunk_count', width: 90, align: 'center' },
        {
            title: '', width: 130, render: (_: unknown, r: KnowledgeDoc) => (
                <Space size={2}>
                    <Tooltip title="Xem nội dung đã lấy">
                        <Button size="small" type="text" icon={<EyeOutlined />} onClick={() => setViewingId(r.id)} />
                    </Tooltip>
                    {canManage && r.source !== 'inline' && (
                        <Tooltip title="Tải lại (lấy dữ liệu mới từ nguồn)">
                            <Button size="small" type="text" icon={<ReloadOutlined />} loading={reindex.isPending && reindex.variables === r.id}
                                onClick={() => reindex.mutate(r.id, { onSuccess: () => message.success('Đang tải lại tài liệu'), onError: (e) => message.error(errorMessage(e)) })} />
                        </Tooltip>
                    )}
                    {canManage && (
                        <Popconfirm title="Xoá tài liệu?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                            onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                            <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            <Card
                title={<Space>Tài liệu (chữ) <Tag>Cũ — chỉ xem</Tag></Space>}
            >
                <Typography.Paragraph type="secondary">
                    Tài liệu chữ đã ngừng tạo mới. Vào tab &quot;Kiến thức&quot; để thêm nội dung mới (văn bản + ảnh tùy chọn) — nội dung cũ ở đây vẫn được AI dùng bình thường.
                </Typography.Paragraph>
                <Table<KnowledgeDoc> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
            </Card>

            <KnowledgeContentDrawer id={viewingId} onClose={() => setViewingId(null)} />
        </div>
    );
}

/** Drawer xem nội dung đã trích (chunk) — kiểm tra dữ liệu AI thực sự lấy được. */
function KnowledgeContentDrawer({ id, onClose }: { id: number | null; onClose: () => void }) {
    const { data, isFetching } = useKnowledgeChunks(id);

    return (
        <Drawer open={id != null} onClose={onClose} width={560} title={data ? `Nội dung: ${data.title}` : 'Nội dung tài liệu'}>
            {isFetching && <div style={{ textAlign: 'center', padding: 32 }}><Spin /></div>}
            {!isFetching && data && (
                <Space direction="vertical" style={{ width: '100%' }} size="middle">
                    <Space wrap>
                        <Tag>{data.source}</Tag>
                        <Tag color={STATUS[data.status].color}>{STATUS[data.status].label}</Tag>
                        <Tag>{data.chunk_count} đoạn</Tag>
                    </Space>
                    {data.url && <Typography.Link href={data.url} target="_blank" rel="noreferrer">{data.url}</Typography.Link>}
                    {data.error && <Typography.Text type="danger">{data.error}</Typography.Text>}
                    {data.chunks.length === 0 && <Empty description="Chưa có nội dung được trích. Thử “Tải lại”." />}
                    {data.chunks.map((c) => (
                        <Card key={c.index} size="small" title={`Đoạn ${c.index + 1}`}>
                            <Typography.Paragraph style={{ whiteSpace: 'pre-wrap', marginBottom: 0 }}>{c.text}</Typography.Paragraph>
                        </Card>
                    ))}
                </Space>
            )}
        </Drawer>
    );
}
