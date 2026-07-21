// SPEC 0039 — quản lý thư viện hình nền màn Desktop (giao diện v2). Form tạo/sửa dùng Drawer
// (redesign 2026-07-21 §5.2 — bỏ Card nội tuyến làm form). Xoá dùng Popconfirm — tier Standard
// theo §5.1 (trước đây modal.confirm không lý do, cùng tier, gộp thống nhất về Popconfirm).
import { useState } from 'react';
import { App, Button, Card, Drawer, Form, Image, Input, InputNumber, Popconfirm, Space, Switch, Table, Tag, Upload } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import {
    type AdminDesktopBackground,
    useAdminDesktopBackgrounds,
    useCreateDesktopBackground,
    useUpdateDesktopBackground,
    useDeleteDesktopBackground,
    uploadDesktopBackgroundMedia,
} from '../lib/desktopBackgrounds';

interface FormShape {
    name: string;
    is_active: boolean;
    position: number;
}

export function AdminDesktopBackgroundsPage() {
    const { data: rows = [], isLoading } = useAdminDesktopBackgrounds();
    const create = useCreateDesktopBackground();
    const update = useUpdateDesktopBackground();
    const remove = useDeleteDesktopBackground();
    const { message } = App.useApp();
    const [form] = Form.useForm<FormShape>();
    const [open, setOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [image, setImage] = useState<{ url: string; path: string } | null>(null);
    const [uploading, setUploading] = useState(false);

    const openCreate = () => {
        form.resetFields();
        setEditingId(null);
        setImage(null);
        setOpen(true);
    };

    const openEdit = (bg: AdminDesktopBackground) => {
        setEditingId(bg.id);
        setImage({ url: bg.image_url, path: bg.image_path });
        form.setFieldsValue({ name: bg.name, is_active: bg.is_active, position: bg.position });
        setOpen(true);
    };

    const submit = (v: FormShape) => {
        if (!image) { message.error('Vui lòng tải ảnh nền lên.'); return; }
        const input = { name: v.name, image_url: image.url, image_path: image.path, is_active: v.is_active, position: v.position };
        const opts = { onSuccess: () => { message.success('Đã lưu hình nền.'); setOpen(false); }, onError: () => message.error('Lưu thất bại.') };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const columns: ColumnsType<AdminDesktopBackground> = [
        { title: 'Ảnh', dataIndex: 'image_url', width: 120, render: (url: string) => <Image src={url} width={96} height={54} style={{ objectFit: 'cover', borderRadius: 6 }} /> },
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Thứ tự', dataIndex: 'position', width: 90 },
        { title: 'Trạng thái', dataIndex: 'is_active', width: 120, render: (a: boolean) => <Tag color={a ? 'green' : 'default'}>{a ? 'Đang bật' : 'Tắt'}</Tag> },
        {
            title: 'Thao tác', width: 140, render: (_, bg) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(bg)} />
                    <Popconfirm
                        title={`Xoá hình nền "${bg.name}"?`}
                        onConfirm={() => remove.mutate(bg.id, { onSuccess: () => message.success('Đã xoá.') })}
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Card
                title="Thư viện hình nền" size="small"
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm hình nền</Button>}
            >
                <Table rowKey="id" size="small" loading={isLoading} columns={columns} dataSource={rows} pagination={false} />
            </Card>

            <Drawer
                open={open}
                title={editingId ? 'Sửa hình nền' : 'Thêm hình nền'}
                width={420}
                onClose={() => setOpen(false)}
                destroyOnHidden
            >
                <Form form={form} layout="vertical" initialValues={{ is_active: true, position: 0 }} onFinish={submit}>
                    <Form.Item label="Ảnh nền" required>
                        <Upload
                            accept="image/*" listType="picture-card" maxCount={1} showUploadList={false}
                            customRequest={async ({ file, onSuccess, onError }) => {
                                setUploading(true);
                                try { setImage(await uploadDesktopBackgroundMedia(file as File)); onSuccess?.({}); }
                                catch (e) { message.error('Tải ảnh thất bại.'); onError?.(e as Error); }
                                finally { setUploading(false); }
                            }}
                        >
                            {image
                                ? <img src={image.url} alt="nền" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                : <div>{uploading ? '...' : <><UploadOutlined /> <div>Tải lên</div></>}</div>}
                        </Upload>
                    </Form.Item>
                    <Form.Item name="name" label="Tên" rules={[{ required: true, max: 120 }]}>
                        <Input placeholder="VD: Biển xanh" />
                    </Form.Item>
                    <Form.Item name="position" label="Thứ tự hiển thị">
                        <InputNumber min={0} max={9999} style={{ width: 140 }} />
                    </Form.Item>
                    <Form.Item name="is_active" label="Bật cho người dùng chọn" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Space>
                        <Button type="primary" htmlType="submit" icon={<PlusOutlined />} loading={create.isPending || update.isPending}>
                            {editingId ? 'Cập nhật' : 'Thêm'}
                        </Button>
                        <Button onClick={() => setOpen(false)}>Huỷ</Button>
                    </Space>
                </Form>
            </Drawer>
        </Space>
    );
}
