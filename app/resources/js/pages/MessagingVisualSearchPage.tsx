import {
    DeleteOutlined,
    PictureOutlined,
    PlusOutlined,
    SearchOutlined,
    StarFilled,
    StarOutlined,
    UploadOutlined,
} from '@ant-design/icons';
import {
    App as AntApp,
    Button,
    Card,
    Drawer,
    Empty,
    Form,
    Input,
    Modal,
    Popconfirm,
    Space,
    Spin,
    Switch,
    Table,
    Tag,
    Tooltip,
    Typography,
    Upload,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { useState } from 'react';

import {
    useCreateVisualItem,
    useDeleteVisualImage,
    useDeleteVisualItem,
    useSetVisualPrimary,
    useUpdateVisualItem,
    useUploadVisualImages,
    useVisualImageBlob,
    useVisualItem,
    useVisualItems,
    useVisualLookup,
} from '@/features/visual-search/hooks';
import type { VisualImage, VisualItem, VisualMatch } from '@/features/visual-search/api';
import { useCan } from '@/lib/tenant';

function Thumb({ itemId, image, size = 72 }: { itemId: number; image: VisualImage; size?: number }) {
    const url = useVisualImageBlob(itemId, image.id);
    return (
        <div style={{ width: size, height: size, borderRadius: 8, overflow: 'hidden', background: '#F1F5F9', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            {url ? (
                <img src={url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            ) : (
                <Spin size="small" />
            )}
        </div>
    );
}

function ImagesDrawer({ itemId, onClose, canManage }: { itemId: number; onClose: () => void; canManage: boolean }) {
    const { message } = AntApp.useApp();
    const { data: item, isLoading } = useVisualItem(itemId);
    const upload = useUploadVisualImages();
    const del = useDeleteVisualImage();
    const setPrimary = useSetVisualPrimary();

    return (
        <Drawer open width={520} onClose={onClose} title={item ? `Ảnh (tùy chọn) — ${item.name}` : 'Ảnh (tùy chọn)'}>
            {canManage && (
                <Upload
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    showUploadList={false}
                    beforeUpload={(file, fileList) => {
                        // Gom nhiều file 1 lần upload (chỉ chạy ở file cuối).
                        if (file === fileList[fileList.length - 1]) {
                            upload.mutate(
                                { itemId, files: fileList as unknown as File[] },
                                { onSuccess: () => message.success('Đã tải ảnh lên'), onError: () => message.error('Tải ảnh thất bại') },
                            );
                        }
                        return false;
                    }}
                >
                    <Button icon={<UploadOutlined />} loading={upload.isPending}>Tải ảnh lên</Button>
                </Upload>
            )}
            <div style={{ marginTop: 16 }}>
                {isLoading ? (
                    <Spin />
                ) : !item?.images?.length ? (
                    <Empty description="Chưa có ảnh" />
                ) : (
                    <Space size={12} wrap>
                        {item.images.map((img) => (
                            <Card key={img.id} size="small" styles={{ body: { padding: 8 } }}>
                                <Thumb itemId={itemId} image={img} />
                                <Space style={{ marginTop: 6 }}>
                                    <Tooltip title={img.is_primary ? 'Ảnh đại diện' : 'Đặt làm đại diện'}>
                                        <Button
                                            size="small"
                                            type="text"
                                            disabled={!canManage || img.is_primary}
                                            icon={img.is_primary ? <StarFilled style={{ color: '#F59E0B' }} /> : <StarOutlined />}
                                            onClick={() => setPrimary.mutate({ itemId, imageId: img.id })}
                                        />
                                    </Tooltip>
                                    {canManage && (
                                        <Popconfirm title="Xoá ảnh này?" okButtonProps={{ danger: true }} onConfirm={() => del.mutate({ itemId, imageId: img.id })}>
                                            <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                                        </Popconfirm>
                                    )}
                                </Space>
                            </Card>
                        ))}
                    </Space>
                )}
            </div>
        </Drawer>
    );
}

function LookupModal({ onClose }: { onClose: () => void }) {
    const [file, setFile] = useState<File | null>(null);
    const [rerank, setRerank] = useState(true);
    const [result, setResult] = useState<VisualMatch | null>(null);
    const lookup = useVisualLookup();

    const run = () => {
        if (!file) return;
        lookup.mutate({ file, rerank }, { onSuccess: setResult });
    };

    return (
        <Modal open title="Tìm sản phẩm bằng ảnh" onCancel={onClose} footer={null}>
            <Space direction="vertical" style={{ width: '100%' }} size="middle">
                <Upload accept="image/*" maxCount={1} showUploadList={{ showPreviewIcon: false }} beforeUpload={(f) => { setFile(f as File); setResult(null); return false; }} onRemove={() => setFile(null)}>
                    <Button icon={<UploadOutlined />}>Chọn ảnh</Button>
                </Upload>
                <Space>
                    <Switch checked={rerank} onChange={setRerank} />
                    <Typography.Text>Dùng AI đối chiếu (chính xác hơn, tốn 1 lượt AI)</Typography.Text>
                </Space>
                <Button type="primary" icon={<SearchOutlined />} disabled={!file} loading={lookup.isPending} onClick={run}>
                    Tìm
                </Button>

                {result && (
                    <Card size="small">
                        {result.status === 'matched' && result.item ? (
                            <Space direction="vertical">
                                <Typography.Text strong>Khớp: {result.item.name}</Typography.Text>
                                {result.item.description && <Typography.Text type="secondary">{result.item.description}</Typography.Text>}
                                <Tag color="green">Độ tin cậy {(result.item.confidence * 100).toFixed(0)}% · {result.stage === 'rerank' ? 'AI đối chiếu' : 'so khớp ảnh'}</Tag>
                            </Space>
                        ) : result.status === 'ambiguous' ? (
                            <Space direction="vertical">
                                <Typography.Text strong>Chưa chắc — nhiều ứng viên gần giống:</Typography.Text>
                                {result.candidates.map((c) => (
                                    <Typography.Text key={c.item_id}>• {c.name} ({(c.confidence * 100).toFixed(0)}%)</Typography.Text>
                                ))}
                            </Space>
                        ) : (
                            <Empty description="Không tìm thấy sản phẩm khớp" />
                        )}
                    </Card>
                )}
            </Space>
        </Modal>
    );
}

export function VisualTrainingPanel() {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.ai.train');
    const { data: items, isLoading } = useVisualItems();
    const create = useCreateVisualItem();
    const update = useUpdateVisualItem();
    const del = useDeleteVisualItem();

    const [form] = Form.useForm();
    const [editing, setEditing] = useState<VisualItem | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [imagesItemId, setImagesItemId] = useState<number | null>(null);
    const [lookupOpen, setLookupOpen] = useState(false);

    const openCreate = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue?.({ applies_all_pages: true });
        setModalOpen(true);
    };
    const openEdit = (item: VisualItem) => {
        setEditing(item);
        form.setFieldsValue({ name: item.name, description: item.description, ref_code: item.ref_code, applies_all_pages: item.applies_all_pages });
        setModalOpen(true);
    };

    const submit = async () => {
        const v = await form.validateFields();
        const payload = { name: v.name, description: v.description ?? null, ref_code: v.ref_code ?? null, applies_all_pages: !!v.applies_all_pages };
        const opts = {
            onSuccess: () => {
                message.success('Đã lưu');
                setModalOpen(false);
            },
            onError: () => message.error('Lưu thất bại'),
        };
        if (editing) update.mutate({ id: editing.id, payload }, opts);
        else create.mutate(payload, opts);
    };

    const columns: ColumnsType<VisualItem> = [
        { title: 'Tên', dataIndex: 'name', key: 'name', render: (v, r) => (<Space direction="vertical" size={0}><Typography.Text strong>{v}</Typography.Text>{r.ref_code && <Typography.Text type="secondary" style={{ fontSize: 12 }}>Mã: {r.ref_code}</Typography.Text>}</Space>) },
        { title: 'Số ảnh', dataIndex: 'image_count', key: 'image_count', width: 90, render: (v) => <Tag>{v}</Tag> },
        { title: 'Phạm vi', dataIndex: 'applies_all_pages', key: 'scope', width: 130, render: (v) => (v ? <Tag color="blue">Tất cả trang</Tag> : <Tag>Theo trang</Tag>) },
        {
            title: '',
            key: 'actions',
            width: 220,
            render: (_, r) => (
                <Space>
                    <Button size="small" icon={<PictureOutlined />} onClick={() => setImagesItemId(r.id)}>Ảnh</Button>
                    <Button size="small" disabled={!canManage} onClick={() => openEdit(r)}>Sửa</Button>
                    {canManage && (
                        <Popconfirm title="Xoá sản phẩm này?" okButtonProps={{ danger: true }} onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá') })}>
                            <Button size="small" danger icon={<DeleteOutlined />} />
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <Card
            title={<Space><PictureOutlined /> Sản phẩm để AI nhận diện bằng ảnh</Space>}
            extra={
                <Space>
                    <Button icon={<SearchOutlined />} onClick={() => setLookupOpen(true)}>Tìm bằng ảnh</Button>
                    {canManage && <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm sản phẩm</Button>}
                </Space>
            }
        >
            <Typography.Paragraph type="secondary">
                Thêm sản phẩm + tải ảnh để AI nhận diện khi khách gửi ảnh trong tin nhắn. Mỗi sản phẩm có thể đặt 1 ảnh đại diện.
            </Typography.Paragraph>

            <Table<VisualItem> rowKey="id" loading={isLoading} dataSource={items ?? []} columns={columns} pagination={false} locale={{ emptyText: <Empty description="Chưa có sản phẩm AI training" /> }} />

            <Modal open={modalOpen} title={editing ? 'Sửa sản phẩm' : 'Thêm sản phẩm'} onOk={submit} onCancel={() => setModalOpen(false)} confirmLoading={create.isPending || update.isPending}>
                <Form form={form} layout="vertical">
                    <Form.Item name="name" label="Tên sản phẩm" rules={[{ required: true, message: 'Nhập tên' }]}>
                        <Input placeholder="VD: Áo thun cotton trắng" />
                    </Form.Item>
                    <Form.Item name="description" label="Nội dung" rules={[{ required: true, message: 'Nhập nội dung' }]}>
                        <Input.TextArea rows={6} placeholder="Mô tả sản phẩm, chất liệu, màu, size, công dụng, chính sách liên quan… (AI dùng nội dung này để tư vấn khách)" />
                    </Form.Item>
                    <Form.Item name="ref_code" label="Mã tham chiếu (tuỳ chọn)">
                        <Input placeholder="VD: SP001" />
                    </Form.Item>
                    <Form.Item name="applies_all_pages" label="Áp dụng tất cả trang" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                        Ảnh (tùy chọn — để AI gửi ảnh cho khách): lưu xong, bấm nút &quot;Ảnh&quot; trong danh sách để tải ảnh lên.
                    </Typography.Paragraph>
                </Form>
            </Modal>

            {imagesItemId != null && <ImagesDrawer itemId={imagesItemId} canManage={canManage} onClose={() => setImagesItemId(null)} />}
            {lookupOpen && <LookupModal onClose={() => setLookupOpen(false)} />}
        </Card>
    );
}
