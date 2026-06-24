// SPEC 0039 — quản lý thư viện hình nền màn Desktop (giao diện v2).
import { useState } from 'react';
import { App, Button, Card, Form, Image, Input, InputNumber, Space, Switch, Table, Tag, Upload } from 'antd';
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
    const { message, modal } = App.useApp();
    const [form] = Form.useForm<FormShape>();
    const [editingId, setEditingId] = useState<number | null>(null);
    const [image, setImage] = useState<{ url: string; path: string } | null>(null);
    const [uploading, setUploading] = useState(false);

    const reset = () => { form.resetFields(); setEditingId(null); setImage(null); };

    const startEdit = (bg: AdminDesktopBackground) => {
        setEditingId(bg.id);
        setImage({ url: bg.image_url, path: bg.image_path });
        form.setFieldsValue({ name: bg.name, is_active: bg.is_active, position: bg.position });
    };

    const submit = (v: FormShape) => {
        if (!image) { message.error('Vui lòng tải ảnh nền lên.'); return; }
        const input = { name: v.name, image_url: image.url, image_path: image.path, is_active: v.is_active, position: v.position };
        const opts = { onSuccess: () => { message.success('Đã lưu hình nền.'); reset(); }, onError: () => message.error('Lưu thất bại.') };
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
                    <Button size="small" icon={<EditOutlined />} onClick={() => startEdit(bg)} />
                    <Button size="small" danger icon={<DeleteOutlined />} onClick={() => modal.confirm({
                        title: `Xoá hình nền "${bg.name}"?`,
                        onOk: () => remove.mutateAsync(bg.id).then(() => message.success('Đã xoá.')),
                    })} />
                </Space>
            ),
        },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Card title={editingId ? 'Sửa hình nền' : 'Thêm hình nền'} size="small" style={{ maxWidth: 560 }}>
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
                        {editingId && <Button onClick={reset}>Huỷ</Button>}
                    </Space>
                </Form>
            </Card>

            <Card title="Thư viện hình nền" size="small">
                <Table rowKey="id" size="small" loading={isLoading} columns={columns} dataSource={rows} pagination={false} />
            </Card>
        </Space>
    );
}
